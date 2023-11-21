<?php

namespace BioSounds\Controller\Administration;

use BioSounds\Controller\BaseController;
use BioSounds\Entity\Site;
use BioSounds\Entity\SiteCollection;
use BioSounds\Exception\ForbiddenException;
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
    public function show()
    {
        $siteId = $_POST['id'];
        $site_name = $_POST['name'];
        $site = count(explode(',', $siteId)) == 1 ? (new SiteProvider())->get($siteId[0]) : '';
        $userId = Auth::getUserID();
        $listProjects = (new ProjectProvider())->getSiteProjectWithPermission($userId, $siteId);
        return json_encode([
            'errorCode' => 0,
            'data' => $this->twig->render('administration/siteCollection.html.twig', [
                'site' => $site,
                'site_name' => $site_name,
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
        $site_name = "'" . implode("','", explode(',', $_POST['site'])) . "'";
        $siteCollectionProvider = new SiteCollection();
        $result = array_map(function ($segment) {
            return explode('@', $segment);
        }, explode('&', $siteCollectionProvider->isValid($site_name, $_POST['site_id'], $_POST['c']['true'])));
        if ($result[0][0] != '') {
            return json_encode([
                'isValid' => 1,
                'result' => $result,
            ]);
        }
        if (isset($_POST['c']['true'])) {
            $siteCollectionProvider->delete($_POST['site_id'], $_POST['c']['true']);
        }
        if (isset($_POST['c']['false'])) {
            $siteCollectionProvider->delete($_POST['site_id'], $_POST['c']['false']);
        }
        if (isset($_POST['c']['true'])) {
            $siteCollectionProvider->insert($_POST['site_id'], $_POST['c']['true']);
        }

        return json_encode([
            'errorCode' => 0,
            'message' => 'successfully changed site assignment',
        ]);
    }

    public function export($project_id, $collection_id)
    {
        if (!Auth::isManage()) {
            throw new ForbiddenException();
        }
        $colArr = [];
        $file_name = "site_collections.csv";
        $fp = fopen('php://output', 'w');
        header('Content-Type: application/octet-stream;charset=utf-8');
        header('Accept-Ranges:bytes');
        header('Content-Disposition: attachment; filename=' . $file_name);
        $columns = (new SiteCollection())->getColumns();
        foreach ($columns as $column) {
            $colArr[] = $column['COLUMN_NAME'];
        }

        $Als[] = $colArr;
        $List = (new SiteCollection())->getSiteCollection($project_id, $collection_id);
        foreach ($List as $Item) {
            $Als[] = $Item;
        }
        foreach ($Als as $line) {
            fputcsv($fp, $line);
        }
        fclose($fp);
        exit();
    }
}
