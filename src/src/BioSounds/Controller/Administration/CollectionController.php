<?php

namespace BioSounds\Controller\Administration;

use BioSounds\Controller\BaseController;
use BioSounds\Controller\UserPermissionController;
use BioSounds\Entity\Collection;
use BioSounds\Entity\Recording;
use BioSounds\Entity\SiteCollection;
use BioSounds\Entity\User;
use BioSounds\Entity\UserPermission;
use BioSounds\Exception\ForbiddenException;
use BioSounds\Provider\CollectionProvider;
use BioSounds\Provider\IndexLogProvider;
use BioSounds\Provider\LabelAssociationProvider;
use BioSounds\Provider\ProjectProvider;
use BioSounds\Provider\RecordingProvider;
use BioSounds\Provider\SoundProvider;
use BioSounds\Provider\SpectrogramProvider;
use BioSounds\Utils\Auth;

class CollectionController extends BaseController
{
    const SECTION_TITLE = 'Collections';

    /**
     * @return string
     * @throws \Exception
     */
    public function show($projectId = null)
    {
        if (!Auth::isManage()) {
            throw new ForbiddenException();
        }
        if (isset($_GET['projectId'])) {
            $projectId = $_GET['projectId'];
        }

        $projects = (new ProjectProvider())->getWithPermission(Auth::getUserID());
        if (empty($projectId)) {
            $projectId = $projects[0]->getId();
        }
        return $this->twig->render('administration/collections.html.twig', [
            'projects' => $projects,
            'projectId' => $projectId,
            'isProjectManage' => (new User())->isProjectManageByProject(Auth::getUserLoggedID(), $projectId)
        ]);
    }

    public function getListByPage($projectId = null)
    {
        $total = count((new CollectionProvider())->getCollection($projectId));
        $start = $_POST['start'];
        $length = $_POST['length'];
        $search = $_POST['search']['value'];
        $column = $_POST['order'][0]['column'];
        $dir = $_POST['order'][0]['dir'];
        $data = (new CollectionProvider())->getListByPage($projectId, $start, $length, $search, $column, $dir);
        if (count($data) == 0) {
            $data = [];
        }
        $result = [
            'draw' => $_POST['draw'],
            'recordsTotal' => $total,
            'recordsFiltered' => (new CollectionProvider())->getFilterCount($projectId, $search),
            'data' => $data,
        ];
        return json_encode($result);
    }


    /**
     * @return false|string
     * @throws \Exception
     */
    public function save()
    {
        if (!Auth::isManage()) {
            throw new ForbiddenException();
        }
        $collProvider = new Collection();
        $data = [];

        foreach ($_POST as $key => $value) {
            if (strrpos($key, '_')) {
                $key = substr($key, 0, strrpos($key, '_'));
            }
            $data[$key] = $value;
        }
        if ($collProvider->isValid($data['project_id'], $data['name'], $data['collId'])) {
            return json_encode([
                'isValid' => 1,
                'message' => 'Collection name already exists.',
            ]);
        }
        $data['user_id'] = $_SESSION['user_id'];
        if (isset($data['collId'])) {
            $collProvider->updateColl($data);
            return json_encode([
                'errorCode' => 0,
                'message' => 'Collection updated successfully.'
            ]);
        } else {
            $id = $collProvider->insertColl($data);
            (new UserPermission())->updatePermission($id, $data['project_id']);
            (new SiteCollection())->insertByCollection($data['project_id'], $id);
            return json_encode([
                'errorCode' => 0,
                'message' => 'Collection created successfully.',
            ]);
        }
    }

    /**
     * @throws \Exception
     */
    public function export($project_id)
    {
        if (!Auth::isManage()) {
            throw new ForbiddenException();
        }
        $colArr = [];
        $file_name = "collections.csv";
        $fp = fopen('php://output', 'w');
        header('Content-Type: application/octet-stream;charset=utf-8');
        header('Accept-Ranges:bytes');
        header('Content-Disposition: attachment; filename=' . $file_name);
        $columns = (new CollectionProvider())->getColumns();
        foreach ($columns as $column) {
            $colArr[] = $column['COLUMN_NAME'];
        }
        array_splice($colArr, 4, 0, 'user');
        $Als[] = $colArr;

        $List = (new CollectionProvider())->getCollection($project_id);
        foreach ($List as $Item) {
            $valueToMove = $Item['username'] == null ? '' : $Item['username'];
            unset($Item['username']);
            array_splice($Item, 4, 0, $valueToMove);
            $Als[] = $Item;
        }
        foreach ($Als as $line) {
            fputcsv($fp, $line);
        }
        fclose($fp);
        exit();
    }

    /**
     * @param int $id
     * @return false|string
     * @throws \Exception
     */
    public function delete(int $id)
    {
        if (!Auth::isManage()) {
            throw new ForbiddenException();
        }

        if (empty($id)) {
            throw new \Exception(ERROR_EMPTY_ID);
        }
        $collectionProvider = new CollectionProvider();
        $recordingProvider = new RecordingProvider();
        $indexLogProvider = new indexLogProvider();
        $userProvider = new UserPermission();
        $labelAssociationProvider = new LabelAssociationProvider();

        $recordings = $recordingProvider->getByCollection($id);
        $userProvider->deleteByCollection($id);
        if (count($recordings) > 0) {
            foreach ($recordings as $recording) {
                $fileName = $recording[Recording::FILENAME];
                $colId = $recording[Recording::COL_ID];
                $dirID = $recording[Recording::DIRECTORY];

                $soundsDir = "sounds/sounds/$colId/$dirID/";
                $imagesDir = "sounds/images/$colId/$dirID/";

                if (is_file($soundsDir . $fileName)) {
                    unlink($soundsDir . $fileName);
                }

                //Check if there are images
                $images = (new SpectrogramProvider())->getListInRecording($recording[Recording::ID]);

                foreach ($images as $image) {
                    if (is_file($imagesDir . $image->getFilename())) {
                        unlink($imagesDir . $image->getFilename());
                    }
                }

                $wavFileName = substr($fileName, 0, strrpos($fileName, '.')) . '.wav';
                if (is_file($soundsDir . $wavFileName)) {
                    unlink($soundsDir . $wavFileName);
                }

                $labelAssociationProvider->delete($recording[Recording::ID]);
                $recordingProvider->delete($recording[Recording::ID]);
                $indexLogProvider->deleteByRecording($recording[Recording::ID]);
            }
        }
        $collectionProvider->delete($id);

        return json_encode([
            'errorCode' => 0,
            'message' => 'Collection deleted successfully.',
        ]);
    }

    public function count($id)
    {
        $count = count((new RecordingProvider())->getListByCollection($id, Auth::getUserID()));
        return $count;
    }
}
