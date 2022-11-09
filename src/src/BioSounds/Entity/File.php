<?php

namespace BioSounds\Entity;

class File
{
    const STATUS_SUCCESS = 0;
    const STATUS_READY = 1;
    const STATUS_IN_PROGRESS = 2;
    const STATUS_ERROR = 9;

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
    private $user;

    /**
     * @var string
     */
    private $date;

    /**
     * @var string
     */
    private $time;

    /**
     * @var string
     */
    private $error;

    /**
     * @var int
     */
    private $status;

    /**
     * @var string
     */
    private $path;

    /**
     * @var int
     */
    private $recording = null;

    /**
     * @var int
     */
    private $species;

    /**
     * @var int
     */
    private $soundType;

    /**
     * @var string
     */
    private $subtype;

    /**
     * @var string
     */
    private $rating;

    /**
     * @var string
     */
    private $doi;

    /**
     * @var int
     */
    private $license;

    /**
     * @return null|int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return File
     */
    public function setId(int $id): File
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
     * @return File
     */
    public function setName($name): File
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
     * @return File
     */
    public function setCollection(int $collection): File
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
     * @return File
     */
    public function setDirectory(int $directory): File
    {
        $this->directory = $directory;
        return $this;
    }

    /**
     * @return int
     */
    public function getSite(): ?int
    {
        return $this->site;
    }

    /**
     * @param int $site
     * @return File
     */
    public function setSite(?int $site): File
    {
        $this->site = $site;
        return $this;
    }

    /**
     * @return int
     */
    public function getSensor(): int
    {
        return $this->sensor;
    }

    /**
     * @param int $sensor
     * @return File
     */
    public function setSensor(int $sensor): File
    {
        $this->sensor = $sensor;
        return $this;
    }

    /**
     * @return int
     */
    public function getUser(): int
    {
        return $this->user;
    }

    /**
     * @param int $user
     * @return File
     */
    public function setUser(int $user): File
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getDate(): ?string
    {
        return $this->date;
    }

    /**
     * @param null|string $date
     * @return File
     */
    public function setDate(?string $date): File
    {
        $this->date = $date;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getTime(): ?string
    {
        return $this->time;
    }

    /**
     * @param null|string $time
     * @return File
     */
    public function setTime(?string $time): File
    {
        $this->time = $time;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getError(): ?string
    {
        return $this->error;
    }

    /**
     * @param string $error
     * @return File
     */
    public function setError(?string $error): File
    {
        $this->error = $error;
        return $this;
    }

    /**
     * @return null|int
     */
    public function getStatus(): ?int
    {
        return $this->status;
    }

    /**
     * @param int $status
     * @return File
     */
    public function setStatus(int $status): File
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @param string $path
     * @return File
     */
    public function setPath(string $path): File
    {
        $this->path = $path;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getRecording(): ?int
    {
        return $this->recording;
    }

    /**
     * @param int $recording
     * @return File
     */
    public function setRecording(?int $recording): File
    {
        $this->recording = $recording;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getSpecies(): ?int
    {
        return $this->species;
    }

    /**
     * @param int|null $species
     * @return File
     */
    public function setSpecies(?int $species): File
    {
        $this->species = $species;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getSoundType(): ?int
    {
        return $this->soundType;
    }

    /**
     * @param int|null $soundType
     * @return File
     */
    public function setSoundType(?int $soundType): File
    {
        $this->soundType = $soundType;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getSubtype(): ?string
    {
        return $this->subtype;
    }

    /**
     * @param null|string $subtype
     * @return File
     */
    public function setSubtype(?string $subtype): File
    {
        $this->subtype = $subtype;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getRating(): ?string
    {
        return $this->rating;
    }

    /**
     * @param null|string $rating
     * @return File
     */
    public function setRating(?string $rating): File
    {
        $this->rating = $rating;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getDoi(): ?string
    {
        return $this->doi;
    }


    /**
     * @param null|string $doi
     * @return File
     */
    public function setDoi(?string $doi): File
    {
        $this->doi = $doi;
        return $this;
    }

    /**
     * @return null|integer
     */
    public function getLicense(): ?int
    {
        return $this->license;
    }


    /**
     * @param null|integer $license
     * @return File
     */
    public function setLicense(?int $license): File
    {
        $this->license = $license;
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
     * @return File
     */
    public function setType(?string $type): File
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
     * @return File
     */
    public function setMedium(?string $medium): File
    {
        $this->medium = $medium;
        return $this;
    }

    public function getDatabaseValues(): array
    {
        return [
            ':path' => $this->getPath(),
            ':filename' => $this->getName(),
            ':date' => $this->getDate(),
            ':time' => $this->getTime(),
            ':site' => $this->getSite(),
            ':collection' => $this->getCollection(),
            ':directory' => $this->getDirectory(),
            ':sensor' => $this->getSensor(),
            ':recording' => $this->getRecording(),
            ':user' => $this->getUser(),
            ':species' => $this->getSpecies(),
            ':soundType' => $this->getSoundType(),
            ':subtype' => $this->getSubtype(),
            ':rating' => $this->getRating(),
            ':doi' => $this->getDoi(),
            ':license' => $this->getLicense(),
            ':type' => $this->getType(),
            ':medium' => $this->getMedium(),
        ];
    }
}
