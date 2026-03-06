<?php

namespace BioSounds\Controller\Administration;

use BioSounds\Controller\BaseController;
use BioSounds\Entity\SoundType;
use BioSounds\Exception\ForbiddenException;
use BioSounds\Utils\Auth;

class SoundTypeController extends BaseController
{
    const SECTION_TITLE = 'Sound Types';

    /**
     * @return false|string
     * @throws \Exception
     */
    public function show()
    {
        if (!Auth::isUserAdmin()) {
            throw new ForbiddenException();
        }

        $soundType = new SoundType();
        $soundTypes = $soundType->getAll();

        return $this->twig->render('administration/soundTypes.html.twig', [
            'soundTypes' => $soundTypes,
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

            $soundType = new SoundType();
            $data = [];
            $itemID = null;

            foreach ($_POST as $key => $value) {
                $fieldName = preg_replace('/_(?:text|number|hidden|select-one|date|time|checkbox|email|tel)$/', '', $key);
                
                if ($fieldName === 'itemID') {
                    $itemID = filter_var($value, FILTER_SANITIZE_NUMBER_INT);
                    continue;
                }
                
                if ($fieldName === 'sound_type_id') {
                    $data[$fieldName] = filter_var($value, FILTER_SANITIZE_NUMBER_INT);
                } else {
                    $data[$fieldName] = htmlentities(strip_tags(filter_var($value, FILTER_SANITIZE_STRING)), ENT_QUOTES);
                }
            }

            if (!empty($itemID)) {
                $soundType->update($data, $itemID);
            } else {
                $soundType->insert($data);
            }

            return json_encode([
                'errorCode' => 0,
                'message' => 'Sound type saved successfully.',
            ]);
        } catch (ForbiddenException $e) {
            return json_encode([
                'errorCode' => 403,
                'message' => 'Access denied.',
            ]);
        } catch (\Exception $e) {
            return json_encode([
                'errorCode' => 1,
                'message' => 'Error saving sound type: ' . $e->getMessage(),
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
            
            $soundType = new SoundType();
            foreach ($ids as $id) {
                $soundTypeId = (int) filter_var($id, FILTER_SANITIZE_NUMBER_INT);
                $soundType->delete($soundTypeId);
            }

            return json_encode([
                'errorCode' => 0,
                'message' => 'Sound type(s) deleted successfully.',
            ]);
        } catch (ForbiddenException $e) {
            return json_encode([
                'errorCode' => 403,
                'message' => 'Access denied.',
            ]);
        } catch (\Exception $e) {
            return json_encode([
                'errorCode' => 1,
                'message' => 'Error deleting sound type: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Upload sound types from CSV file
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

            if (!isset($_FILES['soundTypesCSVFile']) || $_FILES['soundTypesCSVFile']['error'] != UPLOAD_ERR_OK) {
                return json_encode([
                    'errorCode' => 1,
                    'message' => 'No file uploaded or upload error occurred.',
                ]);
            }

            $handle = fopen($_FILES['soundTypesCSVFile']['tmp_name'], "rb");
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
                    
                    // Validate required columns
                    $requiredColumns = ['sound_type_id', 'name', 'taxon_class', 'taxon_order'];
                    foreach ($requiredColumns as $required) {
                        if (!in_array($required, $headers)) {
                            fclose($handle);
                            return json_encode([
                                'errorCode' => 1,
                                'message' => "Missing required column: {$required}",
                            ]);
                        }
                    }
                    $rowNum++;
                    continue;
                }

                // Pad row to match header length
                $row = array_pad($row, count($headers), '');
                $row = array_slice($row, 0, count($headers));
                $row = array_map('trim', $row);
                
                // Map row data to headers
                $rowData = array_combine($headers, $row);
                
                // Validate required fields
                if (empty($rowData['sound_type_id'])) {
                    fclose($handle);
                    return json_encode([
                        'errorCode' => 1,
                        'message' => "Row {$rowNum}: sound_type_id is required.",
                    ]);
                }
                
                if (empty($rowData['name'])) {
                    fclose($handle);
                    return json_encode([
                        'errorCode' => 1,
                        'message' => "Row {$rowNum}: name is required.",
                    ]);
                }
                
                if (empty($rowData['taxon_class'])) {
                    fclose($handle);
                    return json_encode([
                        'errorCode' => 1,
                        'message' => "Row {$rowNum}: taxon_class is required.",
                    ]);
                }
                
                if (empty($rowData['taxon_order'])) {
                    fclose($handle);
                    return json_encode([
                        'errorCode' => 1,
                        'message' => "Row {$rowNum}: taxon_order is required.",
                    ]);
                }
                
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

            // Insert sound types
            $soundType = new SoundType();
            $inserted = 0;
            foreach ($data as $soundTypeData) {
                $insertData = [
                    'sound_type_id' => (int)$soundTypeData['sound_type_id'],
                    'name' => htmlentities(strip_tags($soundTypeData['name']), ENT_QUOTES),
                    'taxon_class' => htmlentities(strip_tags($soundTypeData['taxon_class']), ENT_QUOTES),
                    'taxon_order' => htmlentities(strip_tags($soundTypeData['taxon_order']), ENT_QUOTES),
                ];
                
                $soundType->insert($insertData);
                $inserted++;
            }

            return json_encode([
                'errorCode' => 0,
                'message' => "Successfully uploaded {$inserted} sound types.",
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
     * Export all sound types to CSV
     * @return void
     * @throws \Exception
     */
    public function export()
    {
        if (!Auth::isUserAdmin()) {
            throw new ForbiddenException();
        }

        $soundType = new SoundType();
        $soundTypes = $soundType->getAll();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=sound_types_export.csv');
        
        $fp = fopen('php://output', 'w');
        
        // Write headers
        fputcsv($fp, ['sound_type_id', 'name', 'taxon_class', 'taxon_order']);
        
        // Write data
        foreach ($soundTypes as $st) {
            fputcsv($fp, [
                $st['sound_type_id'] ?? '',
                $st['name'] ?? '',
                $st['taxon_class'] ?? '',
                $st['taxon_order'] ?? ''
            ]);
        }
        
        fclose($fp);
        exit();
    }
}
