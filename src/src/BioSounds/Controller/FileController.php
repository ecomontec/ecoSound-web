<?php

namespace BioSounds\Controller;

use BioSounds\Exception\ForbiddenException;
use BioSounds\Provider\RecordingProvider;
use BioSounds\Provider\SiteProvider;
use BioSounds\Entity\License;
use BioSounds\Entity\Recorder;
use BioSounds\Entity\Microphone;
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
            'errorCode' => 0,
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
                
                // Validate required columns
                $requiredColumns = ['file_date', 'file_time', 'duration', 'sampling_rate'];
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
            if (empty($rowData['file_date'])) {
                fclose($handle);
                return json_encode([
                    'error_code' => 1,
                    'message' => "Row {$rowNum}: file_date is required.",
                ]);
            }
            
            if (empty($rowData['file_time'])) {
                fclose($handle);
                return json_encode([
                    'error_code' => 1,
                    'message' => "Row {$rowNum}: file_time is required.",
                ]);
            }
            
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
            
            // Validate type enum values
            if (isset($rowData['type']) && $rowData['type'] !== '') {
                $allowedTypes = ['Passive', 'Focal', 'Enclosure'];
                if (!in_array($rowData['type'], $allowedTypes)) {
                    fclose($handle);
                    return json_encode([
                        'error_code' => 1,
                        'message' => "Row {$rowNum}: type must be one of: " . implode(', ', $allowedTypes) . ".",
                    ]);
                }
            }
            
            // Validate medium enum values
            if (isset($rowData['medium']) && $rowData['medium'] !== '') {
                $allowedMediums = ['Air', 'Water'];
                if (!in_array($rowData['medium'], $allowedMediums)) {
                    fclose($handle);
                    return json_encode([
                        'error_code' => 1,
                        'message' => "Row {$rowNum}: medium must be one of: " . implode(', ', $allowedMediums) . ".",
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

        // Validate foreign key IDs exist in database
        $siteProvider = new SiteProvider();
        $licenseEntity = new License();
        $recorderEntity = new Recorder();
        $microphoneEntity = new Microphone();
        
        foreach ($data as $index => $recordingData) {
            $rowNum = $index + 2; // +2 because index starts at 0 and we skip header row
            
            if (isset($recordingData['site_id']) && $recordingData['site_id'] !== '') {
                $siteId = (int)$recordingData['site_id'];
                try {
                    $site = $siteProvider->get((string)$siteId);
                    if (empty($site)) {
                        return json_encode([
                            'error_code' => 1,
                            'message' => "Row {$rowNum}: site_id {$siteId} does not exist in the database.",
                        ]);
                    }
                } catch (\Exception $e) {
                    return json_encode([
                        'error_code' => 1,
                        'message' => "Row {$rowNum}: site_id {$siteId} does not exist in the database.",
                    ]);
                }
            }
            
            if (isset($recordingData['license_id']) && $recordingData['license_id'] !== '') {
                $licenseId = (int)$recordingData['license_id'];
                $licenses = $licenseEntity->getBasicList();
                $licenseExists = false;
                foreach ($licenses as $license) {
                    if ($license['license_id'] == $licenseId) {
                        $licenseExists = true;
                        break;
                    }
                }
                if (!$licenseExists) {
                    return json_encode([
                        'error_code' => 1,
                        'message' => "Row {$rowNum}: license_id {$licenseId} does not exist in the database.",
                    ]);
                }
            }
            
            if (isset($recordingData['recorder_id']) && $recordingData['recorder_id'] !== '') {
                $recorderId = (int)$recordingData['recorder_id'];
                $recorders = $recorderEntity->getBasicList();
                $recorderExists = false;
                foreach ($recorders as $recorder) {
                    if ($recorder['recorder_id'] == $recorderId) {
                        $recorderExists = true;
                        break;
                    }
                }
                if (!$recorderExists) {
                    return json_encode([
                        'error_code' => 1,
                        'message' => "Row {$rowNum}: recorder_id {$recorderId} does not exist in the database.",
                    ]);
                }
            }
            
            if (isset($recordingData['microphone_id']) && $recordingData['microphone_id'] !== '') {
                $microphoneId = (int)$recordingData['microphone_id'];
                $microphones = $microphoneEntity->getBasicList();
                $microphoneExists = false;
                foreach ($microphones as $microphone) {
                    if ($microphone['microphone_id'] == $microphoneId) {
                        $microphoneExists = true;
                        break;
                    }
                }
                if (!$microphoneExists) {
                    return json_encode([
                        'error_code' => 1,
                        'message' => "Row {$rowNum}: microphone_id {$microphoneId} does not exist in the database.",
                    ]);
                }
            }
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
