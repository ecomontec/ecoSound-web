<?php

namespace BioSounds\Provider;

use BioSounds\Entity\Explore;
use BioSounds\Entity\Site;
use BioSounds\Exception\Database\NotFoundException;

use BioSounds\Utils\Auth;

class SiteProvider extends BaseProvider
{
    /**
     * @return Site[]
     * @throws \Exception
     */
    public function getBasicList($project_id,$collection_id): array
    {
        return $this->getList($project_id,$collection_id);
    }

    /**
     * @param string $order
     * @return Site[]
     * @throws \Exception
     */
    public function getList(int $projectId, int $collectionId = null, string $order = 'name'): array
    {
        $data = [];
        $sql = "SELECT s.*, e1.`name` AS realm,e2.`name` AS biome,e3.`name` AS functional_group FROM site s 
                    LEFT JOIN explore e1 ON e1.explore_id = s.realm_id 
                    LEFT JOIN explore e2 ON e2.explore_id = s.biome_id 
                    LEFT JOIN explore e3 ON e3.explore_id = s.functional_group_id 
                    LEFT JOIN site_collection sc ON sc.site_id = s.site_id
                    LEFT JOIN collection c ON c.collection_id = sc.collection_id
                    WHERE c.project_id = $projectId ";
        if ($collectionId != null) {
            $sql .= " AND c.collection_id = $collectionId ";
        }
        $sql .= " GROUP BY s.site_id ORDER BY $order";
        $this->database->prepareQuery($sql);
        $result = $this->database->executeSelect();
        foreach ($result as $item) {
            $data[] = (new Site())
                ->setId($item['site_id'])
                ->setName($item['name'])
                ->setUserId($item['user_id'])
                ->setCreationDateTime($item['creation_date_time'])
                ->setLongitude($item['longitude_WGS84_dd_dddd'])
                ->setLatitude($item['latitude_WGS84_dd_dddd'])
                ->setTopography($item['topography_m'])
                ->setFreshwaterDepth($item['freshwater_depth_m'])
                ->setGadm0($item['gadm0'])
                ->setGadm1($item['gadm1'])
                ->setGadm2($item['gadm2'])
                ->setRealmId($item['realm_id'])
                ->setBiomeId($item['biome_id'])
                ->setFunctionalGroupId($item['functional_group_id'])
                ->setRealm($item['realm'])
                ->setBiome($item['biome'])
                ->setFunctionalGroup($item['functional_group']);
        }
        return $data;
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
            ->setFunctionalGroup($result['functional_group_id']);
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
