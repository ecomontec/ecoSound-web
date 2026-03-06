<?php

namespace BioSounds\Controller\Administration;

use BioSounds\Controller\BaseController;
use BioSounds\Entity\Microphone;
use BioSounds\Exception\ForbiddenException;
use BioSounds\Utils\Auth;

class MicrophoneController extends BaseController
{
    const SECTION_TITLE = 'Microphones';

    /**
     * @return false|string
     * @throws \Exception
     */
    public function show()
    {
        if (!Auth::isUserAdmin()) {
            throw new ForbiddenException();
        }

        $microphone = new Microphone();
        $microphones = $microphone->getAll();

        return $this->twig->render('administration/microphones.html.twig', [
            'microphones' => $microphones,
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

            $microphone = new Microphone();
            $data = [];
            $itemID = null;

            foreach ($_POST as $key => $value) {
                $fieldName = preg_replace('/_(?:text|number|hidden|select-one|date|time|checkbox|email|tel)$/', '', $key);
                
                if ($fieldName === 'itemID') {
                    $itemID = filter_var($value, FILTER_SANITIZE_NUMBER_INT);
                    continue;
                }
                
                if (in_array($fieldName, ['microphone_id', 'sensitivity', 'signal_to_noise_ratio'])) {
                    $value = empty($value) ? null : filter_var($value, FILTER_SANITIZE_NUMBER_INT);
                    $data[$fieldName] = $value;
                } else {
                    $data[$fieldName] = htmlentities(strip_tags(filter_var($value, FILTER_SANITIZE_STRING)), ENT_QUOTES);
                }
            }

            if (!empty($itemID)) {
                $microphone->update($data, $itemID);
            } else {
                $microphone->insert($data);
            }

            return json_encode([
                'errorCode' => 0,
                'message' => 'Microphone saved successfully.',
            ]);
        } catch (ForbiddenException $e) {
            return json_encode([
                'errorCode' => 403,
                'message' => 'Access denied.',
            ]);
        } catch (\Exception $e) {
            return json_encode([
                'errorCode' => 1,
                'message' => 'Error saving microphone: ' . $e->getMessage(),
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
            
            $microphone = new Microphone();
            foreach ($ids as $id) {
                $microphoneId = (int) filter_var($id, FILTER_SANITIZE_NUMBER_INT);
                $microphone->delete($microphoneId);
            }

            return json_encode([
                'errorCode' => 0,
                'message' => 'Microphone(s) deleted successfully.',
            ]);
        } catch (ForbiddenException $e) {
            return json_encode([
                'errorCode' => 403,
                'message' => 'Access denied.',
            ]);
        } catch (\Exception $e) {
            return json_encode([
                'errorCode' => 1,
                'message' => 'Error deleting microphone: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Upload microphones from CSV file
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

            if (!isset($_FILES['microphonesCSVFile']) || $_FILES['microphonesCSVFile']['error'] != UPLOAD_ERR_OK) {
                return json_encode([
                    'errorCode' => 1,
                    'message' => 'No file uploaded or upload error occurred.',
                ]);
            }

            $handle = fopen($_FILES['microphonesCSVFile']['tmp_name'], "rb");
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

            // Insert microphones
            $microphone = new Microphone();
            $inserted = 0;
            foreach ($data as $microphoneData) {
                $insertData = [];
                
                if (!empty($microphoneData['name'])) {
                    $insertData['name'] = htmlentities(strip_tags($microphoneData['name']), ENT_QUOTES);
                }
                if (!empty($microphoneData['microphone_element'])) {
                    $insertData['microphone_element'] = htmlentities(strip_tags($microphoneData['microphone_element']), ENT_QUOTES);
                }
                if (!empty($microphoneData['sensitivity'])) {
                    $insertData['sensitivity'] = (int)$microphoneData['sensitivity'];
                }
                if (!empty($microphoneData['signal_to_noise_ratio'])) {
                    $insertData['signal_to_noise_ratio'] = (int)$microphoneData['signal_to_noise_ratio'];
                }
                
                if (!empty($insertData)) {
                    $microphone->insert($insertData);
                    $inserted++;
                }
            }

            return json_encode([
                'errorCode' => 0,
                'message' => "Successfully uploaded {$inserted} microphones.",
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
     * Export all microphones to CSV
     * @return void
     * @throws \Exception
     */
    public function export()
    {
        if (!Auth::isUserAdmin()) {
            throw new ForbiddenException();
        }

        $microphone = new Microphone();
        $microphones = $microphone->getAll();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=microphones_export.csv');
        
        $fp = fopen('php://output', 'w');
        
        // Write headers
        fputcsv($fp, ['microphone_id', 'name', 'microphone_element', 'sensitivity', 'signal_to_noise_ratio']);
        
        // Write data
        foreach ($microphones as $mic) {
            fputcsv($fp, [
                $mic['microphone_id'] ?? '',
                $mic['name'] ?? '',
                $mic['microphone_element'] ?? '',
                $mic['sensitivity'] ?? '',
                $mic['signal_to_noise_ratio'] ?? ''
            ]);
        }
        
        fclose($fp);
        exit();
    }
}
