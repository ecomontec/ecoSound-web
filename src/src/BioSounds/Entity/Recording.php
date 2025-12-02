<?php

namespace BioSounds\Entity;


class Recording
{
    const TABLE_NAME = 'recording';
    const ID = 'recording_id';
    const COL_ID = 'col_id';
    const SITE_ID = 'site_id';
    const RECORDER_ID = 'recorder_id';
    const MICROPHONE_ID = 'microphone_id';
    const RECORDER_NAME = 'model';
    const MICROPHONE_NAME = 'name';
    const RECORDING_GAIN = 'recording_gain';
    const DIRECTORY = 'directory';
    const FILE_SIZE = "file_size";
    const NAME = "name";
    const FILENAME = "filename";
    const FILE_DATE = "file_date";
    const FILE_TIME = "file_time";
    const DURATION = "duration";
    const CHANNEL_NUM = "channel_num";
    const SAMPLING_RATE = "sampling_rate";
    const BITDEPTH  = 'bitdepth';
    const MD5_HASH = 'md5_hash';
    const DOI = "doi";
    const Type = "type";
    const Medium = "medium";
    const LICENSE_ID = "license_id";
    const LICENSE_NAME = "license_name";
    const SITE_NAME = "site_name";
    const USER_ID = 'user_id';
    const LABEL_ID = "label_id";
    const LABEL_NAME = 'label_name';
    const Note = 'note';
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
    private $recorder;

    /**
     * @var int
     */
    private $microphone;

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
    private $bitdepth;

    /**
     * TODO: this should be property 'sound', when using an ORM
     * @var Sound
     */
    private $soundData;

    /**
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
    private $realmName;
    private $biomeName;
    private $functionalTypeName;
    private $longitude;
    private $latitude;
    private $type;
    private $medium;
    private $note;
    private $username;
    private $recorderName;
    private $microphoneName;
    private $startDate;
    private $endDate;
    private $path;
    private $iho;
    private $duty_cycle_recording;
    private $duty_cycle_period;
    private $realmId;
    private $biomeId;
    private $functionalTypeId;
    private $recording_gain;

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
    public function getDirectory(): ?int
    {
        return $this->directory;
    }

    /**
     * @param int $directory
     * @return Recording
     */
    public function setDirectory(?int $directory): Recording
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
    public function getRecorder(): ?int
    {
        return $this->recorder;
    }

    /**
     * @param int|null $recorder
     * @return Recording
     */
    public function setRecorder(?int $recorder): Recording
    {
        $this->recorder = $recorder;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getRecorderName(): ?string
    {
        return $this->recorderName;
    }

    /**
     * @param string|null $recorderName
     * @return Recording
     */
    public function setRecorderName(?string $recorderName): Recording
    {
        $this->recorderName = $recorderName;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getMicrophone(): ?int
    {
        return $this->microphone;
    }

    /**
     * @param int|null $microphone
     * @return Recording
     */
    public function setMicrophone(?int $microphone): Recording
    {
        $this->microphone = $microphone;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getMicrophoneName(): ?string
    {
        return $this->microphoneName;
    }

    /**
     * @param string|null $microphoneName
     * @return Recording
     */
    public function setMicrophoneName(?string $microphoneName): Recording
    {
        $this->microphoneName = $microphoneName;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getRecordingGain(): ?int
    {
        return $this->recording_gain;
    }

    /**
     * @param int|null $recording_gain
     * @return Recording
     */
    public function setRecordingGain(?int $recording_gain): Recording
    {
        $this->recording_gain = $recording_gain;
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
    public function getUserId(): ?int
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
    public function getFileSize(): ?int
    {
        return $this->fileSize;
    }

    /**
     * @param int $fileSize
     * @return Recording
     */
    public function setFileSize(?int $fileSize): Recording
    {
        $this->fileSize = $fileSize;
        return $this;
    }

    /**
     * @return string
     */
    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    /**
     * @param string $fileName
     * @return Recording
     */
    public function setFileName(?string $fileName): Recording
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
    public function getStartDate(): ?string
    {
        return $this->startDate;
    }

    /**
     * @param null|string $startDate
     * @return Recording
     */
    public function setStartDate(?string $startDate): Recording
    {
        $this->startDate = $startDate;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getEndDate(): ?string
    {
        return $this->endDate;
    }

    /**
     * @param null|string $endDate
     * @return Recording
     */
    public function setEndDate(?string $endDate): Recording
    {
        $this->endDate = $endDate;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getDataType(): ?string
    {
        return $this->data_type;
    }

    /**
     * @param null|string $data_type
     * @return Recording
     */
    public function setDataType(?string $data_type): Recording
    {
        $this->data_type = $data_type;
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
    public function getBitdepth(): int
    {
        return $this->bitdepth;
    }

    /**
     * @param int $bitdepth
     * @return Recording
     */
    public function setBitdepth(int $bitdepth): Recording
    {
        $this->bitdepth = $bitdepth;
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
    public function setUserId(?int $user_id): Recording
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
    public function getLicenseName(): ?string
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
     * @return int
     */
    public function getRealmId(): ?int
    {
        return $this->realmId;
    }

    /**
     * @param null|int $realmId
     * @return Recording
     */
    public function setRealmId(?int $realmId): Recording
    {
        $this->realmId = $realmId;
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
     * @return int
     */
    public function getBiomeId(): ?int
    {
        return $this->biomeId;
    }

    /**
     * @param null|int $biomeId
     * @return Recording
     */
    public function setBiomeId(?int $biomeId): Recording
    {
        $this->biomeId = $biomeId;
        return $this;
    }

    /**
     * @return string
     */
    public function getFunctionalTypeName(): ?string
    {
        return $this->functionalTypeName;
    }

    /**
     * @param null|string $functionalTypeName
     * @return Recording
     */
    public function setFunctionalTypeName(?string $functionalTypeName): Recording
    {
        $this->functionalTypeName = $functionalTypeName;
        return $this;
    }

    /**
     * @return int
     */
    public function getFunctionalTypeId(): ?int
    {
        return $this->functionalTypeId;
    }

    /**
     * @param null|int $functionalTypeId
     * @return Recording
     */
    public function setFunctionalTypeId(?int $functionalTypeId): Recording
    {
        $this->functionalTypeId = $functionalTypeId;
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
     * @return null|string
     */
    public function getPath(): ?string
    {
        return $this->path;
    }


    /**
     * @param null|string $path
     * @return Recording
     */
    public function setPath(?string $path): Recording
    {
        $this->path = $path;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getIHO(): ?string
    {
        return $this->iho;
    }


    /**
     * @param null|string $iho
     * @return Recording
     */
    public function setIHO(?string $iho): Recording
    {
        $this->iho = $iho;
        return $this;
    }

    /**
     * @return null|int
     */
    public function getDutyCycleRecording(): ?int
    {
        return $this->duty_cycle_recording;
    }

    /**
     * @param null|int $duty_cycle_recording
     * @return Recording
     */
    public function setDutyCycleRecording(?int $duty_cycle_recording): Recording
    {
        $this->duty_cycle_recording = $duty_cycle_recording;
        return $this;
    }

    /**
     * @return null|int
     */
    public function getDutyCyclePeriod(): ?int
    {
        return $this->duty_cycle_period;
    }

    /**
     * @param null|int $duty_cycle_period
     * @return Recording
     */
    public function setDutyCyclePeriod(?int $duty_cycle_period): Recording
    {
        $this->duty_cycle_period = $duty_cycle_period;
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
        $this->setRecorder($values['recorder_id']);
        $this->setMicrophone($values['microphone_id']);
        $this->setSite($values['site_id']);
        $this->setRecorder($values['recorder_id']);
        $this->setRecorderName($values['recorderName']);
        $this->setMicrophone($values['microphone_id']);
        $this->setMicrophoneName($values['microphoneName']);
        $this->setRecordingGain($values['recording_gain']);
        $this->setDataType($values['data_type']);
        $this->setFileName($values['filename']);
        $this->setFileDate($values['file_date']);
        $this->setFileTime($values['file_time']);
        $this->setFileSize($values['file_size']);
        $this->setBitdepth($values['bitdepth']);
        $this->setChannelNum($values['channel_num']);
        $this->setSamplingRate($values['sampling_rate']);
        $this->setDuration($values['duration']);
        $this->setDoi($values['DOI']);
        $this->setSiteName($values['site_name']);
        $this->setLicense($values['license_id']);
        $this->setLicenseName($values['license_name']);
        $this->setUserId($values['user_id']);
        $this->setUserName($values['user_name']);
        $this->setLabelId($values['label_id']);
        $this->setLabelName($values['label_name']);
        $this->setRealmName($values['realm']);
        $this->setBiomeName($values['biome']);
        $this->setFunctionalTypeName($values['functionalType']);
        $this->setRealmId($values['realm_id']);
        $this->setBiomeId($values['biome_id']);
        $this->setFunctionalTypeId($values['functionalType_id']);
        $this->setLongitude($values['longitude']);
        $this->setLatitude($values['latitude']);
        $this->setType($values['type']);
        $this->setMedium($values['medium']);
        $this->setNote($values['note']);
        $this->setStartDate($values['start_date']);
        $this->setEndDate($values['end_date']);
        $this->setPath($values['path']);
        $this->setIHO($values['iho']);
        $this->setDutyCycleRecording($values['duty_cycle_recording']);
        $this->setDutyCyclePeriod($values['duty_cycle_period']);
        return $this;
    }
}
