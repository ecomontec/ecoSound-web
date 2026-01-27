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
        $data = [];
        foreach ($_POST as $key => $value) {
            if ($key === 'itemID') continue;
            // Sanitize numeric fields
            if (in_array($key, ['species_id', 'level'])) {
                $data[$key] = filter_var($value, FILTER_SANITIZE_NUMBER_INT);
            } else {
                $data[$key] = htmlentities(strip_tags(filter_var($value, FILTER_SANITIZE_STRING)), ENT_QUOTES);
            }
        }

        if (!empty($data['itemID'])) {
            $speciesId = $data['itemID'];
            unset($data['itemID']);
            $species->update($data, $speciesId);
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
