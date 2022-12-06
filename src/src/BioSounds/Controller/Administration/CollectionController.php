<?php

namespace BioSounds\Controller\Administration;

use BioSounds\Controller\BaseController;
use BioSounds\Controller\UserPermissionController;
use BioSounds\Entity\Collection;
use BioSounds\Entity\Recording;
use BioSounds\Entity\SiteCollection;
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
        $collProvider = new CollectionProvider();

        return $this->twig->render('administration/collections.html.twig', [
            'projects' => $projects,
            'projectId' => $projectId,
            'collections' => $collProvider->getByProject($projectId, Auth::getUserID()),
        ]);
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
        $data['user_id'] = $_SESSION['user_id'];
        if (isset($data['collId'])) {
            $collProvider->updateColl($data);
            return json_encode([
                'errorCode' => 0,
                'message' => 'Collection updated successfully.'
            ]);
        } else {
            $id = $collProvider->insertColl($data);
            (new UserPermission())->updatePermission($id);
            (new SiteCollection())->insertByCollection($data['project_id'],$id);
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

        $file_name = "collections.csv";
        $fp = fopen('php://output', 'w');
        header('Content-Type: application/octet-stream;charset=utf-8');
        header('Accept-Ranges:bytes');
        header('Content-Disposition: attachment; filename=' . $file_name);

        $collList = (new CollectionProvider())->getByProject($project_id, Auth::getUserID());
        $colAls[] = array('#', 'Name', 'User', 'DOI', 'Domain', 'Description', 'Creation Date(UTC)', 'View', 'Public');

        foreach ($collList as $collItem) {
            $colArray = array($collItem->getId(), $collItem->getName(), $collItem->getAuthor(), $collItem->getDoi(), $collItem->getDomain(), $collItem->getNote(), $collItem->getCreationDate(), $collItem->getView(), $collItem->getPublic());
            $colAls[] = $colArray;
        }

        foreach ($colAls as $line) {
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

                unlink($soundsDir . $fileName);
                //Check if there are images
                $images = (new SpectrogramProvider())->getListInRecording($recording[Recording::ID]);

                foreach ($images as $image) {
                    unlink($imagesDir . $image->getFilename());
                }

                $wavFileName = substr($fileName, 0, strrpos($fileName, '.')) . '.wav';
                if (is_file($soundsDir . $wavFileName)) {
                    unlink($soundsDir . $wavFileName);
                }

                $labelAssociationProvider->delete($recording[Recording::ID]);
                $recordingProvider->delete($recording[Recording::ID]);
                $indexLogProvider->deleteByRecording($recording[Recording::ID]);

                if (!empty($recording[Recording::SOUND_ID])) {
                    (new SoundProvider())->delete($recording[Recording::SOUND_ID]);
                }
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
