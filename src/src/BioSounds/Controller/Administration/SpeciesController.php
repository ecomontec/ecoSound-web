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

        $speciesId = filter_var($_POST['id'], FILTER_SANITIZE_NUMBER_INT);
        
        $species = new Species();
        $species->delete($speciesId);

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
}
