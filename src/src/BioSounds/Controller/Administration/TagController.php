<?php

namespace BioSounds\Controller\Administration;

use BioSounds\Controller\BaseController;
use BioSounds\Entity\Explore;
use BioSounds\Entity\SoundType;
use BioSounds\Entity\Tag;
use BioSounds\Exception\ForbiddenException;
use BioSounds\Provider\CollectionProvider;
use BioSounds\Provider\SoundProvider;
use BioSounds\Provider\SoundTypeProvider;
use BioSounds\Provider\TagProvider;
use BioSounds\Utils\Auth;


class TagController extends BaseController
{
    const SECTION_TITLE = 'Tags';

    /**
     * @return string
     * @throws \Exception
     */
    public function show(int $cId = null)
    {
        if (!Auth::isUserLogged()) {
            throw new ForbiddenException();
        }
        if (isset($_GET['colId'])) {
            $colId = $_GET['colId'];
        }
        if (!empty($cId)) {
            $colId = $cId;
        }
        $collections = Auth::isUserAdmin() ? (new CollectionProvider())->getList() : (new CollectionProvider())->getPublicList(Auth::getUserID());
        if (empty($colId)) {
            $colId = $collections[0]->getId();
        }
        $arr = [];
        $animal_sound_types = (new SoundTypeProvider())->getAllList();
        foreach ($animal_sound_types as $animal_sound_type) {
            $arr[$animal_sound_type->getTaxonClass() . $animal_sound_type->getTaxonOrder()][$animal_sound_type->getSoundTypeId()] = [$animal_sound_type->getSoundTypeId(), $animal_sound_type->getName()];
        }

        return $this->twig->render('administration/tags.html.twig', [
            'collections' => $collections,
            'colId' => $colId,
            'tags' => (new TagProvider())->getTagPagesByCollection($colId),
            'animal_sound_types' => $arr,
            'soundTypes' => (new SoundProvider())->getAll(),
            'phonys' => (new SoundProvider())->get(),
        ]);
    }

    /**
     * @throws \Exception
     */
    public function export(int $collection_id)
    {
        if (!Auth::isUserLogged()) {
            throw new ForbiddenException();
        }

        $file_name = "tags.csv";
        $fp = fopen('php://output', 'w');
        header('Content-Type: application/octet-stream;charset=utf-8');
        header('Accept-Ranges:bytes');
        header('Content-Disposition: attachment; filename=' . $file_name);

        $tagList = (new TagProvider())->getTagPagesByCollection($collection_id);
        $tagAls[] = array('#', 'Species', 'Recording', 'User', 'Time Start', 'Time End', 'Min Frequency', 'Max Frequency', 'Uncertain', 'Call Distance', 'Distance Not Estimable', 'Individuals', 'Type', 'Reference Call', 'Comments', 'Creation Date(UTC)');
        foreach ($tagList as $tagItem) {
            $tagArray = array(
                $tagItem->getId(),
                $tagItem->getSpeciesName(),
                $tagItem->getRecordingName(),
                $tagItem->getUserName(),
                $tagItem->getMinTime(),
                $tagItem->getMaxTime(),
                $tagItem->getMinFrequency(),
                $tagItem->getMaxFrequency(),
                $tagItem->isUncertain(),
                $tagItem->getCallDistance(),
                $tagItem->isDistanceNotEstimable(),
                $tagItem->getNumberIndividuals(),
                $tagItem->getType(),
                $tagItem->isReferenceCall(),
                $tagItem->getComments(),
                $tagItem->getCreationDate(),
            );
            $tagAls[] = $tagArray;
        }

        foreach ($tagAls as $line) {
            fputcsv($fp, $line);
        }
        fclose($fp);
        exit();
    }

    /**
     * @return false|string
     * @throws \Exception
     */
    public function save()
    {
        if (!Auth::isUserLogged()) {
            throw new ForbiddenException();
        }
        $tagProvider = new TagProvider();
        $data = [];

        foreach ($_POST as $key => $value) {
            if ($key != "_text" && $key != "_hidden") {
                if (strrpos($key, '_')) {
                    $key = substr($key, 0, strrpos($key, '_'));
                }
                $data[$key] = $value;
                if ($key === Tag::CALL_DISTANCE && empty($value)) {
                    $data[$key] = null;
                }
            }
        }
        unset($data['_search']);
        if ($data['species_id'] == '') {
            $data['species_id'] = null;
        }
        if ($data['phony'] != "biophony") {
            $data['species_id'] = null;
            $data['uncertain'] = null;
            $data['animal_sound_type'] = null;
            $data['distance_not_estimable'] = null;
            $data['sound_distance_m'] = null;
        }
        unset($data['phony']);
        $tagProvider->update($data);
        return json_encode([
            'errorCode' => 0,
            'message' => 'Tag updated successfully.'
        ]);

    }
}
