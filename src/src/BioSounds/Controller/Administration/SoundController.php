<?php

namespace BioSounds\Controller\Administration;

use BioSounds\Controller\BaseController;
use BioSounds\Entity\Sound;
use BioSounds\Exception\ForbiddenException;
use BioSounds\Utils\Auth;

class SoundController extends BaseController
{
    const SECTION_TITLE = 'Sounds';

    /**
     * @return false|string
     * @throws \Exception
     */
    public function show()
    {
        if (!Auth::isUserAdmin()) {
            throw new ForbiddenException();
        }

        $sound = new Sound();
        $sounds = $sound->getAll();

        return $this->twig->render('administration/sounds.html.twig', [
            'sounds' => $sounds,
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

            $sound = new Sound();
            $data = [];
            $itemID = null;

            foreach ($_POST as $key => $value) {
                $fieldName = preg_replace('/_(?:text|number|hidden|select-one|date|time|checkbox|email|tel)$/', '', $key);
                
                if ($fieldName === 'itemID') {
                    $itemID = filter_var($value, FILTER_SANITIZE_NUMBER_INT);
                    continue;
                }
                
                if ($fieldName === 'sound_id') {
                    $data[$fieldName] = filter_var($value, FILTER_SANITIZE_NUMBER_INT);
                } else {
                    $data[$fieldName] = htmlentities(strip_tags(filter_var($value, FILTER_SANITIZE_STRING)), ENT_QUOTES);
                }
            }

            if (!empty($itemID)) {
                $sound->update($data, $itemID);
            } else {
                $sound->insert($data);
            }

            return json_encode([
                'errorCode' => 0,
                'message' => 'Sound saved successfully.',
            ]);
        } catch (ForbiddenException $e) {
            return json_encode([
                'errorCode' => 403,
                'message' => 'Access denied.',
            ]);
        } catch (\Exception $e) {
            return json_encode([
                'errorCode' => 1,
                'message' => 'Error saving sound: ' . $e->getMessage(),
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
            
            $sound = new Sound();
            foreach ($ids as $id) {
                $soundId = (int) filter_var($id, FILTER_SANITIZE_NUMBER_INT);
                $sound->delete($soundId);
            }

            return json_encode([
                'errorCode' => 0,
                'message' => 'Sound(s) deleted successfully.',
            ]);
        } catch (ForbiddenException $e) {
            return json_encode([
                'errorCode' => 403,
                'message' => 'Access denied.',
            ]);
        } catch (\Exception $e) {
            return json_encode([
                'errorCode' => 1,
                'message' => 'Error deleting sound: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Upload sounds from CSV file
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

            if (!isset($_FILES['soundsCSVFile']) || $_FILES['soundsCSVFile']['error'] != UPLOAD_ERR_OK) {
                return json_encode([
                    'errorCode' => 1,
                    'message' => 'No file uploaded or upload error occurred.',
                ]);
            }

            $handle = fopen($_FILES['soundsCSVFile']['tmp_name'], "rb");
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

            // Insert sounds
            $sound = new Sound();
            $inserted = 0;
            foreach ($data as $soundData) {
                $insertData = [];
                
                if (empty($soundData['soundscape_component'])) {
                    continue; // Skip rows without required soundscape_component
                }
                
                $insertData['soundscape_component'] = htmlentities(strip_tags($soundData['soundscape_component']), ENT_QUOTES);
                
                if (!empty($soundData['sound_type'])) {
                    $insertData['sound_type'] = htmlentities(strip_tags($soundData['sound_type']), ENT_QUOTES);
                }
                
                $sound->insert($insertData);
                $inserted++;
            }

            return json_encode([
                'errorCode' => 0,
                'message' => "Successfully uploaded {$inserted} sounds.",
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
     * Export all sounds to CSV
     * @return void
     * @throws \Exception
     */
    public function export()
    {
        if (!Auth::isUserAdmin()) {
            throw new ForbiddenException();
        }

        $sound = new Sound();
        $sounds = $sound->getAll();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=sounds_export.csv');
        
        $fp = fopen('php://output', 'w');
        
        // Write headers
        fputcsv($fp, ['sound_id', 'soundscape_component', 'sound_type']);
        
        // Write data
        foreach ($sounds as $s) {
            fputcsv($fp, [
                $s['sound_id'] ?? '',
                $s['soundscape_component'] ?? '',
                $s['sound_type'] ?? ''
            ]);
        }
        
        fclose($fp);
        exit();
    }
}
