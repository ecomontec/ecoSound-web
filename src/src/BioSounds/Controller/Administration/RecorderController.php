<?php

namespace BioSounds\Controller\Administration;

use BioSounds\Controller\BaseController;
use BioSounds\Entity\Recorder;
use BioSounds\Exception\ForbiddenException;
use BioSounds\Utils\Auth;

class RecorderController extends BaseController
{
    const SECTION_TITLE = 'Recorders';

    /**
     * @return false|string
     * @throws \Exception
     */
    public function show()
    {
        if (!Auth::isUserAdmin()) {
            throw new ForbiddenException();
        }

        $recorder = new Recorder();
        $recorders = $recorder->getAll();

        return $this->twig->render('administration/recorders.html.twig', [
            'recorders' => $recorders,
        ]);
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function save()
    {
        header('Content-Type: application/json');
        
        try {
            if (!Auth::isUserAdmin()) {
                throw new ForbiddenException();
            }

            $recorder = new Recorder();
            $data = [];
            $itemID = null;

            foreach ($_POST as $key => $value) {
                $fieldName = preg_replace('/_(?:text|number|hidden|select-one|date|time|checkbox|email|tel)$/', '', $key);
                
                if ($fieldName === 'itemID') {
                    $itemID = filter_var($value, FILTER_SANITIZE_NUMBER_INT);
                    continue;
                }
                
                if (in_array($fieldName, ['recorder_id', 'sensitivity', 'signal_to_noise_ratio'])) {
                    $data[$fieldName] = filter_var($value, FILTER_SANITIZE_NUMBER_INT);
                } else {
                    $data[$fieldName] = htmlentities(strip_tags(filter_var($value, FILTER_SANITIZE_STRING)), ENT_QUOTES);
                }
            }

            if (!empty($itemID)) {
                $recorder->update($data, $itemID);
            } else {
                $recorder->insert($data);
            }

            return json_encode([
                'errorCode' => 0,
                'message' => 'Recorder saved successfully.',
            ]);
        } catch (ForbiddenException $e) {
            return json_encode([
                'errorCode' => 403,
                'message' => 'Access denied.',
            ]);
        } catch (\Exception $e) {
            return json_encode([
                'errorCode' => 1,
                'message' => 'Error saving recorder: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function delete()
    {
        header('Content-Type: application/json');
        
        try {
            if (!Auth::isUserAdmin()) {
                throw new ForbiddenException();
            }

            $ids = $_POST['id'];
            if (!is_array($ids)) {
                $ids = [$ids];
            }
            
            $recorder = new Recorder();
            foreach ($ids as $id) {
                $recorderId = (int) filter_var($id, FILTER_SANITIZE_NUMBER_INT);
                $recorder->delete($recorderId);
            }

            return json_encode([
                'errorCode' => 0,
                'message' => 'Recorder(s) deleted successfully.',
            ]);
        } catch (ForbiddenException $e) {
            return json_encode([
                'errorCode' => 403,
                'message' => 'Access denied.',
            ]);
        } catch (\Exception $e) {
            return json_encode([
                'errorCode' => 1,
                'message' => 'Error deleting recorder: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Upload recorders from CSV file
     * @return string
     * @throws \Exception
     */
    public function uploadCSV()
    {
        header('Content-Type: application/json');
        
        try {
            if (!Auth::isUserAdmin()) {
                throw new ForbiddenException();
            }

            if (!isset($_FILES['recordersCSVFile']) || $_FILES['recordersCSVFile']['error'] != UPLOAD_ERR_OK) {
                return json_encode([
                    'errorCode' => 1,
                    'message' => 'No file uploaded or upload error occurred.',
                ]);
            }

            $handle = fopen($_FILES['recordersCSVFile']['tmp_name'], "rb");
            if (!$handle) {
                return json_encode([
                    'errorCode' => 1,
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
                    $rowNum++;
                    continue;
                }

                // Pad row to match header length
                $row = array_pad($row, count($headers), '');
                $row = array_slice($row, 0, count($headers));
                $row = array_map('trim', $row);
                
                // Map row data to headers
                $rowData = array_combine($headers, $row);
                
                $data[] = $rowData;
                $rowNum++;
            }
            fclose($handle);

            if (empty($data)) {
                return json_encode([
                    'errorCode' => 1,
                    'message' => 'No valid data rows found in CSV file.',
                ]);
            }

            // Insert recorders
            $recorder = new Recorder();
            $inserted = 0;
            foreach ($data as $recorderData) {
                $insertData = [];
                
                if (!empty($recorderData['model'])) {
                    $insertData['model'] = htmlentities(strip_tags($recorderData['model']), ENT_QUOTES);
                }
                if (!empty($recorderData['version'])) {
                    $insertData['version'] = htmlentities(strip_tags($recorderData['version']), ENT_QUOTES);
                }
                if (!empty($recorderData['brand'])) {
                    $insertData['brand'] = htmlentities(strip_tags($recorderData['brand']), ENT_QUOTES);
                }
                if (!empty($recorderData['microphone'])) {
                    $insertData['microphone'] = htmlentities(strip_tags($recorderData['microphone']), ENT_QUOTES);
                }
                
                if (!empty($insertData)) {
                    $recorder->insert($insertData);
                    $inserted++;
                }
            }

            return json_encode([
                'errorCode' => 0,
                'message' => "Successfully uploaded {$inserted} recorders.",
            ]);
        } catch (ForbiddenException $e) {
            return json_encode([
                'errorCode' => 403,
                'message' => 'Access denied.',
            ]);
        } catch (\Exception $e) {
            return json_encode([
                'errorCode' => 1,
                'message' => 'Error uploading CSV: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Export all recorders to CSV
     * @return void
     * @throws \Exception
     */
    public function export()
    {
        if (!Auth::isUserAdmin()) {
            throw new ForbiddenException();
        }

        $recorder = new Recorder();
        $recorders = $recorder->getAll();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=recorders_export.csv');
        
        $fp = fopen('php://output', 'w');
        
        // Write headers
        fputcsv($fp, ['recorder_id', 'model', 'version', 'brand', 'microphone']);
        
        // Write data
        foreach ($recorders as $rec) {
            fputcsv($fp, [
                $rec['recorder_id'] ?? '',
                $rec['model'] ?? '',
                $rec['version'] ?? '',
                $rec['brand'] ?? '',
                $rec['microphone'] ?? ''
            ]);
        }
        
        fclose($fp);
        exit();
    }
}
