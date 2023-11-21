<?php

namespace BioSounds\Controller\Administration;

use BioSounds\Controller\BaseController;
use BioSounds\Entity\IucnGet;
use BioSounds\Entity\Site;
use BioSounds\Entity\SiteCollection;
use BioSounds\Exception\ForbiddenException;
use BioSounds\Provider\CollectionProvider;
use BioSounds\Provider\ProjectProvider;
use BioSounds\Provider\SiteProvider;
use BioSounds\Utils\Auth;


class SiteController extends BaseController
{
    const SECTION_TITLE = 'Sites';

    /**
     * @param int|null $projectId
     * @param int|null $collectionId
     * @return false|string
     * @throws \Exception
     */
    public function show($projectId = null, $collectionId = null)
    {
        if (!Auth::isManage()) {
            throw new ForbiddenException();
        }
        if (isset($_GET['projectId'])) {
            $projectId = $_GET['projectId'];
        }
        if (isset($_GET['collectionId'])) {
            $collectionId = $_GET['collectionId'];
        }
        if ($collectionId == '') {
            $collectionId = null;
        }
        $projects = (new ProjectProvider())->getWithPermission(Auth::getUserID());
        if (empty($projectId)) {
            $projectId = $projects[0]->getId();
        }
        $collections = (new CollectionProvider())->getByProject($projectId, Auth::getUserID());
        $arr = [];
        $iucn_gets = (new IucnGet())->getAllIucnGets();
        foreach ($iucn_gets as $iucn_get) {
            $arr['pid' . $iucn_get['pid']]['id' . $iucn_get['iucn_get_id']] = [$iucn_get['iucn_get_id'], $iucn_get['name']];
        }
        return $this->twig->render('administration/sites.html.twig', [
            'projects' => $projects,
            'collections' => $collections,
            'projectId' => $projectId,
            'collectionId' => $collectionId,
            'iucn_gets' => $arr,
            'realms' => (new IucnGet())->getIucnGets(),
            'gadm0' => json_decode($this->gadm()),
        ]);
    }

    public function getListByPage($projectId = null, $collectionId = null)
    {
        $total = count((new SiteProvider())->getSite($projectId, $collectionId));
        $start = $_POST['start'];
        $length = $_POST['length'];
        $search = $_POST['search']['value'];
        $column = $_POST['order'][0]['column'];
        $dir = $_POST['order'][0]['dir'];
        $data = (new SiteProvider())->getListByPage($projectId, $collectionId, $start, $length, $search, $column, $dir);
        if (count($data) == 0) {
            $data = [];
        }
        $result = [
            'draw' => $_POST['draw'],
            'recordsTotal' => $total,
            'recordsFiltered' => (new SiteProvider())->getFilterCount($projectId, $collectionId, $search),
            'data' => $data,
        ];
        return json_encode($result);
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function save()
    {
        $siteEnt = new Site();
        $siteProvider = new SiteProvider();
        if (!Auth::isManage()) {
            throw new ForbiddenException();
        }

        $data = [];
        foreach ($_POST as $key => $value) {
            if (strrpos($key, '_')) {
                $key = substr($key, 0, strrpos($key, '_'));
            }
            $sitePdoValue = $value;
            if ($sitePdoValue != '0' && empty($sitePdoValue)) {
                $sitePdoValue = '';
            }
            switch ($key) {
                case 'project_id':
                    $project_id = $sitePdoValue;
                    break;
                case 'collection_id':
                    $collection_id = $sitePdoValue;
                    break;
                case 'realm_id':
                    $data['realm_id'] = $sitePdoValue == '' ? 0 : $sitePdoValue;
                    break;
                case 'biome_id':
                    $data['biome_id'] = $sitePdoValue == '' ? 0 : $sitePdoValue;
                    break;
                case 'functional_type_id':
                    $data['functional_type_id'] = $sitePdoValue == '' ? 0 : $sitePdoValue;
                    break;
                case 'longitude':
                    $data['longitude_WGS84_dd_dddd'] = $sitePdoValue == '' ? null : $sitePdoValue;
                    break;
                case 'latitude':
                    $data['latitude_WGS84_dd_dddd'] = $sitePdoValue == '' ? null : $sitePdoValue;
                    break;
                case 'topography_m':
                    $data['topography_m'] = $sitePdoValue == '' ? null : $sitePdoValue;
                    break;
                case 'freshwater_depth_m':
                    $data['freshwater_depth_m'] = $sitePdoValue == '' ? null : $sitePdoValue;
                    break;
                default:
                    $data[$key] = $sitePdoValue;
            }
        }
        if ($siteProvider->isValid($data['name'], $project_id, $data['steId'])) {
            return json_encode([
                'isValid' => 1,
                'message' => 'Site name already exists in this project.',
            ]);
        }
        if (isset($data['steId'])) {
            $siteEnt->update($data);
            return json_encode([
                'errorCode' => 0,
                'message' => 'Site updated successfully.',
            ]);
        } else {
            $data['creation_date_time'] = date('Y-m-d H:i:s', time());
            $data['user_id'] = Auth::getUserID();
            $site_id = $siteEnt->insert($data);
            $siteCollection = new SiteCollection();
            if ($collection_id != "") {
                $siteCollection->insert($site_id, $collection_id);
            } else {
                $siteCollection->insertByProject($project_id, $site_id);
            }
            return json_encode([
                'errorCode' => 0,
                'message' => 'Site created successfully.',
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

        $siteProvider = new SiteProvider();
        $siteProvider->delete($id);

        return json_encode([
            'errorCode' => 0,
            'message' => 'Site deleted successfully.',
        ]);
    }

    public function getIucnGet(int $pid = 0)
    {
        $iucn_gets = (new IucnGet())->getIucnGets($pid);
        return json_encode($iucn_gets);
    }

    public function gadm(int $level = 0, string $pid = '0')
    {
        return json_encode((new SiteProvider())->getGamds($level, $pid));
    }

    public function export($project_id, $collection_id)
    {
        if (!Auth::isManage()) {
            throw new ForbiddenException();
        }
        $colArr = [];
        $file_name = "sites.csv";
        $fp = fopen('php://output', 'w');
        header('Content-Type: application/octet-stream;charset=utf-8');
        header('Accept-Ranges:bytes');
        header('Content-Disposition: attachment; filename=' . $file_name);
        $columns = (new SiteProvider())->getColumns();
        foreach ($columns as $column) {
            if ($column['COLUMN_NAME'] == 'user_id') {
                continue;
            }
            $colArr[] = $column['COLUMN_NAME'];
        }

        array_splice($colArr, 10, 0, 'realm');
        array_splice($colArr, 12, 0, 'biome');
        array_splice($colArr, 14, 0, 'functional_type');

        $Als[] = $colArr;
        $List = (new SiteProvider())->getSite($project_id, $collection_id);
        foreach ($List as $Item) {
            unset($Item['user_id']);
            $Item['realm_id'] = $Item['realm_id'] == '0' ? '' : $Item['realm_id'];
            $Item['biome_id'] = $Item['biome_id'] == '0' ? '' : $Item['biome_id'];
            $Item['functional_type_id'] = $Item['functional_type_id'] == '0' ? '' : $Item['functional_type_id'];
            $valueToMove = $Item['realm'] == null ? '' : $Item['realm'];
            unset($Item['realm']);
            array_splice($Item, 10, 0, $valueToMove);
            $valueToMove = $Item['biome'] == null ? '' : $Item['biome'];
            unset($Item['biome']);
            array_splice($Item, 12, 0, $valueToMove);
            $valueToMove = $Item['functional_type'] == null ? '' : $Item['functional_type'];
            unset($Item['functional_type']);
            array_splice($Item, 14, 0, $valueToMove);

            $Als[] = $Item;
        }
        foreach ($Als as $line) {
            fputcsv($fp, $line);
        }
        fclose($fp);
        exit();
    }
}
