<?php

namespace BioSounds\Controller\Administration;

use BioSounds\Controller\BaseController;
use BioSounds\Entity\IucnGet;
use BioSounds\Entity\Site;
use BioSounds\Entity\SiteCollection;
use BioSounds\Entity\User;
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
        $collections = (new CollectionProvider())->getByProject($projectId, 0);
        if (empty($colId) && $collections && !(new User())->isProjectManageByProject(Auth::getUserID(), $projectId)) {
            $collectionId = $collections[0]->getId();
        }
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
            'iho' => (new SiteProvider())->getIHO(),
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

    /**
     * Upload sites from CSV file
     * @return string
     * @throws \Exception
     */
    public function uploadCSV()
    {
        if (!Auth::isManage()) {
            throw new ForbiddenException();
        }

        if (!isset($_FILES['sitesCSVFile']) || $_FILES['sitesCSVFile']['error'] != UPLOAD_ERR_OK) {
            return json_encode([
                'error_code' => 1,
                'message' => 'No file uploaded or upload error occurred.',
            ]);
        }

        $projectId = $_POST['projectId'] ?? null;
        $collectionId = $_POST['collectionId'] ?? null;

        $handle = fopen($_FILES['sitesCSVFile']['tmp_name'], "rb");
        if (!$handle) {
            return json_encode([
                'error_code' => 1,
                'message' => 'Unable to open uploaded file.',
            ]);
        }

        $data = [];
        $rowNum = 1;
        $headers = null;
        
        while (!feof($handle)) {
            $row = fgetcsv($handle);
            
            if (!$row || empty(array_filter($row))) {
                $rowNum++;
                continue;
            }

            if ($headers === null) {
                $headers = array_map('trim', $row);
                
                if (!in_array('name', $headers)) {
                    fclose($handle);
                    return json_encode([
                        'error_code' => 1,
                        'message' => "Missing required column: name",
                    ]);
                }
                $rowNum++;
                continue;
            }

            $rowData = array_combine($headers, $row);
            
            if (empty($rowData['name'])) {
                fclose($handle);
                return json_encode([
                    'error_code' => 1,
                    'message' => "Row {$rowNum}: name is required.",
                ]);
            }
            
            $hasCoords = !empty($rowData['longitude_WGS84_dd_dddd']) && !empty($rowData['latitude_WGS84_dd_dddd']);
            $hasGadm = !empty($rowData['gadm0']);
            
            if (!$hasCoords && !$hasGadm) {
                fclose($handle);
                return json_encode([
                    'error_code' => 1,
                    'message' => "Row {$rowNum}: Either coordinates (longitude AND latitude) OR gadm0 must be provided.",
                ]);
            }
            
            if (!empty($rowData['longitude_WGS84_dd_dddd']) && !is_numeric($rowData['longitude_WGS84_dd_dddd'])) {
                fclose($handle);
                return json_encode([
                    'error_code' => 1,
                    'message' => "Row {$rowNum}: longitude must be a number.",
                ]);
            }
            
            if (!empty($rowData['latitude_WGS84_dd_dddd']) && !is_numeric($rowData['latitude_WGS84_dd_dddd'])) {
                fclose($handle);
                return json_encode([
                    'error_code' => 1,
                    'message' => "Row {$rowNum}: latitude must be a number.",
                ]);
            }
            
            $data[] = $rowData;
            $rowNum++;
        }
        fclose($handle);

        if (empty($data)) {
            return json_encode([
                'error_code' => 1,
                'message' => 'No valid data rows found in CSV file.',
            ]);
        }

        $siteProvider = new SiteProvider();
        $inserted = 0;
        
        foreach ($data as $siteData) {
            $insertData = [
                'name' => htmlentities(strip_tags($siteData['name']), ENT_QUOTES),
                'user_id' => Auth::getUserID(),
                'creation_date_time' => date('Y-m-d H:i:s'),
            ];
            
            if (!empty($siteData['longitude_WGS84_dd_dddd'])) {
                $insertData['longitude_WGS84_dd_dddd'] = (float)$siteData['longitude_WGS84_dd_dddd'];
            }
            if (!empty($siteData['latitude_WGS84_dd_dddd'])) {
                $insertData['latitude_WGS84_dd_dddd'] = (float)$siteData['latitude_WGS84_dd_dddd'];
            }
            if (!empty($siteData['topography_m'])) {
                $insertData['topography_m'] = (float)$siteData['topography_m'];
            }
            if (!empty($siteData['freshwater_depth_m'])) {
                $insertData['freshwater_depth_m'] = (float)$siteData['freshwater_depth_m'];
            }
            if (!empty($siteData['gadm0'])) {
                $insertData['gadm0'] = htmlentities(strip_tags($siteData['gadm0']), ENT_QUOTES);
            }
            if (!empty($siteData['gadm1'])) {
                $insertData['gadm1'] = htmlentities(strip_tags($siteData['gadm1']), ENT_QUOTES);
            }
            if (!empty($siteData['gadm2'])) {
                $insertData['gadm2'] = htmlentities(strip_tags($siteData['gadm2']), ENT_QUOTES);
            }
            if (!empty($siteData['iho'])) {
                $insertData['iho'] = htmlentities(strip_tags($siteData['iho']), ENT_QUOTES);
            }
            if (!empty($siteData['realm_id'])) {
                $insertData['realm_id'] = (int)$siteData['realm_id'];
            }
            if (!empty($siteData['biome_id'])) {
                $insertData['biome_id'] = (int)$siteData['biome_id'];
            }
            if (!empty($siteData['functional_type_id'])) {
                $insertData['functional_type_id'] = (int)$siteData['functional_type_id'];
            }
            
            $siteId = $siteProvider->insert($insertData);
            
            if ($collectionId && $siteId) {
                $siteCollection = new SiteCollection();
                $siteCollection->insert([
                    'site_id' => $siteId,
                    'collection_id' => $collectionId,
                ]);
            }
            
            $inserted++;
        }

        return json_encode([
            'error_code' => 0,
            'message' => "Successfully uploaded {$inserted} sites.",
        ]);
    }
}
