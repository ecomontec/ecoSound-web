<?php

namespace BioSounds\Provider;

use BioSounds\Entity\IucnGet;
use BioSounds\Entity\Site;
use BioSounds\Exception\Database\NotFoundException;

use BioSounds\Utils\Auth;
use Cassandra\Varint;

class SiteProvider extends BaseProvider
{
    /**
     * @param string $order
     * @return Site[]
     * @throws \Exception
     */
    public function getList(int $projectId, int $collectionId = null, string $order = 'name'): array
    {
        $sql = "SELECT s.site_id,s.name FROM site s 
                    LEFT JOIN site_collection sc ON sc.site_id = s.site_id
                    LEFT JOIN collection c ON c.collection_id = sc.collection_id
                    WHERE c.project_id = $projectId ";
        if ($collectionId != null) {
            $sql .= " AND c.collection_id = $collectionId ";
        }
        $sql .= " GROUP BY s.site_id ORDER BY $order";
        $this->database->prepareQuery($sql);
        $result = $this->database->executeSelect();
        return $result;
    }

    /**
     * @param string $order
     * @return Site[]
     * @throws \Exception
     */
    public function getListWithCollection(int $projectId, int $collectionId = null, string $order = 'name'): array
    {
        $str = ($collectionId == null) ? "" : " AND sc.collection_id = $collectionId ";
        $sql = "SELECT s.site_id,s.name,sc.collection, IF(longitude_WGS84_dd_dddd IS NOT NULL AND latitude_WGS84_dd_dddd IS NOT NULL,s.longitude_WGS84_dd_dddd,IF(gadm2 IS NOT NULL,a2.x,IF(gadm1 IS NOT NULL,a1.x,IF( gadm0 IS NOT NULL, a0.x, NULL )))) AS x,IF(longitude_WGS84_dd_dddd IS NOT NULL AND latitude_WGS84_dd_dddd IS NOT NULL,s.latitude_WGS84_dd_dddd,IF(gadm2 IS NOT NULL,a2.y,IF(gadm1 IS NOT NULL,a1.y,IF( gadm0 IS NOT NULL, a0.y, NULL )))) AS y FROM site s LEFT JOIN adm_2 a2 ON a2.NAME = s.gadm2 LEFT JOIN adm_1 a1 ON a1.NAME = s.gadm1 LEFT JOIN adm_0 a0 ON a0.NAME = s.gadm0 LEFT JOIN (SELECT sc.site_id,GROUP_CONCAT( sc.collection_id )AS collection FROM site_collection sc LEFT JOIN collection c ON c.collection_id = sc.collection_id WHERE c.project_id = $projectId $str GROUP BY sc.site_id) sc ON sc.site_id = s.site_id";
        $sql .= " GROUP BY s.site_id,a0.x,a1.x,a2.x,a0.y,a1.y,a2.y ORDER BY $order";
        $this->database->prepareQuery($sql);
        $result = $this->database->executeSelect();
        return $result;
    }

    public function getCount(string $projectId, string $collectionId): int
    {
        $sql = "SELECT COUNT(*) FROM site s 
                    LEFT JOIN site_collection sc ON sc.site_id = s.site_id
                    LEFT JOIN collection c ON c.collection_id = sc.collection_id
                    WHERE c.project_id = $projectId ";
        if ($collectionId != null && $collectionId != '0') {
            $sql .= " AND c.collection_id = $collectionId ";
        }
        $sql .= " GROUP BY s.site_id ";
        $this->database->prepareQuery($sql);
        $count = count($this->database->executeSelect());
        return $count;
    }

    public function getFilterCount(string $projectId, string $collectionId, string $search): int
    {
        $sql = "SELECT COUNT(*) FROM site s 
                    LEFT JOIN iucn_get e1 ON e1.iucn_get_id = s.realm_id 
                    LEFT JOIN iucn_get e2 ON e2.iucn_get_id = s.biome_id 
                    LEFT JOIN iucn_get e3 ON e3.iucn_get_id = s.functional_type_id 
                    LEFT JOIN site_collection sc ON sc.site_id = s.site_id
                    LEFT JOIN collection c ON c.collection_id = sc.collection_id
                    WHERE c.project_id = $projectId ";
        if ($collectionId != null && $collectionId != '0') {
            $sql .= " AND c.collection_id = $collectionId ";
        }
        if ($search) {
            $sql .= " AND CONCAT(s.site_id, s.name, s.longitude_WGS84_dd_dddd, s.latitude_WGS84_dd_dddd, s.topography_m, s.freshwater_depth_m, e1.name, e2.name, e3.name) LIKE '%$search%' ";
        }
        $sql .= " GROUP BY s.site_id ";
        $this->database->prepareQuery($sql);
        $count = count($this->database->executeSelect());
        return $count;
    }

    public function getListByPage(string $projectId, string $collectionId, string $start = '0', string $length = '8', string $search = null, string $column = '0', string $dir = 'asc'): array
    {
        $arr = [];
        $sql = "SELECT s.*, e1.`name` AS realm,e2.`name` AS biome,e3.`name` AS functional_type FROM site s 
                    LEFT JOIN iucn_get e1 ON e1.iucn_get_id = s.realm_id 
                    LEFT JOIN iucn_get e2 ON e2.iucn_get_id = s.biome_id 
                    LEFT JOIN iucn_get e3 ON e3.iucn_get_id = s.functional_type_id 
                    LEFT JOIN site_collection sc ON sc.site_id = s.site_id
                    LEFT JOIN collection c ON c.collection_id = sc.collection_id
                    WHERE c.project_id = $projectId ";
        if ($collectionId != null && $collectionId != '0') {
            $sql .= " AND c.collection_id = $collectionId ";
        }
        if ($search) {
            $sql .= " AND CONCAT(s.site_id, s.name, s.longitude_WGS84_dd_dddd, s.latitude_WGS84_dd_dddd, s.topography_m, s.freshwater_depth_m, e1.name, e2.name, e3.name) LIKE '%$search%' ";
        }
        $sql .= " GROUP BY s.site_id ";
        $a = ['s.site_id', 's.name', 's.longitude_WGS84_dd_dddd', 's.latitude_WGS84_dd_dddd', 's.topography_m', 's.freshwater_depth_m', 's.gadm0', 's.gadm1', 's.gadm2', 'e1.name', 'e2.name', 'e3.name'];
        $sql .= " ORDER BY $a[$column] $dir LIMIT $length OFFSET $start";
        $this->database->prepareQuery($sql);
        $result = $this->database->executeSelect();
        if (count($result)) {
            foreach ($result as $key => $value) {
                $arr[$key][0] = "$value[site_id]<input type='hidden' name='steId' value='$value[site_id]'>";
                $arr[$key][1] = "<input type='text' class='form-control form-control-sm' style='width:100px;' name='name' value='$value[name]'>";
                $arr[$key][2] = "<input type='number' class='form-control form-control-sm' style='width:120px;' name='longitude' min='-180' max='180' step='0.0000000000001' pattern='##.##############' value='$value[longitude_WGS84_dd_dddd]'><div class='invalid-feedback'>Please provide a longitude value, from -180 to 180.</div>";
                $arr[$key][3] = "<input type='number' class='form-control form-control-sm' style='width:120px;' name='latitude' min='-90' max='90' step='0.0000000000001' pattern='##.##############' value='$value[latitude_WGS84_dd_dddd]'><div class='invalid-feedback'>Please provide a latitude value, from -90 to 90.</div>";
                $arr[$key][4] = "<input type='number' class='form-control form-control-sm' style='width:120px;' name='topography_m' min='-15000' max='10000' step='0.1' pattern='##.##' value='$value[topography_m]'><div class='invalid-feedback'>Please provide a topography value, from -15000.0 to 10000.0.</div>";
                $arr[$key][5] = "<input type='number' class='form-control form-control-sm' style='width:120px;' name='freshwater_depth_m' min='-2000' max='0' step='0.1' pattern='##.##' value='$value[freshwater_depth_m]'><div class='invalid-feedback'>Please provide a freshwater depth value, from -2000.0 to 0.0.</div>";
                $arr[$key][6] = "<select id='gadm0_$value[site_id]' name='gadm0' style='width:120px;' class='form-control form-control-sm'><option value='$value[gadm0]' selected>$value[gadm0]</option></select>";
                $arr[$key][7] = "<select id='gadm1_$value[site_id]' name='gadm1' style='width:120px;' class='form-control form-control-sm'><option value='$value[gadm1]' selected>$value[gadm1]</option></select>";
                $arr[$key][8] = "<select id='gadm2_$value[site_id]' name='gadm2' style='width:120px;' class='form-control form-control-sm'><option value='$value[gadm2]' selected>$value[gadm2]</option></select>";
                $arr[$key][9] = "<select id='realm_$value[site_id]' name='realm_id' style='width:120px;' class='form-control form-control-sm'><option value='$value[realm_id]'>$value[realm]</option></select>";
                $arr[$key][10] = "<select id='biome_$value[site_id]' name='biome_id' class='form-control form-control-sm' style='width:120px;'><option value='$value[biome_id]' selected>$value[biome]</option></select>";
                $arr[$key][11] = "<select id='functionalType_$value[site_id]' name='functional_type_id' class='form-control form-control-sm' style='width:120px;'><option value='$value[functional_type_id]' selected>$value[functional_type]</option></select>";
                $arr[$key][12] = "<a href='" . $this->config['APP_URL'] . "/admin/siteCollections/$value[site_id]' class='js-open-modal' title='Site Assignment'><i class='fas fa-tasks'></i></a>";
                $arr[$key][13] = "<a class='js-site-delete' href='#' data-id='$value[site_id]' title='Delete Site'><span class='fas fa-trash'></span></a>";
            }
        }
        return $arr;
    }

    /**
     * @param string $siteId
     * @return Site|null
     * @throws \Exception
     */
    public function get(string $siteId): ?Site
    {
        $this->database->prepareQuery('SELECT * FROM site WHERE site_id = :siteId');

        if (empty($result = $this->database->executeSelect([':siteId' => $siteId]))) {
            return null;
        }
        $result = $result[0];
        return (new Site())
            ->setId($result['site_id'])
            ->setName($result['name'])
            ->setUserId($result['user_id'])
            ->setCreationDateTime($result['creation_date_time'])
            ->setLongitude($result['longitude_WGS84_dd_dddd'])
            ->setLatitude($result['latitude_WGS84_dd_dddd'])
            ->setTopography($result['topography_m'])
            ->setFreshwaterDepth($result['freshwater_depth_m'])
            ->setGadm0($result['gadm0'])
            ->setGadm1($result['gadm1'])
            ->setGadm2($result['gadm2'])
            ->setRealm($result['realm_id'])
            ->setBiome($result['biome_id'])
            ->setFunctionalType($result['functional_type_id']);
    }

    public function getGamds(int $level = 0, string $pid = '0')
    {
        $this->database->prepareQuery('SELECT `name` FROM adm_' . $level . ' WHERE pid = "' . $pid . '" GROUP BY `name` ORDER BY `name`');
        $data = $this->database->executeSelect();
        return $data;
    }

    public function getGamd(int $level, string $name)
    {
        $this->database->prepareQuery('SELECT x,y FROM adm_' . $level . ' WHERE name = "' . $name . '"');
        return $this->database->executeSelect()[0];
    }

    /**
     * @param int $id
     * @throws \Exception
     */
    public function delete(int $id): void
    {
        $this->database->prepareQuery('DELETE FROM site WHERE ' . Site::PRIMARY_KEY . ' = :id');
        $this->database->executeDelete([':id' => $id]);
        $this->database->prepareQuery('DELETE FROM site_collection WHERE site_id = :id');
        $this->database->executeDelete([':id' => $id]);
    }
}
