<?php

namespace BioSounds\Controller;

use BioSounds\Exception\ForbiddenException;
use BioSounds\Provider\RecordingProvider;
use BioSounds\Service\FileService;
use BioSounds\Utils\Auth;
use Cassandra\Varint;

/**
 * Class FileController
 * @package BioSounds\Controller
 */
class FileController
{
    /**
     * @param string $uploadDirectory
     * @return array
     * @throws \Exception
     */
    public function upload(string $uploadDirectory)
    {
        if (!Auth::isManage()) {
            throw new ForbiddenException();
        }
        (new FileService())->upload($_POST, 'tmp/' . $uploadDirectory . '/');

        return json_encode([
            'error_code' => 0,
            'message' => 'Files sent to the upload queue successfully.',
        ]);
    }

    public function metadata()
    {
        if (!Auth::isManage()) {
            throw new ForbiddenException();
        }

        if (!isset($_FILES['metaDataFile']) || $_FILES['metaDataFile']['error'] != UPLOAD_ERR_OK) {
            return json_encode([
                'error_code' => 1,
                'message' => 'No file uploaded or upload error occurred.',
            ]);
        }

        $handle = fopen($_FILES['metaDataFile']['tmp_name'], "rb");
        if (!$handle) {
            return json_encode([
                'error_code' => 1,
                'message' => 'Unable to open uploaded file.',
            ]);
        }

        $data = [];
        $rowNum = 1;
        $headers = null;
        
        while (!feof($handle)) {
            $row = fgetcsv($handle);
            
            // Skip empty rows
            if (!$row || empty(array_filter($row))) {
                $rowNum++;
                continue;
            }

            // First row is headers
            if ($headers === null) {
                $headers = array_map('trim', $row);
                
                // Validate required columns (support both recording_start and file_date/file_time)
                $hasRecordingStart = in_array('recording_start', $headers);
                $hasFileDate = in_array('file_date', $headers);
                $hasFileTime = in_array('file_time', $headers);
                
                if (!$hasRecordingStart && (!$hasFileDate || !$hasFileTime)) {
                    fclose($handle);
                    return json_encode([
                        'error_code' => 1,
                        'message' => "Missing required columns: either 'recording_start' OR both 'file_date' and 'file_time' are required.",
                    ]);
                }
                
                $requiredColumns = ['duration', 'sampling_rate'];
                foreach ($requiredColumns as $required) {
                    if (!in_array($required, $headers)) {
                        fclose($handle);
                        return json_encode([
                            'error_code' => 1,
                            'message' => "Missing required column: {$required}",
                        ]);
                    }
                }
                $rowNum++;
                continue;
            }

            // Map row data to headers
            $rowData = array_combine($headers, $row);
            
            // Validate required fields
            $hasRecordingStart = isset($rowData['recording_start']) && !empty($rowData['recording_start']);
            $hasFileDate = isset($rowData['file_date']) && !empty($rowData['file_date']);
            $hasFileTime = isset($rowData['file_time']) && !empty($rowData['file_time']);
            
            if (!$hasRecordingStart && (!$hasFileDate || !$hasFileTime)) {
                fclose($handle);
                return json_encode([
                    'error_code' => 1,
                    'message' => "Row {$rowNum}: recording_start OR both file_date and file_time are required.",
                ]);
            }
            
            // Parse recording_start if provided
            if ($hasRecordingStart) {
                if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $rowData['recording_start'])) {
                    fclose($handle);
                    return json_encode([
                        'error_code' => 1,
                        'message' => "Row {$rowNum}: recording_start must be in format YYYY-MM-DD HH:MM:SS.",
                    ]);
                }
                $parts = explode(' ', $rowData['recording_start']);
                $rowData['file_date'] = $parts[0];
                $rowData['file_time'] = $parts[1];
            } else {
                // Validate file_date and file_time formats
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $rowData['file_date'])) {
                    fclose($handle);
                    return json_encode([
                        'error_code' => 1,
                        'message' => "Row {$rowNum}: file_date must be in format YYYY-MM-DD.",
                    ]);
                }
                if (!preg_match('/^\d{2}:\d{2}:\d{2}$/', $rowData['file_time'])) {
                    fclose($handle);
                    return json_encode([
                        'error_code' => 1,
                        'message' => "Row {$rowNum}: file_time must be in format HH:MM:SS.",
                    ]);
                }
            }
            
            if (empty($rowData['duration']) || !is_numeric($rowData['duration']) || $rowData['duration'] <= 0) {
                fclose($handle);
                return json_encode([
                    'error_code' => 1,
                    'message' => "Row {$rowNum}: duration must be a positive number.",
                ]);
            }
            
            if (empty($rowData['sampling_rate']) || !is_numeric($rowData['sampling_rate']) || $rowData['sampling_rate'] <= 0) {
                fclose($handle);
                return json_encode([
                    'error_code' => 1,
                    'message' => "Row {$rowNum}: sampling_rate must be a positive integer.",
                ]);
            }
            
            // Validate optional integer fields
            $intFields = ['bitdepth', 'channel_num', 'duty_cycle_recording', 'duty_cycle_period', 'recording_gain'];
            foreach ($intFields as $field) {
                if (isset($rowData[$field]) && $rowData[$field] !== '' && !is_numeric($rowData[$field])) {
                    fclose($handle);
                    return json_encode([
                        'error_code' => 1,
                        'message' => "Row {$rowNum}: {$field} must be an integer.",
                    ]);
                }
            }
            
            // Validate optional ID fields (foreign keys)
            $idFields = ['site_id', 'recorder_id', 'microphone_id', 'license_id'];
            foreach ($idFields as $field) {
                if (isset($rowData[$field]) && $rowData[$field] !== '' && (!is_numeric($rowData[$field]) || $rowData[$field] <= 0)) {
                    fclose($handle);
                    return json_encode([
                        'error_code' => 1,
                        'message' => "Row {$rowNum}: {$field} must be a positive integer.",
                    ]);
                }
            }
            
            $data[] = $rowData;
            $rowNum++;
        }
        fclose($handle);

        if (empty($data)) {
            return json_encode([
                'error_code' => 1,
                'message' => 'No valid data rows found in CSV file.',
            ]);
        }

        // Insert recordings
        $inserted = 0;
        $recordingProvider = new RecordingProvider();
        
        foreach ($data as $recordingData) {
            $insertData = [
                'col_id' => $_POST['colId'],
                'user_id' => Auth::getUserID(),
                'file_date' => $recordingData['file_date'],
                'file_time' => $recordingData['file_time'],
                'duration' => (float)$recordingData['duration'],
                'sampling_rate' => (int)$recordingData['sampling_rate'],
                'data_type' => 'meta-data',
                'creation_date' => date('Y-m-d H:i:s', time()),
            ];
            
            // Optional string fields (with length limits from schema)
            if (!empty($recordingData['name'])) {
                $insertData['name'] = htmlentities(strip_tags(substr($recordingData['name'], 0, 250)), ENT_QUOTES);
            }
            if (!empty($recordingData['type'])) {
                $insertData['type'] = htmlentities(strip_tags(substr($recordingData['type'], 0, 50)), ENT_QUOTES);
            }
            if (!empty($recordingData['medium'])) {
                $insertData['medium'] = htmlentities(strip_tags(substr($recordingData['medium'], 0, 50)), ENT_QUOTES);
            }
            if (!empty($recordingData['note'])) {
                $insertData['note'] = htmlentities(strip_tags(substr($recordingData['note'], 0, 250)), ENT_QUOTES);
            }
            if (!empty($recordingData['DOI'])) {
                $insertData['DOI'] = htmlentities(strip_tags(substr($recordingData['DOI'], 0, 255)), ENT_QUOTES);
            }
            
            // Optional integer fields
            if (isset($recordingData['bitdepth']) && $recordingData['bitdepth'] !== '') {
                $insertData['bitdepth'] = (int)$recordingData['bitdepth'];
            }
            if (isset($recordingData['channel_num']) && $recordingData['channel_num'] !== '') {
                $insertData['channel_num'] = (int)$recordingData['channel_num'];
            }
            if (isset($recordingData['recording_gain']) && $recordingData['recording_gain'] !== '') {
                $insertData['recording_gain'] = (int)$recordingData['recording_gain'];
            }
            if (isset($recordingData['duty_cycle_recording']) && $recordingData['duty_cycle_recording'] !== '') {
                $insertData['duty_cycle_recording'] = (int)$recordingData['duty_cycle_recording'];
            }
            if (isset($recordingData['duty_cycle_period']) && $recordingData['duty_cycle_period'] !== '') {
                $insertData['duty_cycle_period'] = (int)$recordingData['duty_cycle_period'];
            }
            
            // Optional foreign key fields
            if (isset($recordingData['site_id']) && $recordingData['site_id'] !== '') {
                $insertData['site_id'] = (int)$recordingData['site_id'];
            }
            if (isset($recordingData['recorder_id']) && $recordingData['recorder_id'] !== '') {
                $insertData['recorder_id'] = (int)$recordingData['recorder_id'];
            }
            if (isset($recordingData['microphone_id']) && $recordingData['microphone_id'] !== '') {
                $insertData['microphone_id'] = (int)$recordingData['microphone_id'];
            }
            if (isset($recordingData['license_id']) && $recordingData['license_id'] !== '') {
                $insertData['license_id'] = (int)$recordingData['license_id'];
            }
            
            $recordingProvider->insert($insertData);
            $inserted++;
        }

        return json_encode([
            'error_code' => 0,
            'message' => "Successfully uploaded {$inserted} recording metadata entries.",
        ]);
    }
}
