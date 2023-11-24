<?php

namespace BioSounds\Controller\Administration;

use BioSounds\Controller\BaseController;
use BioSounds\Entity\IucnGet;
use BioSounds\Entity\SoundType;
use BioSounds\Entity\Tag;
use BioSounds\Exception\ForbiddenException;
use BioSounds\Provider\CollectionProvider;
use BioSounds\Provider\ProjectProvider;
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
    public function show(int $pId = null, int $cId = null)
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
        if (!empty($pId)) {
            $projectId = $pId;
        }
        if (!empty($cId)) {
            $colId = $cId;
        }

        $projects = (new ProjectProvider())->getWithPermission(Auth::getUserID(), 0);
        if (empty($projects)) {
            $projectId = null;
            $colId = null;
        } else {
            if (empty($projectId)) {
                $projectId = $projects[0]->getId();
            }
            $collections = (new CollectionProvider())->getByProject($projectId, Auth::getUserID());
            if (empty($colId)) {
                $colId = $collections[0]->getId();
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
            'animal_sound_types' => $arr,
            'soundTypes' => (new SoundProvider())->getAll(),
            'phonys' => (new SoundProvider())->get(),
        ]);
    }

    public function getListByPage($collectionId)
    {
        if ($collectionId == null) {
            $collectionId = 0;
        }
        $total = count((new TagProvider())->getTag($collectionId));
        $start = $_POST['start'];
        $length = $_POST['length'];
        $search = $_POST['search']['value'];
        $column = $_POST['order'][0]['column'];
        $dir = $_POST['order'][0]['dir'];
        $data = (new TagProvider())->getListByPage($collectionId, $start, $length, $search, $column, $dir);
        if (count($data) == 0) {
            $data = [];
        }
        $result = [
            'draw' => $_POST['draw'],
            'recordsTotal' => $total,
            'recordsFiltered' => (new TagProvider())->getFilterCount($collectionId, $search),
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
        $file_name = "tags.csv";
        $fp = fopen('php://output', 'w');
        header('Content-Type: application/octet-stream;charset=utf-8');
        header('Accept-Ranges:bytes');
        header('Content-Disposition: attachment; filename=' . $file_name);
        $columns = (new TagProvider())->getColumns();
        foreach ($columns as $column) {
            $colArr[] = $column['COLUMN_NAME'];
        }

        array_splice($colArr, 2, 0, 'phony');
        array_splice($colArr, 3, 0, 'sound_type');
        array_splice($colArr, 5, 0, 'recording');
        array_splice($colArr, 7, 0, 'user');
        array_splice($colArr, 15, 0, 'species');
        array_splice($colArr, 21, 0, 'animal sound type');

        $Als[] = $colArr;
        $List = (new TagProvider())->getTag($collection_id);
        foreach ($List as $Item) {
            unset($Item['TaxonOrder']);
            unset($Item['TaxonClass']);

            $valueToMove = $Item['phony'] == null ? '' : $Item['phony'];
            unset($Item['phony']);
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
