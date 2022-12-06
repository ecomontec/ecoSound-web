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
        $sound_types = (new SoundTypeProvider())->getAllList();
        foreach ($sound_types as $sound_type) {
            $arr[$sound_type->getTaxonClass() . $sound_type->getTaxonOrder()][$sound_type->getSoundTypeId()] = [$sound_type->getSoundTypeId(), $sound_type->getName()];
        }

        return $this->twig->render('administration/tags.html.twig', [
            'collections' => $collections,
            'colId' => $colId,
            'tags' => (new TagProvider())->getTagPagesByCollection($colId),
            'sound_types' => $arr,
            'phonys'=>(new SoundProvider())->get(),
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
        $tagAls[] = array('#', 'Species', 'Recording', 'User', 'Time Start', 'Time End', 'Min Frequency', 'Max Frequency', 'Uncertain', 'Call Distance', 'Distance Not Estimable', 'Number of Individuals', 'Type', 'Reference Call', 'Comments', 'Creation Date(UTC)');
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
        if ($data['sound_id'] != 1) {
            unset($data['species_id']);
            unset($data['uncertain']);
            unset($data['sound_distance_m']);
            unset($data['distance_not_estimable']);
            unset($data['animal_sound_type']);
        }
        $tagProvider->update($data);
        return json_encode([
            'errorCode' => 0,
            'message' => 'Tag updated successfully.'
        ]);

    }
}
