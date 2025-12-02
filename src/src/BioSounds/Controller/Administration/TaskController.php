<?php

namespace BioSounds\Controller\Administration;

use BioSounds\Controller\BaseController;
use BioSounds\Entity\Task;
use BioSounds\Entity\User;
use BioSounds\Exception\ForbiddenException;
use BioSounds\Provider\TaskProvider;
use BioSounds\Utils\Auth;


class TaskController extends BaseController
{
    const SECTION_TITLE = 'Tasks';

    public function show()
    {
        if (!Auth::isUserLogged()) {
            throw new ForbiddenException();
        }
        return $this->twig->render('administration/tasks.html.twig');
    }

    public function getListByPage()
    {
        $total = count((new TaskProvider())->getTask());
        $start = $_POST['start'];
        $length = $_POST['length'];
        $search = $_POST['search']['value'];
        $column = $_POST['order'][0]['column'];
        $dir = $_POST['order'][0]['dir'];
        $data = (new TaskProvider())->getListByPage($start, $length, $search, $column, $dir);
        if (count($data) == 0) {
            $data = [];
        }
        $result = [
            'draw' => $_POST['draw'],
            'recordsTotal' => $total,
            'recordsFiltered' => (new TaskProvider())->getFilterCount($search),
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
        $data = [];
        $jsons = json_decode($_POST['data']);
        $type = $_POST['type'];
        $assigned_ids = explode(',', $_POST['id']);
        $assigner_id = Auth::getUserLoggedID();
        $datetime = date('Y-m-d H:i:s');
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
        return json_encode([
            'errorCode' => 0,
            'message' => "successfully changed $type assignment",
        ]);
    }

    public function export()
    {
        if (!Auth::isUserLogged()) {
            throw new ForbiddenException();
        }
        $colArr = [];
        $file_name = "Tasks.csv";
        $fp = fopen('php://output', 'w');
        header('Content-Type: application/octet-stream;charset=utf-8');
        header('Accept-Ranges:bytes');
        header('Content-Disposition: attachment; filename=' . $file_name);
        $columns = (new taskProvider())->getColumns();
        foreach ($columns as $column) {
            $colArr[] = $column['COLUMN_NAME'];
        }

        array_splice($colArr, 2, 0, 'recording');
        array_splice($colArr, 5, 0, 'assigner');
        array_splice($colArr, 7, 0, 'assignee');
        $Als[] = $colArr;
        $List = (new taskProvider())->getTask();

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
