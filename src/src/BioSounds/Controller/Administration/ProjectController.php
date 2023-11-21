<?php

namespace BioSounds\Controller\Administration;

use BioSounds\Controller\BaseController;
use BioSounds\Exception\ForbiddenException;
use BioSounds\Provider\ProjectProvider;
use BioSounds\Utils\Auth;

class ProjectController extends BaseController
{
    const SECTION_TITLE = 'Projects';

    /**
     * @return mixed
     * @throws \Exception
     */
    public function show()
    {
        if (!Auth::isUserAdmin()) {
            throw new ForbiddenException();
        }
        return $this->twig->render('administration/projects.html.twig');
    }

    public function getListByPage()
    {
        if (!Auth::isUserAdmin()) {
            throw new ForbiddenException();
        }
        $total = count((new ProjectProvider())->getProject());
        $start = $_POST['start'];
        $length = $_POST['length'];
        $search = $_POST['search']['value'];
        $column = $_POST['order'][0]['column'];
        $dir = $_POST['order'][0]['dir'];
        $data = (new ProjectProvider())->getListByPage($start, $length, $search, $column, $dir);
        if (count($data) == 0) {
            $data = [];
        }
        $result = [
            'draw' => $_POST['draw'],
            'recordsTotal' => $total,
            'recordsFiltered' => (new ProjectProvider())->getFilterCount($search),
            'data' => $data,
        ];

        return json_encode($result);
    }

    /**
     * @return bool|int|null
     * @throws \Exception
     */
    public function save()
    {
        if (!Auth::isUserAdmin()) {
            throw new ForbiddenException();
        }

        if (!is_dir(ABSOLUTE_DIR . 'sounds/projects/')) {
            mkdir(ABSOLUTE_DIR . 'sounds/projects/');
            chmod(ABSOLUTE_DIR . 'sounds/projects/', 0777);
        }
        $projectProvider = new ProjectProvider();
        $data = [];
        foreach ($_POST as $key => $value) {
            if (strrpos($key, '_')) {
                $key = substr($key, 0, strrpos($key, '_'));
            }
            $data[$key] = $value;
        }
        if ($projectProvider->isValid($data['name'], $data['projectId'])) {
            return json_encode([
                'isValid' => 1,
                'message' => 'Project name already exists.',
            ]);
        }
        if (isset($data['projectId'])) {
            if ($_FILES["picture_id_file"]) {
                $data['picture_id'] = $data['projectId'] . '.' . explode('/', $_FILES["picture_id_file"]['type'])[1];
                move_uploaded_file($_FILES["picture_id_file"]['tmp_name'], ABSOLUTE_DIR . 'sounds/projects/' . $data['picture_id']);
            } else {
                unset($data['picture_id']);
            }
            $projectProvider->update($data);
            return json_encode([
                'errorCode' => 0,
                'message' => 'Project updated successfully.',
            ]);
        } else {
            $data['creator_id'] = Auth::getUserID();
            $data['picture_id'] = null;
            $insert = $projectProvider->insert($data);
            if ($insert > 0) {
                if ($_FILES["picture_id_file"]) {
                    $data['picture_id'] = $insert . '.' . explode('/', $_FILES["picture_id_file"]['type'])[1];
                    $data['projectId'] = $insert;
                    $projectProvider->update($data);
                    move_uploaded_file($_FILES["picture_id_file"]['tmp_name'], ABSOLUTE_DIR . 'sounds/projects/' . $data['picture_id']);
                } else {
                    unset($data['picture_id']);
                }
                return json_encode([
                    'errorCode' => 0,
                    'message' => 'Project created successfully.',
                ]);
            }
        }
    }

    public function description(int $project_id)
    {
        $project = (new ProjectProvider())->get($project_id);

        return json_encode([
            'errorCode' => 0,
            'data' => $this->twig->render('administration/projectEdit.html.twig', [
                'project' => $project,
            ]),
        ]);

    }

    public function export()
    {
        if (!Auth::isManage()) {
            throw new ForbiddenException();
        }
        $colArr = [];
        $file_name = "projects.csv";
        $fp = fopen('php://output', 'w');
        header('Content-Type: application/octet-stream;charset=utf-8');
        header('Accept-Ranges:bytes');
        header('Content-Disposition: attachment; filename=' . $file_name);
        $columns = (new ProjectProvider())->getColumns();
        foreach ($columns as $column) {
            $colArr[] = $column['COLUMN_NAME'];
        }
        array_splice($colArr, 3, 0, 'creator');
        $Als[] = $colArr;

        $List = (new ProjectProvider())->getProject();
        foreach ($List as $Item) {
            unset($Item['collection_id']);
            unset($Item['permission_id']);
            $Item['description'] = strip_tags($Item['description']);
            $Item['description_short'] = strip_tags($Item['description_short']);
            $valueToMove = $Item['username'] == null ? '' : $Item['username'];
            unset($Item['username']);
            array_splice($Item, 3, 0, $valueToMove);
            $Als[] = $Item;
        }
        foreach ($Als as $line) {
            fputcsv($fp, $line);
        }
        fclose($fp);
        exit();
    }
}
