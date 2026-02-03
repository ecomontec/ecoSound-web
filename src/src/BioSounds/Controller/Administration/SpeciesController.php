<?php

namespace BioSounds\Controller\Administration;

use BioSounds\Controller\BaseController;
use BioSounds\Entity\Species;
use BioSounds\Exception\ForbiddenException;
use BioSounds\Utils\Auth;

class SpeciesController extends BaseController
{
    const SECTION_TITLE = 'Species';
    const ITEMS_PAGE = 50;

    /**
     * @param int $page
     * @return false|string
     * @throws \Exception
     */
    public function show(int $page = 1)
    {
        if (!Auth::isUserAdmin()) {
            throw new ForbiddenException();
        }

        $species = new Species();
        $allSpecies = $species->getAll();
        
        $totalItems = count($allSpecies);
        $pages = ceil($totalItems / self::ITEMS_PAGE);
        
        $offset = ($page - 1) * self::ITEMS_PAGE;
        $speciesList = array_slice($allSpecies, $offset, self::ITEMS_PAGE);

        return $this->twig->render('administration/species.html.twig', [
            'species' => $speciesList,
            'currentPage' => $page,
            'pages' => $pages,
            'totalItems' => $totalItems,
        ]);
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function save()
    {
        if (!Auth::isUserAdmin()) {
            throw new ForbiddenException();
        }

        $species = new Species();
        // Debug: log raw $_POST and php://input
        file_put_contents('/tmp/species_post_debug.log', "_POST:\n" . print_r($_POST, true) . "\nphp://input:\n" . file_get_contents('php://input') . "\n\n", FILE_APPEND);

        $data = [];
        $itemID = null;
        $postData = $_POST;
        // If $_POST is empty, try to parse php://input (for non-standard POSTs)
        if (empty($postData)) {
            $rawInput = file_get_contents('php://input');
            parse_str($rawInput, $postData);
        }
        foreach ($postData as $key => $value) {
            // Strip _type suffix (e.g., binomial_text -> binomial, itemID_hidden -> itemID)
            $fieldName = preg_replace('/_(?:text|number|hidden|select-one|date|time|checkbox|email|tel)$/', '', $key);
            
            if ($fieldName === 'itemID') {
                $itemID = filter_var($value, FILTER_SANITIZE_NUMBER_INT);
                continue;
            }
            
            // Sanitize numeric fields
            if (in_array($fieldName, ['species_id', 'level'])) {
                $data[$fieldName] = filter_var($value, FILTER_SANITIZE_NUMBER_INT);
            } else {
                $data[$fieldName] = htmlentities(strip_tags(filter_var($value, FILTER_SANITIZE_STRING)), ENT_QUOTES);
            }
        }

        if (!empty($itemID)) {
            $species->update($data, $itemID);
        } else {
            // Generate next species_id
            $allSpecies = $species->getAll();
            $maxId = 0;
            foreach ($allSpecies as $sp) {
                if (isset($sp['species_id']) && $sp['species_id'] > $maxId) {
                    $maxId = $sp['species_id'];
                }
            }
            $data['species_id'] = $maxId + 1;
            file_put_contents('/tmp/species_debug.log', print_r($data, true), FILE_APPEND);
            $species->insert($data);
        }

        return json_encode([
            'errorCode' => 0,
            'message' => 'Species saved successfully.',
        ]);
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function delete()
    {
        if (!Auth::isUserAdmin()) {
            throw new ForbiddenException();
        }

        $ids = $_POST['id'];
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        
        $species = new Species();
        foreach ($ids as $id) {
            $speciesId = (int) filter_var($id, FILTER_SANITIZE_NUMBER_INT);
            $species->delete($speciesId);
        }

        return json_encode([
            'errorCode' => 0,
            'message' => 'Species deleted successfully.',
        ]);
    }

    /**
     * @param int $id
     * @return string
     * @throws \Exception
     */
    public function get(int $id)
    {
        if (!Auth::isUserAdmin()) {
            throw new ForbiddenException();
        }

        $species = new Species();
        $speciesData = $species->getById($id);

        return json_encode([
            'errorCode' => 0,
            'data' => $speciesData,
        ]);
    }

    /**
     * Upload species from CSV file
     * @return string
     * @throws \Exception
     */
    public function uploadCSV()
    {
        if (!Auth::isUserAdmin()) {
            throw new ForbiddenException();
        }

        if (!isset($_FILES['speciesCSVFile']) || $_FILES['speciesCSVFile']['error'] != UPLOAD_ERR_OK) {
            return json_encode([
                'error_code' => 1,
                'message' => 'No file uploaded or upload error occurred.',
            ]);
        }

        $handle = fopen($_FILES['speciesCSVFile']['tmp_name'], "rb");
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
                $requiredColumns = ['binomial', 'common_name', 'level', 'source'];
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
            if (empty($rowData['binomial'])) {
                fclose($handle);
                return json_encode([
                    'error_code' => 1,
                    'message' => "Row {$rowNum}: binomial is required.",
                ]);
            }
            
            if (empty($rowData['common_name'])) {
                fclose($handle);
                return json_encode([
                    'error_code' => 1,
                    'message' => "Row {$rowNum}: common_name is required.",
                ]);
            }
            
            if (!isset($rowData['level']) || $rowData['level'] === '') {
                fclose($handle);
                return json_encode([
                    'error_code' => 1,
                    'message' => "Row {$rowNum}: level is required.",
                ]);
            }
            
            if (!is_numeric($rowData['level']) || ($rowData['level'] != 0 && $rowData['level'] != 1)) {
                fclose($handle);
                return json_encode([
                    'error_code' => 1,
                    'message' => "Row {$rowNum}: level must be 0 or 1 (1 if species level, 0 otherwise).",
                ]);
            }
            
            if (empty($rowData['source'])) {
                fclose($handle);
                return json_encode([
                    'error_code' => 1,
                    'message' => "Row {$rowNum}: source is required.",
                ]);
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

        // Get max species_id
        $species = new Species();
        $allSpecies = $species->getAll();
        $maxId = 0;
        foreach ($allSpecies as $sp) {
            if (isset($sp['species_id']) && $sp['species_id'] > $maxId) {
                $maxId = $sp['species_id'];
            }
        }

        // Insert species
        $inserted = 0;
        foreach ($data as $speciesData) {
            $maxId++;
            $insertData = [
                'species_id' => $maxId,
                'binomial' => htmlentities(strip_tags($speciesData['binomial']), ENT_QUOTES),
                'common_name' => htmlentities(strip_tags($speciesData['common_name']), ENT_QUOTES),
                'level' => (int)$speciesData['level'],
                'source' => htmlentities(strip_tags($speciesData['source']), ENT_QUOTES),
            ];
            
            // Optional fields
            if (!empty($speciesData['genus'])) {
                $insertData['genus'] = htmlentities(strip_tags($speciesData['genus']), ENT_QUOTES);
            }
            if (!empty($speciesData['family'])) {
                $insertData['family'] = htmlentities(strip_tags($speciesData['family']), ENT_QUOTES);
            }
            if (!empty($speciesData['taxon_order'])) {
                $insertData['taxon_order'] = htmlentities(strip_tags($speciesData['taxon_order']), ENT_QUOTES);
            }
            if (!empty($speciesData['class'])) {
                $insertData['class'] = htmlentities(strip_tags($speciesData['class']), ENT_QUOTES);
            }
            
            $species->insert($insertData);
            $inserted++;
        }

        return json_encode([
            'error_code' => 0,
            'message' => "Successfully uploaded {$inserted} species.",
        ]);
    }

    /**
     * Export all species to CSV
     * @return void
     * @throws \Exception
     */
    public function export()
    {
        if (!Auth::isUserAdmin()) {
            throw new ForbiddenException();
        }

        $species = new Species();
        $allSpecies = $species->getAll();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=species_export.csv');
        
        $fp = fopen('php://output', 'w');
        
        // Write headers
        fputcsv($fp, ['species_id', 'binomial', 'common_name', 'genus', 'family', 'taxon_order', 'class', 'level', 'source']);
        
        // Write data
        foreach ($allSpecies as $sp) {
            fputcsv($fp, [
                $sp['species_id'] ?? '',
                $sp['binomial'] ?? '',
                $sp['common_name'] ?? '',
                $sp['genus'] ?? '',
                $sp['family'] ?? '',
                $sp['taxon_order'] ?? '',
                $sp['class'] ?? '',
                $sp['level'] ?? '',
                $sp['source'] ?? ''
            ]);
        }
        
        fclose($fp);
        exit();
    }
}
