<?php

namespace BioSounds\Controller\Administration;

use BioSounds\Controller\BaseController;
use BioSounds\Entity\Task;
use BioSounds\Entity\User;
use BioSounds\Exception\ForbiddenException;
use BioSounds\Provider\CollectionProvider;
use BioSounds\Provider\ProjectProvider;
use BioSounds\Provider\RecordingProvider;
use BioSounds\Provider\TaskProvider;
use BioSounds\Provider\ProjectProvider;
use BioSounds\Provider\CollectionProvider;
use BioSounds\Provider\RecordingProvider;
use BioSounds\Utils\Auth;


class TaskController extends BaseController
{
    const SECTION_TITLE = 'Tasks';

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
            if (!empty($colId)) {
                $recordings = (new RecordingProvider())->getRecording($colId);
            }
            if (empty($recordingId)) {
                $recordingId = 0;
            }
        }
        
        return $this->twig->render('administration/tasks.html.twig', [
            'projectId' => $projectId,
            'projects' => $projects,
            'colId' => $colId,
            'collections' => $collections,
            'recordingId' => $recordingId,
            'recordings' => $recordings,
        ]);
    }

    public function getListByPage($collectionId = null, $recordingId = null)
    {
        if ($collectionId == null) {
            $collectionId = 0;
        }
        if ($recordingId == null) {
            $recordingId = 0;
        }

        $taskProvider = new TaskProvider();

        $start = $_POST['start'] ?? 0;
        $length = $_POST['length'] ?? 10;
        $search = $_POST['search']['value'] ?? '';
        $column = $_POST['order'][0]['column'] ?? 0;
        $dir = $_POST['order'][0]['dir'] ?? 'asc';

        $totalInScope = $taskProvider->getFilterCount(null, $collectionId, $recordingId);

        $data = $taskProvider->getListByPage($start, $length, $search, $column, $dir, $collectionId, $recordingId);

        if (count($data) == 0) {
            $data = [];
        }

        $result = [
            'draw' => $_POST['draw'] ?? 0,
            'recordsTotal' => $totalInScope,
            'recordsFiltered' => $taskProvider->getFilterCount($search, $collectionId, $recordingId),
            'data' => $data,
        ];
        return json_encode($result);
    }

    public function assign()
    {
        $assigned_id = $_POST['id'];
        $count = count(explode(',', $assigned_id));
        $collection_id = $_POST['collection_id'];
        $type = $_POST['type'];
        $listUsers = (new User())->getTaskUser($assigned_id, $collection_id, $type);
        return json_encode([
            'errorCode' => 0,
            'data' => $this->twig->render('administration/taskCreate.html.twig', [
                'users' => $listUsers,
                'type' => $type,
                'count' => $count,
            ]),
        ]);
    }

    public function save(): string
    {
        $task = new Task();
        $user = new User();
        $data = [];
        $jsons = json_decode($_POST['data']);
        $type = $_POST['type'];
        $assigned_ids = explode(',', $_POST['id']);
        $assigner_id = Auth::getUserLoggedID();
        $datetime = date('Y-m-d H:i:s');
        
        // Collect user names for the success message
        $userNames = [];
        foreach ($jsons as $json) {
            $userName = $user->getFullName($json->user_id);
            if ($userName) {
                $userNames[] = $userName;
            }
        }
        
        foreach ($jsons as $json) {
            $data = [];
            foreach ($assigned_ids as $assigned_id) {
                $item = [
                    'assigner_id' => $assigner_id,
                    'assignee_id' => $json->user_id,
                    'datetime' => $datetime,
                    'type' => $type,
                    'comment' => $json->comment,
                    'recording_id' => null,
                    'tag_id' => null,
                ];
                if ($type === 'tag') {
                    $item['tag_id'] = $assigned_id;
                } elseif ($type === 'recording') {
                    $item['recording_id'] = $assigned_id;
                }
                $data[] = $item;
            }
        }
        $task->insert($data);
        
        // Build informative success message
        $count = count($assigned_ids);
        $itemType = $type === 'recording' ? ($count === 1 ? 'recording' : 'recordings') : ($count === 1 ? 'tag' : 'tags');
        $userList = implode(', ', $userNames);
        $message = "Successfully assigned $count $itemType to $userList";
        
        return json_encode([
            'errorCode' => 0,
            'message' => $message,
        ]);
    }

    public function export($collectionId = null, $recordingId = null)
    {
        if (!Auth::isUserLogged()) {
            throw new ForbiddenException();
        }

        if ($collectionId == null) {
            $collectionId = 0;
        }
        if ($recordingId == null) {
            $recordingId = 0;
        }

        $colArr = [];
        $file_name = "Tasks.csv";
        $fp = fopen('php://output', 'w');
        header('Content-Type: application/octet-stream;charset=utf-8');
        header('Accept-Ranges:bytes');
        header('Content-Disposition: attachment; filename=' . $file_name);

        $taskProvider = new TaskProvider();
        $columns = $taskProvider->getColumns();
        foreach ($columns as $column) {
            $colArr[] = $column['COLUMN_NAME'];
        }

        array_splice($colArr, 2, 0, 'recording');
        array_splice($colArr, 5, 0, 'assigner');
        array_splice($colArr, 7, 0, 'assignee');
        $Als[] = $colArr;

        $List = $taskProvider->getExportList($collectionId, $recordingId);

        foreach ($List as $Item) {
            $valueToMove = $Item['recording'] == null ? '' : $Item['recording'];
            unset($Item['recording']);
            array_splice($Item, 2, 0, $valueToMove);
            $valueToMove = $Item['assigner'] == null ? '' : $Item['assigner'];
            unset($Item['assigner']);
            array_splice($Item, 5, 0, $valueToMove);
            $valueToMove = $Item['assignee'] == null ? '' : $Item['assignee'];
            unset($Item['assignee']);
            array_splice($Item, 7, 0, $valueToMove);

            $Als[] = $Item;
        }
        foreach ($Als as $line) {
            fputcsv($fp, $line);
        }
        fclose($fp);
        exit();
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

        $taskProvider = new TaskProvider();
        $taskProvider->delete($id);

        return json_encode([
            'errorCode' => 0,
            'message' => 'Task deleted successfully.',
        ]);
    }
}