<?php

namespace BioSounds\Controller\Administration;

use BioSounds\Controller\BaseController;
use BioSounds\Entity\License;
use BioSounds\Entity\Recording;
use BioSounds\Entity\Sensor;
use BioSounds\Exception\ForbiddenException;
use BioSounds\Provider\CollectionProvider;
use BioSounds\Provider\IndexLogProvider;
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
     * @param int $page
     * @return mixed
     * @throws \Exception
     */
    public function show(int $cId = null)
    {
        if (!Auth::isUserAdmin()) {
            throw new ForbiddenException();
        }

        // colId proceesing
        if (isset($_POST['colId'])) {
            $colId = $_POST['colId'];
        }
        if (!empty($cId)) {
            $colId = $cId;
        }

        $collections = (new CollectionProvider())->getList();
        if (empty($colId)) {
            $colId = $collections[0]->getId();
        }

        $recordingProvider = new RecordingProvider();

        $recordings = $recordingProvider->getListByCollection(
            $colId,
            (Auth::getUserID() == null) ? 0 : Auth::getUserID()
        );

        $userSites = (new SiteProvider())->getBasicList();

        return $this->twig->render('administration/recordings.html.twig', [
            'colId' => $colId,
            'recordings' => $recordings,
            'sites' => $userSites,
            'sensors' => (new Sensor())->getBasicList(),
            'license' => (new License())->getBasicList(),
        ]);
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
        if (!Auth::isUserAdmin()) {
            throw new ForbiddenException();
        }

        if (empty($id)) {
            throw new \Exception(ERROR_EMPTY_ID);
        }

        $recordingProvider = new RecordingProvider();
        $indexLogProvider = new indexLogProvider();
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
}
