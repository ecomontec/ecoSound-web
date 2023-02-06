<?php

namespace BioSounds\Entity;

class Site extends AbstractProvider
{
    const TABLE_NAME = "site";
    const PRIMARY_KEY = "site_id";
    const NAME = "name";
    const USER_ID = "user_id";
    const CREATION_DATE_TIME = "creation_date_time";
    const LONGITUDE = "longitude_WGS84_dd_dddd";
    const LATITUDE = "latitude_WGS84_dd_dddd";
    const GADM0 = "gadm0";
    const GADM1 = "gadm1";
    const GADM2 = "gadm2";

    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var int
     */
    private $userId;

    /**
     * @var int
     */
    private $collectionId;

    /**
     * @var string
     */
    private $collection;

    /**
     * @var string
     */
    private $creationDateTime;

    /**
     * @var float
     */
    private $longitude;

    /**
     * @var float
     */
    private $latitude;

    /**
     * @var float
     */
    private $topography;

    /**
     * @var float
     */
    private $freshwater_depth;

    /**
     * @var string
     */
    private $gadm0;

    /**
     * @var string
     */
    private $gadm1;

    /**
     * @var string
     */
    private $gadm2;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return site
     */
    public function setId(int $id): Site
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return site
     */
    public function setName(string $name): Site
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return int
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return site
     */
    public function setUserId(int $userId): Site
    {
        $this->userId = $userId;
        return $this;
    }

    /**
     * @return string
     */
    public function getCreationDateTime(): string
    {
        return $this->creationDateTime;
    }

    /**
     * @param string $creationDateTime
     * @return site
     */
    public function setCreationDateTime(string $creationDateTime): Site
    {
        $this->creationDateTime = $creationDateTime;
        return $this;
    }

    /**
     * @return float
     */
    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    /**
     * @param float $longitude
     * @return site
     */
    public function setLongitude(?float $longitude): Site
    {
        $this->longitude = $longitude;
        return $this;
    }

    /**
     * @return float
     */
    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    /**
     * @param float $latitude
     * @return site
     */
    public function setLatitude(?float $latitude): Site
    {
        $this->latitude = $latitude;
        return $this;
    }

    /**
     * @return float
     */
    public function getTopography(): ?float
    {
        return $this->topography;
    }

    /**
     * @param float $topography
     * @return site
     */
    public function setTopography(?float $topography): Site
    {
        $this->topography = $topography;
        return $this;
    }

    /**
     * @return float
     */
    public function getFreshwaterDepth(): ?float
    {
        return $this->freshwater_depth;
    }

    /**
     * @param float $freshwater_depth
     * @return site
     */
    public function setFreshwaterDepth(?float $freshwater_depth): Site
    {
        $this->freshwater_depth = $freshwater_depth;
        return $this;
    }

    /**
     * @return string
     */
    public function getGadm0(): ?string
    {
        return $this->gadm0;
    }

    /**
     * @param string $gadm0
     * @return site
     */
    public function setGadm0($gadm0 = NULL): Site
    {
        $this->gadm0 = $gadm0;
        return $this;
    }

    /**
     * @return string
     */
    public function getGadm1(): ?string
    {
        return $this->gadm1;
    }

    /**
     * @param string $gadm1
     * @return site
     */
    public function setGadm1($gadm1 = NULL): Site
    {
        $this->gadm1 = $gadm1;
        return $this;
    }

    /**
     * @return string
     */
    public function getGadm2(): ?string
    {
        return $this->gadm2;
    }

    /**
     * @param string $gadm2
     * @return site
     */
    public function setGadm2($gadm2 = NULL): Site
    {
        $this->gadm2 = $gadm2;
        return $this;
    }

    /**
     * @return int
     */
    public function getRealmId(): ?int
    {
        return $this->realm_id;
    }

    /**
     * @param int $realm_id
     * @return site
     */
    public function setRealmId($realm_id = NULL): Site
    {
        $this->realm_id = $realm_id;
        return $this;
    }

    /**
     * @return int
     */
    public function getBiomeId(): ?int
    {
        return $this->biome_id;
    }

    /**
     * @param int $biome_id
     * @return site
     */
    public function setBiomeId($biome_id = NULL): Site
    {
        $this->biome_id = $biome_id;
        return $this;
    }

    /**
     * @return int
     */
    public function getFunctionalTypeId(): ?int
    {
        return $this->functional_type_id;
    }

    /**
     * @param int $functional_type_id
     * @return site
     */
    public function setFunctionalTypeId($functional_type_id = NULL): Site
    {
        $this->functional_type_id = $functional_type_id;
        return $this;
    }

    /**
     * @return string
     */
    public function getRealm(): ?string
    {
        return $this->realm;
    }

    /**
     * @param string $realm
     * @return site
     */
    public function setRealm($realm = NULL): Site
    {
        $this->realm = $realm;
        return $this;
    }

    /**
     * @return string
     */
    public function getBiome(): ?string
    {
        return $this->biome;
    }

    /**
     * @param string $biome
     * @return site
     */
    public function setBiome($biome = NULL): Site
    {
        $this->biome = $biome;
        return $this;
    }

    /**
     * @return string
     */
    public function getFunctionalType(): ?string
    {
        return $this->functional_type;
    }

    /**
     * @param string $functional_type
     * @return site
     */
    public function setFunctionalType($functional_type = NULL): Site
    {
        $this->functional_type = $functional_type;
        return $this;
    }

    public function getCollection(int $site_id, string $collections)
    {
        $collections = $collections=="" ? "''" : $collections;
        $this->database->prepareQuery("SELECT collection_id FROM site_collection WHERE site_id = :site_id AND collection_id IN ($collections)");
        if (empty($result = $this->database->executeSelect([":site_id" => $site_id]))) {
            return null;
        }
        return $result;
    }

    /**
     * @param array $siteData
     * @return int
     * @throws \Exception
     */
    public function insert(array $siteData): int
    {
        if (empty($siteData)) {
            return false;
        }

        $fields = "( ";
        $valuesNames = "( ";
        $values = array();

        foreach ($siteData as $key => $value) {
            $fields .= $key;
            $valuesNames .= ":" . $key;
            $values[":" . $key] = $value;
            $fields .= ",";
            $valuesNames .= ",";
        }
        $fields = substr($fields, 0, strlen($fields) - 1) . ' )';
        $valuesNames = substr($valuesNames, 0, strlen($valuesNames) - 1) . ' )';
        $this->database->prepareQuery("INSERT INTO site $fields VALUES $valuesNames");
        return $this->database->executeInsert($values);
    }


    /**
     * @param array $siteData
     * @return bool
     * @throws \Exception
     */
    public function update(array $siteData): bool
    {
        if (empty($siteData)) {
            return false;
        }

        $steId = $siteData["steId"];
        unset($siteData["steId"]);
        $fields = '';
        $values = [];

        foreach ($siteData as $key => $value) {
            $fields .= $key . ' = :' . $key;
            $values[':' . $key] = $value;
            $fields .= ",";
        }
        $fields = substr($fields, 0, strlen($fields) - 1);

        $values[':siteId'] = $steId;
        $this->database->prepareQuery("UPDATE site SET $fields WHERE site_id = :siteId");
        return $this->database->executeUpdate($values);
    }

    public function getList($term)
    {
        $query = 'SELECT ' . self::PRIMARY_KEY . ',' . self::NAME .
            ' FROM ' . self::TABLE_NAME .
            ' WHERE ' . self::NAME . ' LIKE :name ' .
            ' ORDER BY ' . self::NAME . ' ASC LIMIT 0,15';

        $field = ['name' => "%$term%"];

        $this->database->prepareQuery($query);
        $result = $this->database->executeSelect($field);

        return $result;
    }
}
