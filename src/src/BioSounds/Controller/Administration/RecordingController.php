<?php

namespace BioSounds\Controller\Administration;

use BioSounds\Controller\BaseController;
use BioSounds\Entity\License;
use BioSounds\Entity\Microphone;
use BioSounds\Entity\Recorder;
use BioSounds\Entity\Recording;
use BioSounds\Exception\ForbiddenException;
use BioSounds\Provider\CollectionProvider;
use BioSounds\Provider\IndexLogProvider;
use BioSounds\Provider\LabelAssociationProvider;
use BioSounds\Provider\ProjectProvider;
use BioSounds\Provider\RecordingProvider;
use BioSounds\Provider\SpectrogramProvider;
use BioSounds\Provider\SiteProvider;
use BioSounds\Provider\TagProvider;
use BioSounds\Service\Queue\RabbitQueueService;
use BioSounds\Utils\Auth;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RecordingController extends BaseController
{
    const SECTION_TITLE = 'Recordings';

    /**
     * @param int|null $cId
     * @param int|null $pId
     * @param int $page
     * @return mixed
     * @throws \Exception
     */
    public function show(int $pId = null, int $cId = null)
    {
        if (!Auth::isManage()) {
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
        $projects = (new ProjectProvider())->getWithPermission(Auth::getUserID());
        if (empty($projectId)) {
            $projectId = $projects[0]->getId();
        }
        $collections = (new CollectionProvider())->getByProject($projectId, Auth::getUserID());
        if (empty($colId)) {
            $colId = $collections[0]->getId();
        }
        $userSites = (new SiteProvider())->getList($projectId, $colId);
        return $this->twig->render('administration/recordings.html.twig', [
            'projectId' => $projectId,
            'projects' => $projects,
            'colId' => $colId,
            'collections' => $collections,
            'sites' => $userSites,
            'recorders' => (new Recorder())->getBasicList(),
            'microphones' => (new Microphone())->getBasicList(),
            'license' => (new License())->getBasicList(),
            'models' => (new RecordingProvider())->getModel(),
        ]);
    }

    public function getListByPage($projectId = null, $collectionId = null)
    {
        $total = count((new RecordingProvider())->getRecording($collectionId));
        $start = $_POST['start'];
        $length = $_POST['length'];
        $search = $_POST['search']['value'];
        $column = $_POST['order'][0]['column'];
        $dir = $_POST['order'][0]['dir'];
        $data = (new RecordingProvider())->getListByPage($projectId, $collectionId, $start, $length, $search, $column, $dir);
        if (count($data) == 0) {
            $data = [];
        }
        $result = [
            'draw' => $_POST['draw'],
            'recordsTotal' => $total,
            'recordsFiltered' => (new RecordingProvider())->getFilterCount($collectionId, $search),
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
        if (!Auth::isManage()) {
            throw new ForbiddenException();
        }

        $data = [];

        foreach ($_POST as $key => $value) {
            if ($key != "_text" && $key != "_hidden") {
                if (strpos($key, "_")) {
                    $type = substr($key, strripos($key, "_") + 1, strlen($key));
                    $key = substr($key, 0, strripos($key, "_"));
                    switch ($type) {
                        case "date":
                            $data[$key] = $value;
                            break;
                        case "time":
                            $data[$key] = $value;
                            break;
                        case "text":
                            $data[$key] = $value;
                            break;
                        case 'select-one':
                            $data[$key] = $value;
                            break;
                        case "hidden":
                            $data[$key] = filter_var($value, FILTER_SANITIZE_NUMBER_INT);
                            break;
                    }
                } else
                    $data[$key] = $value;
            }
        }
        $data["site_id"] = $data["site_id"] == 0 ? null : $data["site_id"];
        $data["recorder_id"] = $data["recorder_id"] == 0 ? null : $data["recorder_id"];
        $data["microphone_id"] = $data["microphone_id"] == 0 ? null : $data["microphone_id"];
        $data["license_id"] = $data["license_id"] == 0 ? null : $data["license_id"];
        if (isset($data["itemID"])) {
            (new RecordingProvider())->update($data);

            return json_encode([
                'errorCode' => 0,
                'message' => 'Recording updated successfully.',
            ]);
        }
    }

    /**
     * @param int $id
     * @return false|string
     * @throws \Exception
     */
    public function delete()
    {
        if (!Auth::isManage()) {
            throw new ForbiddenException();
        }
        $id = $_POST['id'];
        if (empty($id)) {
            throw new \Exception(ERROR_EMPTY_ID);
        }

        $recordingProvider = new RecordingProvider();
        $indexLogProvider = new indexLogProvider();
        $labelAssociationProvider = new LabelAssociationProvider();
        $recording = $recordingProvider->get($id);

        $fileName = $recording[Recording::FILENAME];
        $colId = $recording[Recording::COL_ID];
        $dirID = $recording[Recording::DIRECTORY];

        $soundsDir = "sounds/sounds/$colId/$dirID/";
        $imagesDir = "sounds/images/$colId/$dirID/";

        if (file_exists($soundsDir . $fileName)) {
            unlink($soundsDir . $fileName);
        }

        //Check if there are images
        $images = (new SpectrogramProvider())->getListInRecording($id);

        foreach ($images as $image) {
            if (file_exists($imagesDir . $image->getFilename())) {
                unlink($imagesDir . $image->getFilename());
            }
        }

        $wavFileName = substr($fileName, 0, strrpos($fileName, '.')) . '.wav';
        if (is_file($soundsDir . $wavFileName)) {
            unlink($soundsDir . $wavFileName);
        }

        $labelAssociationProvider->delete($id);
        $recordingProvider->delete($id);
        $indexLogProvider->deleteByRecording($id);

        return json_encode([
            'errorCode' => 0,
            'message' => 'Recording deleted successfully.',
        ]);
    }

    public function count()
    {
        $count = count((new tagProvider())->getList($_POST['id']));
        return $count;
    }

    public function download()
    {
        $file_name = "meta-data demo.csv";
        $fp = fopen('php://output', 'w');
        header('Content-Type: application/octet-stream;charset=utf-8');
        header('Accept-Ranges:bytes');
        header('Content-Disposition: attachment; filename=' . $file_name);

        $tagAls[] = array('recording_start', 'duration_s', 'sampling_rate', 'name', 'bit_rate', 'channel_number');

        foreach ($tagAls as $line) {
            fputcsv($fp, $line);
        }
        fclose($fp);
        exit();
    }

    public function export($collection_id)
    {
        if (!Auth::isManage()) {
            throw new ForbiddenException();
        }
        $colArr = [];
        $file_name = "recordings.csv";
        $fp = fopen('php://output', 'w');
        header('Content-Type: application/octet-stream;charset=utf-8');
        header('Accept-Ranges:bytes');
        header('Content-Disposition: attachment; filename=' . $file_name);
        $columns = (new RecordingProvider())->getColumns();
        foreach ($columns as $column) {
            if ($column['COLUMN_NAME'] == 'md5_hash') {
                continue;
            }
            $colArr[] = $column['COLUMN_NAME'];
        }
        array_splice($colArr, 7, 0, 'user');
        array_splice($colArr, 9, 0, 'site');
        array_splice($colArr, 11, 0, 'recorder');
        array_splice($colArr, 13, 0, 'microphone');
        array_splice($colArr, 15, 0, 'license');
        $Als[] = $colArr;

        $List = (new RecordingProvider())->getRecording($collection_id);
        foreach ($List as $Item) {
            unset($Item['md5_hash']);
            $valueToMove = $Item['username'] == null ? '' : $Item['username'];
            unset($Item['username']);
            array_splice($Item, 7, 0, $valueToMove);
            $valueToMove = $Item['site'] == null ? '' : $Item['site'];
            unset($Item['site']);
            array_splice($Item, 9, 0, $valueToMove);
            $valueToMove = $Item['model'] == null ? '' : $Item['model'];
            unset($Item['model']);
            array_splice($Item, 11, 0, $valueToMove);
            $valueToMove = $Item['microphone'] == null ? '' : $Item['microphone'];
            unset($Item['microphone']);
            array_splice($Item, 13, 0, $valueToMove);
            $valueToMove = $Item['license'] == null ? '' : $Item['license'];
            unset($Item['license']);
            array_splice($Item, 15, 0, $valueToMove);

            $Als[] = $Item;
        }
        foreach ($Als as $line) {
            fputcsv($fp, $line);
        }
        fclose($fp);
        exit();
    }

    public function model()
    {
        if (!Auth::isUserLogged()) {
            throw new ForbiddenException();
        }
        $this->queueService = new RabbitQueueService();
        foreach ($_POST as $data) {
            $this->queueService->model($data);
        }
        $this->queueService->closeConnection();
        return json_encode([
            'errorCode' => 0,
            'message' => 'Models successfully.'
        ]);
    }
}
