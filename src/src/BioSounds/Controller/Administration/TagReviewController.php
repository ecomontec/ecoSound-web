<?php

namespace BioSounds\Controller\Administration;

use BioSounds\Controller\BaseController;
use BioSounds\Entity\IucnGet;
use BioSounds\Entity\SoundType;
use BioSounds\Entity\Tag;
use BioSounds\Exception\ForbiddenException;
use BioSounds\Provider\CollectionProvider;
use BioSounds\Provider\ProjectProvider;
use BioSounds\Provider\RecordingProvider;
use BioSounds\Provider\SoundProvider;
use BioSounds\Provider\SoundTypeProvider;
use BioSounds\Provider\TagProvider;
use BioSounds\Provider\TagReviewProvider;
use BioSounds\Utils\Auth;


class TagReviewController extends BaseController
{
    const SECTION_TITLE = 'Reviews';

    /**
     * @return string
     * @throws \Exception
     */
    public function show(int $pId = null, int $cId = null, int $rId = null)
    {
        if (!Auth::isUserLogged()) {
            throw new ForbiddenException();
        }
        
        // Initialize variables
        $projectId = null;
        $colId = null;
        $recordingId = null;
        $collections = [];
        $recordings = [];
        
        if (isset($_GET['projectId'])) {
            $projectId = $_GET['projectId'];
        }
        if (isset($_GET['recordingId'])) {
            $rId = $_GET['recordingId'];
        }
        if (isset($_GET['colId'])) {
            $colId = $_GET['colId'];
        }
        if (!empty($pId)) {
            $projectId = $pId;
        }
        if (!empty($cId)) {
            $colId = $cId;
        }
        if (!empty($rId)) {
            $recordingId = $rId;
        }

        $projects = (new ProjectProvider())->getWithPermission(Auth::getUserID(), 0);
        if (empty($projects)) {
            $projectId = null;
            $colId = null;
            $recordingId = null;
        } else {
            if (empty($projectId)) {
                $projectId = $projects[0]->getId();
            }
            $collections = (new CollectionProvider())->getByProject($projectId, Auth::getUserID());
            if (empty($colId) && !empty($collections)) {
                $colId = $collections[0]->getId();
            }
            // If still no colId, set to 0 to prevent template errors
            if (empty($colId)) {
                $colId = 0;
            }
            if (!empty($colId) && $colId > 0) {
                $recordings = (new RecordingProvider())->getHasTags($colId);
            }
            if (empty($recordingId)) {
                $recordingId = 0;
            }
        }
        $arr = [];
        $animal_sound_types = (new SoundTypeProvider())->getAllList();
        foreach ($animal_sound_types as $animal_sound_type) {
            $arr[$animal_sound_type->getTaxonClass() . $animal_sound_type->getTaxonOrder()][$animal_sound_type->getSoundTypeId()] = [$animal_sound_type->getSoundTypeId(), $animal_sound_type->getName()];
        }

        return $this->twig->render('administration/tagReviews.html.twig', [
            'projectId' => $projectId,
            'projects' => $projects,
            'colId' => $colId,
            'collections' => $collections,
            'recordingId' => $recordingId,
            'recordings' => $recordings,
            'animal_sound_types' => $arr,
        ]);
    }

    public function getListByPage($collectionId, $recordingId)
    {
        // Debug logging
        error_log("TagReview getListByPage called with collectionId: " . var_export($collectionId, true) . ", recordingId: " . var_export($recordingId, true));
        
        // Ensure we have valid parameters (0 is valid for recordingId = show all)
        // Convert to int for proper comparison
        $collectionId = intval($collectionId);
        $recordingId = intval($recordingId);
        
        if ($collectionId <= 0) {
            // No valid collection selected - return empty result
            error_log("TagReview: Invalid collection ID: " . $collectionId);
            $result = [
                'draw' => isset($_POST['draw']) ? $_POST['draw'] : 1,
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ];
            return json_encode($result);
        }
        
        // recordingId can be 0 (meaning show all recordings in collection)
        $reviews = (new TagReviewProvider())->getReview($collectionId, $recordingId);
        error_log("TagReview: Found " . count($reviews) . " total reviews for collection " . $collectionId);
        $total = count($reviews);
        $start = $_POST['start'];
        $length = $_POST['length'];
        $search = $_POST['search']['value'];
        $column = $_POST['order'][0]['column'];
        $dir = $_POST['order'][0]['dir'];
        $data = (new TagReviewProvider())->getListByPage($collectionId, $recordingId, $start, $length, $search, $column, $dir);
        if (count($data) == 0) {
            $data = [];
        }
        $result = [
            'draw' => $_POST['draw'],
            'recordsTotal' => $total,
            'recordsFiltered' => (new TagReviewProvider())->getFilterCount($collectionId, $recordingId, $search),
            'data' => $data,
        ];
        return json_encode($result);
    }

    /**
     * @throws \Exception
     */
    public function export($collection_id)
    {
        if (!Auth::isUserLogged()) {
            throw new ForbiddenException();
        }
        $colArr = [];
        $file_name = "reviews.csv";
        $fp = fopen('php://output', 'w');
        header('Content-Type: application/octet-stream;charset=utf-8');
        header('Accept-Ranges:bytes');
        header('Content-Disposition: attachment; filename=' . $file_name);
        $columns = (new TagReviewProvider())->getColumns();
        foreach ($columns as $column) {
            $colArr[] = $column['COLUMN_NAME'];
        }

        array_splice($colArr, 2, 0, 'reviewer');
        array_splice($colArr, 3, 0, 'recording_id');
        array_splice($colArr, 4, 0, 'recording');
        array_splice($colArr, 6, 0, 'status');
        array_splice($colArr, 8, 0, 'species');

        $Als[] = $colArr;
        $List = (new TagReviewProvider())->getReview($collection_id, '0');
        foreach ($List as $Item) {
            $username = $Item['username'] ?? '';
            $recording_id = $Item['recording_id'] ?? '';
            $recording = $Item['recording'] ?? '';
            $state = $Item['state'] ?? '';
            $specie = $Item['specie'] ?? '';

            unset($Item['username'], $Item['recording_id'], $Item['recording'], $Item['state'], $Item['specie']);

            $Item = array_values($Item);

            array_splice($Item, 2, 0, $username);
            array_splice($Item, 3, 0, $recording_id);
            array_splice($Item, 4, 0, $recording);
            array_splice($Item, 6, 0, $state);
            array_splice($Item, 8, 0, $specie);

            $Als[] = $Item;
        }
        foreach ($Als as $line) {
            fputcsv($fp, $line);
        }
        fclose($fp);
        exit();
    }

    public function downloadTemplate()
    {
        if (!Auth::isManage()) {
            throw new ForbiddenException();
        }
        
        $file_name = "reviews_template.csv";
        $fp = fopen('php://output', 'w');
        header('Content-Type: application/octet-stream;charset=utf-8');
        header('Accept-Ranges:bytes');
        header('Content-Disposition: attachment; filename=' . $file_name);
        
        fputcsv($fp, ['tag_id', 'tag_review_status_id', 'species_id', 'note']);
        fputcsv($fp, ['456', '1', '', '1']);
        
        fclose($fp);
        exit();
    }

    public function exportSpecies()
    {
        if (!Auth::isManage()) {
            throw new ForbiddenException();
        }
        
        $file_name = "species.csv";
        $fp = fopen('php://output', 'w');
        header('Content-Type: application/octet-stream;charset=utf-8');
        header('Accept-Ranges:bytes');
        header('Content-Disposition: attachment; filename=' . $file_name);
        
        $speciesProvider = new \BioSounds\Entity\Species();
        $species = $speciesProvider->get();
        
        if (!empty($species)) {
            fputcsv($fp, array_keys($species[0]));
            
            foreach ($species as $sp) {
                fputcsv($fp, $sp);
            }
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
        $tagProvider = new TagReviewProvider();
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
        if ($data['soundscape_component'] != "biophony") {
            $data['species_id'] = null;
            $data['uncertain'] = null;
            $data['animal_sound_type'] = null;
            $data['distance_not_estimable'] = null;
            $data['sound_distance_m'] = null;
        }
        unset($data['soundscape_component']);
        $tagProvider->update($data);
        return json_encode([
            'errorCode' => 0,
            'message' => 'Tag updated successfully.'
        ]);
    }
}
