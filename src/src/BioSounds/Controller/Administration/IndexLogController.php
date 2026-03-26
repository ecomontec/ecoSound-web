<?php

namespace BioSounds\Controller\Administration;

use BioSounds\Controller\BaseController;
use BioSounds\Exception\ForbiddenException;
use BioSounds\Provider\IndexLogProvider;
use BioSounds\Provider\ProjectProvider;
use BioSounds\Provider\CollectionProvider;
use BioSounds\Provider\RecordingProvider;
use BioSounds\Utils\Auth;

class IndexLogController extends BaseController
{
    const SECTION_TITLE = 'IndexLogs';

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
            if (!empty($colId)) {
                $recordings = (new RecordingProvider())->getRecording($colId);
            }
            if (empty($recordingId)) {
                $recordingId = 0;
            }
        }
        
        return $this->twig->render('administration/indexLogs.html.twig', [
            'projectId' => $projectId,
            'projects' => $projects,
            'colId' => $colId,
            'collections' => $collections,
            'recordingId' => $recordingId,
            'recordings' => $recordings,
        ]);
    }

    public function getListByPage()
    {
        $total = count((new IndexLogProvider())->getIndexLog());
        $start = $_POST['start'];
        $length = $_POST['length'];
        $search = $_POST['search']['value'];
        $column = $_POST['order'][0]['column'];
        $dir = $_POST['order'][0]['dir'];
        $data = (new IndexLogProvider())->getListByPage($start, $length, $search, $column, $dir);
        if (count($data) == 0) {
            $data = [];
        }
        $result = [
            'draw' => $_POST['draw'],
            'recordsTotal' => $total,
            'recordsFiltered' => (new IndexLogProvider())->getFilterCount($search),
            'data' => $data,
        ];
        return json_encode($result);
    }

    /**
     * @throws \Exception
     */
    public function export()
    {
        if (!Auth::isUserLogged()) {
            throw new ForbiddenException();
        }
        $colArr = [];
        $file_name = "indexLogs.csv";
        $fp = fopen('php://output', 'w');
        header('Content-Type: application/octet-stream;charset=utf-8');
        header('Accept-Ranges:bytes');
        header('Content-Disposition: attachment; filename=' . $file_name);
        $columns = (new IndexLogProvider())->getColumns();
        foreach ($columns as $column) {
            $colArr[] = $column['COLUMN_NAME'];
        }
        array_splice($colArr, 2, 0, 'recording');
        array_splice($colArr, 4, 0, 'user');
        array_splice($colArr, 6, 0, 'index');

        $Als[] = $colArr;
        $List = (new IndexLogProvider())->getIndexLog();

        foreach ($List as $Item) {
            $valueToMove = $Item['recordingName'] == null ? '' : $Item['recordingName'];
            unset($Item['recordingName']);
            array_splice($Item, 2, 0, $valueToMove);
            $valueToMove = $Item['userName'] == null ? '' : $Item['userName'];
            unset($Item['userName']);
            array_splice($Item, 4, 0, $valueToMove);
            $valueToMove = $Item['indexName'] == null ? '' : $Item['indexName'];
            unset($Item['indexName']);
            array_splice($Item, 6, 0, $valueToMove);

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

        $indexLogProvider = new IndexLogProvider();
        $indexLogProvider->delete($id);

        return json_encode([
            'errorCode' => 0,
            'message' => 'Index log deleted successfully.',
        ]);
    }
}
