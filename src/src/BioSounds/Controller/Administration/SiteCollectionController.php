<?php

namespace BioSounds\Controller\Administration;

use BioSounds\Controller\BaseController;
use BioSounds\Entity\SiteCollection;
use BioSounds\Provider\ProjectProvider;
use BioSounds\Utils\Auth;


class SiteCollectionController extends BaseController
{
    /**
     * @param int $userId
     * @return false|string
     * @throws \Exception
     */
    public function show(int $siteId)
    {
        $userId = Auth::getUserID();
        $listProjects = (new ProjectProvider())->getSiteProjectWithPermission($userId,$siteId);
        return json_encode([
            'errorCode' => 0,
            'data' => $this->twig->render('administration/siteCollection.html.twig', [
                'projects' => $listProjects,
                'site_id' => $siteId,
            ]),
        ]);
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function save(): string
    {
        $siteCollectionProvider = new SiteCollection();
        foreach ($_POST['data'] as $row) {
            if (isset($row['collection_id'])) {
                $siteCollectionProvider->delete($row['collection_id'], $row['site_id']);
                if ($row['check'] == 'true') {
                    $siteCollectionProvider->insert($row['collection_id'], $row['site_id']);
                }
            }
        }
        return json_encode([
            'errorCode' => 0,
            'message' => 'successfully changed site privileges',
        ]);
    }
}
