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
use BioSounds\Utils\Auth;


class TagController extends BaseController
{
    const SECTION_TITLE = 'Tags';

    /**
     * @return string
     * @throws \Exception
     */
    public function show(int $pId = null, int $cId = null, int $rId = null)
    {
        if (!Auth::isUserLogged()) {
            throw new ForbiddenException();
        }
        if (isset($_GET['projectId'])) {
            $projectId = $_GET['projectId'];
        }
        if (isset($_GET['colId'])) {
            $colId = $_GET['colId'];
        }
        if (isset($_GET['recordingId'])) {
            $rId = $_GET['recordingId'];
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
            if (empty($colId) && $collections && !empty($collections)) {
                $colId = $collections[0]->getId();
            }
            // If still no colId, set to 0 to prevent template errors
            if (empty($colId)) {
                $colId = 0;
            }
            $recordings = (new RecordingProvider())->getHasTags($colId);
            if (empty($recordingId)) {
                $recordingId = 0;
            }
        }
        $arr = [];
        $animal_sound_types = (new SoundTypeProvider())->getAllList();
        foreach ($animal_sound_types as $animal_sound_type) {
            $arr[$animal_sound_type->getTaxonClass() . $animal_sound_type->getTaxonOrder()][$animal_sound_type->getSoundTypeId()] = [$animal_sound_type->getSoundTypeId(), $animal_sound_type->getName()];
        }
        return $this->twig->render('administration/tags.html.twig', [
            'projectId' => $projectId,
            'projects' => $projects,
            'colId' => $colId,
            'collections' => $collections,
            'recordingId' => $recordingId,
            'recordings' => $recordings,
            'animal_sound_types' => $arr,
            'soundTypes' => (new SoundProvider())->getAll(),
        ]);
    }

    public function getListByPage($collectionId, $recordingId)
    {
        if ($collectionId == null) {
            $collectionId = 0;
        }
        if ($recordingId == null) {
            $recordingId = 0;
        }
        $total = count((new TagProvider())->getTag($collectionId, $recordingId));
        $start = $_POST['start'];
        $length = $_POST['length'];
        $search = $_POST['search']['value'];
        $column = $_POST['order'][0]['column'];
        $dir = $_POST['order'][0]['dir'];
        $data = (new TagProvider())->getListByPage($collectionId, $recordingId, $start, $length, $search, $column, $dir);
        if (count($data) == 0) {
            $data = [];
        }
        $result = [
            'draw' => $_POST['draw'],
            'recordsTotal' => $total,
            'recordsFiltered' => (new TagProvider())->getFilterCount($collectionId, $recordingId, $search),
            'data' => $data,
        ];
        return json_encode($result);
    }

    /**
     * @throws \Exception
     */
    public function export($collection_id, $recording_id)
    {
        if (!Auth::isUserLogged()) {
            throw new ForbiddenException();
        }
        $colArr = [];
        $file_name = "tags.csv";
        $fp = fopen('php://output', 'w');
        header('Content-Type: application/octet-stream;charset=utf-8');
        header('Accept-Ranges:bytes');
        header('Content-Disposition: attachment; filename=' . $file_name);
        $columns = (new TagProvider())->getColumns();
        foreach ($columns as $column) {
            $colArr[] = $column['COLUMN_NAME'];
        }

        array_splice($colArr, 2, 0, 'soundscape component');
        array_splice($colArr, 3, 0, 'sound type');
        array_splice($colArr, 5, 0, 'recording');
        array_splice($colArr, 7, 0, 'user');
        array_splice($colArr, 15, 0, 'species');
        array_splice($colArr, 21, 0, 'animal sound type');

        $Als[] = $colArr;
        $List = (new TagProvider())->getTag($collection_id, $recording_id);
        foreach ($List as $Item) {
            unset($Item['TaxonOrder']);
            unset($Item['TaxonClass']);

            $valueToMove = $Item['soundscape_component'] == null ? '' : $Item['soundscape_component'];
            unset($Item['soundscape_component']);
            array_splice($Item, 2, 0, $valueToMove);
            $valueToMove = $Item['sound_type'] == null ? '' : $Item['sound_type'];
            unset($Item['sound_type']);
            array_splice($Item, 3, 0, $valueToMove);
            $valueToMove = $Item['recordingName'] == null ? '' : $Item['recordingName'];
            unset($Item['recordingName']);
            array_splice($Item, 5, 0, $valueToMove);
            $valueToMove = $Item['userName'] == null ? '' : $Item['userName'];
            unset($Item['userName']);
            array_splice($Item, 7, 0, $valueToMove);
            $valueToMove = $Item['speciesName'] == null ? '' : $Item['speciesName'];
            unset($Item['speciesName']);
            array_splice($Item, 15, 0, $valueToMove);
            $valueToMove = $Item['typeName'] == null ? '' : $Item['typeName'];
            unset($Item['typeName']);
            array_splice($Item, 21, 0, $valueToMove);

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
        
        $file_name = "tags_template.csv";
        $fp = fopen('php://output', 'w');
        header('Content-Type: application/octet-stream;charset=utf-8');
        header('Accept-Ranges:bytes');
        header('Content-Disposition: attachment; filename=' . $file_name);
        
        fputcsv($fp, ['recording_id', 'min_time', 'max_time', 'min_freq', 'max_freq', 'sound_id', 'individuals', 'species_id', 'uncertain', 'sound_distance_m', 'distance_not_estimable', 'animal_sound_type', 'reference_call', 'comments', 'confidence']);
        fputcsv($fp, ['123', '5.5', '7.2', '1000', '8000', '6', '1', '678', '0', '50', '0', 'call', '0', 'Example tag', '0.95']);
        
        fclose($fp);
        exit();
    }

    public function exportSounds()
    {
        if (!Auth::isManage()) {
            throw new ForbiddenException();
        }
        
        $file_name = "sounds.csv";
        $fp = fopen('php://output', 'w');
        header('Content-Type: application/octet-stream;charset=utf-8');
        header('Accept-Ranges:bytes');
        header('Content-Disposition: attachment; filename=' . $file_name);
        
        $soundProvider = new SoundProvider();
        $sounds = $soundProvider->getAll();
        
        if (!empty($sounds)) {
            fputcsv($fp, array_keys($sounds[0]));
            
            foreach ($sounds as $sound) {
                fputcsv($fp, $sound);
            }
        }
        
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

    public function exportRecordings()
    {
        if (!Auth::isManage()) {
            throw new ForbiddenException();
        }
        
        $file_name = "recordings.csv";
        $fp = fopen('php://output', 'w');
        header('Content-Type: application/octet-stream;charset=utf-8');
        header('Accept-Ranges:bytes');
        header('Content-Disposition: attachment; filename=' . $file_name);
        
        // Query database directly to get array data
        $db = new \BioSounds\Database\Database(DRIVER, HOST, DATABASE, USER, PASSWORD);
        $query = 'SELECT recording_id, name, filename, col_id, directory, site_id, ';
        $query .= 'file_size, bitdepth, channel_num, DATE_FORMAT(file_date, \'%Y-%m-%d\') ';
        $query .= 'AS file_date, DATE_FORMAT(file_time, \'%H:%i:%s\') AS file_time, sampling_rate, ';
        $query .= 'duration, type, medium, recorder_id, microphone_id, recording_gain, ';
        $query .= 'duty_cycle_recording, duty_cycle_period, note, doi, license_id ';
        $query .= 'FROM recording';
        
        $db->prepareQuery($query);
        $recordings = $db->executeSelect();
        
        if (!empty($recordings)) {
            fputcsv($fp, array_keys($recordings[0]));
            
            foreach ($recordings as $recording) {
                fputcsv($fp, $recording);
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

    public function delete()
    {
        if (!Auth::isUserLogged()) {
            throw new ForbiddenException();
        }

        $id = $_POST['id'];

        if (empty($id)) {
            throw new \Exception(ERROR_EMPTY_ID);
        }

        (new TagProvider())->delete($id);

        return json_encode([
            'errorCode' => 0,
            'message' => 'Tag deleted successfully.',
        ]);
    }
}
