<?php

namespace BioSounds\Entity;


class Recording
{
    const TABLE_NAME = 'recording';
    const ID = 'recording_id';
    const COL_ID = 'col_id';
    const SITE_ID = 'site_id';
    const SENSOR_ID = 'sensor_id';
    const SOUND_ID = 'sound_id';
    const DIRECTORY = 'directory';
    const FILE_SIZE = "file_size";
    const NAME = "name";
    const FILENAME = "filename";
    const FILE_DATE = "file_date";
    const FILE_TIME = "file_time";
    const DURATION = "duration";
    const CHANNEL_NUM = "channel_num";
    const SAMPLING_RATE = "sampling_rate";
    const BITRATE = 'bitrate';
    const MD5_HASH = 'md5_hash';
    const DOI = "doi";
    const Type = "type";
    const Medium = "medium";
    //const LICENSE ="license";
    const LICENSE_ID = "license_id";
    const LICENSE_NAME = "license_name";
    const SITE_NAME = "site_name";
    const USER_ID = 'user_id';
    const LABEL_ID = "label_id";
    const LABEL_NAME = 'label_name';
    /**
     * @var string
     */
    private $site_name;

    /**
     * @var integer
     */
    private $license;

    /**
     * @var string
     */
    private $license_name;

    /**
     * @var string
     */
    private $doi;

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
    private $collection;

    /**
     * @var int
     */
    private $directory;

    /**
     * @var int
     */
    private $site;

    /**
     * @var int
     */
    private $sensor;

    /**
     * @var int
     */
    private $sound;

    /**
     * @var int
     */
    private $fileSize;

    /**
     * @var string
     */
    private $fileName;

    /**
     * @var string
     */
    private $fileDate;

    /**
     * @var string
     */
    private $fileTime;

    /**
     * @var int
     */
    private $duration;

    /**
     * @var int
     */
    private $channelNum;

    /**
     * @var int
     */
    private $samplingRate;

    /**
     * @var int
     */
    private $bitrate;

    /**
     * TODO: this should be property 'sound', when using an ORM
     * @var Sound
     */
    private $soundData;



    /**
     *
     * @var int
     */
    private $user_id;

    /**
     * @var string
     */
    private $user_full_name;

    /**
     *
     * @var int
     */
    private $labelId;
    /**
     * @var string
     */
    private $labelName;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return Recording
     */
    public function setId(int $id): Recording
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
     * @return Recording
     */
    public function setName(string $name): Recording
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return int
     */
    public function getCollection(): int
    {
        return $this->collection;
    }

    /**
     * @param int $collection
     * @return Recording
     */
    public function setCollection(int $collection): Recording
    {
        $this->collection = $collection;
        return $this;
    }

    /**
     * @return int
     */
    public function getDirectory(): int
    {
        return $this->directory;
    }

    /**
     * @param int $directory
     * @return Recording
     */
    public function setDirectory(int $directory): Recording
    {
        $this->directory = $directory;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getSite(): ?int
    {
        return $this->site;
    }

    /**
     * @param int|null $site
     * @return Recording
     */
    public function setSite(?int $site): Recording
    {
        $this->site = $site;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getSensor(): ?int
    {
        return $this->sensor;
    }

    /**
     * @param int|null $sensor
     * @return Recording
     */
    public function setSensor(?int $sensor): Recording
    {
        $this->sensor = $sensor;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getSound(): ?int
    {
        return $this->sound;
    }

    /**
     * @return int
     */
    public function getUserId(): int
    {
        return $this->user_id;
    }

    /**
     * @param int|null $sound
     * @return Recording
     */
    public function setSound(?int $sound): Recording
    {
        $this->sound = $sound;
        return $this;
    }

    /**
     * @return int
     */
    public function getFileSize(): int
    {
        return $this->fileSize;
    }

    /**
     * @param int $fileSize
     * @return Recording
     */
    public function setFileSize(int $fileSize): Recording
    {
        $this->fileSize = $fileSize;
        return $this;
    }

    /**
     * @return string
     */
    public function getFileName(): string
    {
        return $this->fileName;
    }

    /**
     * @param string $fileName
     * @return Recording
     */
    public function setFileName(string $fileName): Recording
    {
        $this->fileName = $fileName;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getFileDate(): ?string
    {
        return $this->fileDate;
    }

    /**
     * @param null|string $fileDate
     * @return Recording
     */
    public function setFileDate(?string $fileDate): Recording
    {
        $this->fileDate = $fileDate;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getFileTime(): ?string
    {
        return $this->fileTime;
    }

    /**
     * @param null|string $fileTime
     * @return Recording
     */
    public function setFileTime(?string $fileTime): Recording
    {
        $this->fileTime = $fileTime;
        return $this;
    }

    /**
     * @return int
     */
    public function getDuration(): int
    {
        return $this->duration;
    }

    /**
     * @param int $duration
     * @return Recording
     */
    public function setDuration(int $duration): Recording
    {
        $this->duration = $duration;
        return $this;
    }

    /**
     * @return int
     */
    public function getChannelNum(): int
    {
        return $this->channelNum;
    }

    /**
     * @param int $channelNum
     * @return Recording
     */
    public function setChannelNum(int $channelNum): Recording
    {
        $this->channelNum = $channelNum;
        return $this;
    }

    /**
     * @return int
     */
    public function getSamplingRate(): int
    {
        return $this->samplingRate;
    }

    /**
     * @param int $samplingRate
     * @return Recording
     */
    public function setSamplingRate(int $samplingRate): Recording
    {
        $this->samplingRate = $samplingRate;
        return $this;
    }

    /**
     * @return int
     */
    public function getBitrate(): int
    {
        return $this->bitrate;
    }

    /**
     * @param int $bitrate
     * @return Recording
     */
    public function setBitrate(int $bitrate): Recording
    {
        $this->bitrate = $bitrate;
        return $this;
    }

    /**
     * @return Sound|null
     */
    public function getSoundData(): ?Sound
    {
        return $this->soundData;
    }

    /**
     * @param Sound|null $soundData
     * @return Recording
     */
    public function setSoundData(?Sound $soundData): Recording
    {
        $this->soundData = $soundData;
        return $this;
    }

    /**
     * @param string|null $user_full_name
     * @return Recording
     */
    public function setUserFullName(?string $user_full_name): Recording
    {
        $this->user_full_name = $user_full_name;
        return $this;
    }


    /**
     * @return null|string
     */
    public function getUserFullName(): ?string
    {
        return $this->user_full_name;
    }

    /**
     * @param int $user_id
     * @return Recording
     */
    public function setUserId(int $user_id): Recording
    {
        $this->user_id = $user_id;
        return $this;
    }

    /**
     * @return string
     */
    public function getDoi(): ?string
    {
        return $this->doi;
    }

    /**
     * @param string $doi
     * @return Recording
     */
    public function setDoi(?string $doi): Recording
    {
        $this->doi = $doi;
        return $this;
    }

    /**
     * @return null|int
     */
    public function getLicense(): ?int
    {
        return $this->license;
    }

    /**
     * @return string
     */
    public function getLicenseName(): string
    {
        return $this->license_name;
    }



    /**
     * @param null|int $license
     * @return Recording
     */
    public function setLicense(?int $license): Recording
    {
        $this->license = $license;
        return $this;
    }

    /**
     * @param null|string $license_name
     * @return Recording
     */
    public function setLicenseName(?string $license_name): Recording
    {
        $this->license_name = $license_name;
        return $this;
    }


    /**
     * @return null|string
     */
    public function getSiteName(): ?string
    {
        return $this->site_name;
    }


    /**
     * @param null|string $site_name
     * @return Recording
     */
    public function setSiteName(?string $site_name): Recording
    {
        $this->site_name = $site_name;
        return $this;
    }



    /**
     * @return null|int
     */
    public function getLabelId(): ?int
    {
        return $this->labelId;
    }

    /**
     * @param null|int $labelId
     * @return Recording
     */
    public function setLabelId(?int $labelId): Recording
    {
        $this->labelId = $labelId;
        return $this;
    }

    /**
     * @return string
     */
    public function getLabelName(): ?string
    {
        return $this->labelName;
    }

    /**
     * @param null|string $label_name
     * @return Recording
     */
    public function setLabelName(?string $labelName): Recording
    {
        $this->labelName = $labelName;
        return $this;
    }
    /**
     * @return string
     */
    public function getRealmName(): ?string
    {
        return $this->realmName;
    }

    /**
     * @param null|string $realmName
     * @return Recording
     */
    public function setRealmName(?string $realmName): Recording
    {
        $this->realmName = $realmName;
        return $this;
    }
    /**
     * @return string
     */
    public function getBiomeName(): ?string
    {
        return $this->biomeName;
    }

    /**
     * @param null|string $biomeName
     * @return Recording
     */
    public function setBiomeName(?string $biomeName): Recording
    {
        $this->biomeName = $biomeName;
        return $this;
    }
    /**
     * @return string
     */
    public function getFunctionalGroupName(): ?string
    {
        return $this->functionalGroupName;
    }

    /**
     * @param null|string $functionalGroupName
     * @return Recording
     */
    public function setFunctionalGroupName(?string $functionalGroupName): Recording
    {
        $this->functionalGroupName = $functionalGroupName;
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
     * @param null|float $longitude
     * @return Recording
     */
    public function setLongitude(?float $longitude): Recording
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
     * @param null|float $latitude
     * @return Recording
     */
    public function setLatitude(?float $latitude): Recording
    {
        $this->latitude = $latitude;
        return $this;
    }
    /**
     * @return null|string
     */
    public function getType(): ?string
    {
        return $this->type;
    }


    /**
     * @param null|string $type
     * @return Recording
     */
    public function setType(?string $type): Recording
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getMedium(): ?string
    {
        return $this->medium;
    }


    /**
     * @param null|string $medium
     * @return Recording
     */
    public function setMedium(?string $medium): Recording
    {
        $this->medium = $medium;
        return $this;
    }
    /**
     * @return null|string
     */
    public function getNote(): ?string
    {
        return $this->note;
    }


    /**
     * @param null|string $note
     * @return Recording
     */
    public function setNote(?string $note): Recording
    {
        $this->note = $note;
        return $this;
    }
    /**
     * @return null|string
     */
    public function getUsername(): ?string
    {
        return $this->username;
    }


    /**
     * @param null|string $username
     * @return Recording
     */
    public function setUsername(?string $username): Recording
    {
        $this->username = $username;
        return $this;
    }
    /**
     * @param array $values
     * @return $this
     */
    public function createFromValues(array $values)
    {
        $this->setId($values['recording_id']);
        $this->setName($values['name']);
        $this->setCollection($values['col_id']);
        $this->setDirectory($values['directory']);
        $this->setSensor($values['sensor_id']);
        $this->setSite($values['site_id']);
        $this->setSound($values['sound_id']);
        $this->setFileName($values['filename']);
        $this->setFileDate($values['file_date']);
        $this->setFileTime($values['file_time']);
        $this->setFileSize($values['file_size']);
        $this->setBitrate($values['bitrate']);
        $this->setChannelNum($values['channel_num']);
        $this->setSamplingRate($values['sampling_rate']);
        $this->setDuration($values['duration']);
        $this->setDoi($values['doi']);
        $this->setSiteName($values['site_name']);
        $this->setLicense($values['license_id']);
        $this->setLicenseName($values['license_name']);
        $this->setUserId($values['user_id']);
        $this->setUserName($values['user_name']);
        $this->setLabelId($values['label_id']);
        $this->setLabelName($values['label_name']);
        $this->setRealmName($values['realm']);
        $this->setBiomeName($values['biome']);
        $this->setFunctionalGroupName($values['functionalGroup']);
        $this->setLongitude($values['longitude']);
        $this->setLatitude($values['latitude']);
        $this->setType($values['type']);
        $this->setMedium($values['medium']);
        $this->setNote($values['note']);
        return $this;
    }
}
