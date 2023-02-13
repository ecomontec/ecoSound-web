<?php

namespace BioSounds\Controller\Administration;

use BioSounds\Controller\BaseController;
use BioSounds\Entity\License;
use BioSounds\Entity\Microphone;
use BioSounds\Entity\Recorder;
use BioSounds\Entity\Recording;
use BioSounds\Entity\Sensor;
use BioSounds\Entity\User;
use BioSounds\Exception\ForbiddenException;
use BioSounds\Provider\CollectionProvider;
use BioSounds\Provider\IndexLogProvider;
use BioSounds\Provider\LabelAssociationProvider;
use BioSounds\Provider\ProjectProvider;
use BioSounds\Provider\RecordingProvider;
use BioSounds\Provider\SpectrogramProvider;
use BioSounds\Provider\SiteProvider;
use BioSounds\Provider\SoundProvider;
use BioSounds\Provider\TagProvider;
use BioSounds\Utils\Auth;

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

        $recordingProvider = new RecordingProvider();
        $userProducer = new User();

        $recordings = $recordingProvider->getListByCollection(
            $colId,
            (Auth::getUserID() == null) ? 0 : Auth::getUserID()
        );
        $userSites = (new SiteProvider())->getList($projectId, $colId);
        return $this->twig->render('administration/recordings.html.twig', [
            'projectId' => $projectId,
            'projects' => $projects,
            'colId' => $colId,
            'collections' => $collections,
            'recordings' => $recordings,
            'sites' => $userSites,
            'users' => $userProducer->getList(),
            'recorders' => (new Recorder())->getBasicList(),
            'microphones' => (new Microphone())->getBasicList(),
            'license' => (new License())->getBasicList(),
        ]);
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
    public function delete(int $id)
    {
        if (!Auth::isManage()) {
            throw new ForbiddenException();
        }

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

        unlink($soundsDir . $fileName);
        //Check if there are images
        $images = (new SpectrogramProvider())->getListInRecording($id);

        foreach ($images as $image) {
            unlink($imagesDir . $image->getFilename());
        }

        $wavFileName = substr($fileName, 0, strrpos($fileName, '.')) . '.wav';
        if (is_file($soundsDir . $wavFileName)) {
            unlink($soundsDir . $wavFileName);
        }

        $labelAssociationProvider->delete($id);
        $recordingProvider->delete($id);
        $indexLogProvider->deleteByRecording($id);

        if (!empty($recording[Recording::SOUND_ID])) {
            (new SoundProvider())->delete($recording[Recording::SOUND_ID]);
        }

        return json_encode([
            'errorCode' => 0,
            'message' => 'Recording deleted successfully.',
        ]);
    }

    public function count($id)
    {
        $count = count((new tagProvider())->getList($id));
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
}
