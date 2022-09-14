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
    const GADM1 = "gadm1";
    const GADM2 = "gadm2";
    const GADM3 = "gadm3";

    const CENTROID = 'centroid';

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
     * @var string
     */
    private $gadm1;

    /**
     * @var string
     */
    private $gadm2;

    /**
     * @var string
     */
    private $gadm3;

    /**
     * @var string
     */
    private $centroId;

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
     * @return string
     */
    public function getGadm3(): ?string
    {
        return $this->gadm3;
    }

    /**
     * @param string $gadm3
     * @return site
     */
    public function setGadm3($gadm3 = NULL): Site
    {
        $this->gadm3 = $gadm3;
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
    public function getFunctionalGroupId(): ?int
    {
        return $this->functional_group_id;
    }

    /**
     * @param int $functional_group_id
     * @return site
     */
    public function setFunctionalGroupId($functional_group_id = NULL): Site
    {
        $this->functional_group_id = $functional_group_id;
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
    public function getFunctionalGroup(): ?string
    {
        return $this->functional_group;
    }

    /**
     * @param string $functional_group
     * @return site
     */
    public function setFunctionalGroup($functional_group = NULL): Site
    {
        $this->functional_group = $functional_group;
        return $this;
    }
    /**
     * @return bool
     */
    public function getCentroId(): string
    {
        return $this->centroId;
    }

    /**
     * @param string $centroId
     * @return site
     */
    public function setCentroId(string $centroId): Site
    {
        $this->centroId = $centroId;
        return $this;
    }

    /**
     * @param array $siteData
     * @return bool
     * @throws \Exception
     */
    public function insert(array $siteData): bool
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
            if (end($siteData) !== $value) {
                $fields .= ", ";
                $valuesNames .= ", ";
            }
        }
        $fields .= " )";
        $valuesNames .= " )";

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
            if (end($siteData) !== $value) {
                $fields .= ', ';
            }
        }

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
