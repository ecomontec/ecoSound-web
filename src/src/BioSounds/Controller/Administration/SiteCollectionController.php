<?php

namespace BioSounds\Controller\Administration;

use BioSounds\Controller\BaseController;
use BioSounds\Entity\Site;
use BioSounds\Entity\SiteCollection;
use BioSounds\Provider\ProjectProvider;
use BioSounds\Provider\SiteProvider;
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
        $site = (new SiteProvider())->get($siteId);
        $userId = Auth::getUserID();
        $listProjects = (new ProjectProvider())->getSiteProjectWithPermission($userId, $siteId);
        return json_encode([
            'errorCode' => 0,
            'data' => $this->twig->render('administration/siteCollection.html.twig', [
                'site' => $site,
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
        $result = [];
        $siteCollectionProvider = new SiteCollection();
        $collection = explode(',', $siteCollectionProvider->isValid($_POST['site'], $_POST['site_id']));
        if (isset($_POST['d'])) {
            foreach ($_POST['d'] as $row) {
                if (isset($row['c'])) {
                    $siteCollectionProvider->delete($row['c'], $_POST['site_id']);
                    if ($row['b'] == 'true') {
                        if (in_array($row['c'], $collection)) {
                            if (!in_array($row['p'], $result)) {
                                $result[] = $row['p'];
                            }
                            continue;
                        }
                        $siteCollectionProvider->insert($row['c'], $_POST['site_id']);
                    }
                }
            }
        }
        if (count($result) > 0) {
            return json_encode([
                'isValid' => 1,
                'result' => $result,
                'message' => 'Site name already exists in the project.',
            ]);
        }
        return json_encode([
            'errorCode' => 0,
            'message' => 'successfully changed site assignment',
        ]);
    }
}
