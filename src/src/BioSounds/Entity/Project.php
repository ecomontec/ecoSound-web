<?php

namespace BioSounds\Entity;

use BioSounds\Provider\BaseProvider;

class Project extends BaseProvider
{

    const TABLE_NAME = "project";
    const PRIMARY_KEY = "project_id";


    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $description;

    /**
     * @var int
     */
    private $creator_id;

    /**
     * @var string
     */
    private $creation_date;

    /**
     * @var string
     */
    private $url;

    /**
     * @var int
     */
    private $picture_id;

    /**
     * @var int
     */
    private $public;

    /**
     * @var array
     */
    private $collections;

    /**
     * @var int
     */
    private $permission;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return Project
     */
    public function setId(int $id): Project
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
     * @return Project
     */
    public function setName(string $name): Project
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return Project
     */
    public function setDescription(string $description): Project
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return string
     */
    public function getCreator(): string
    {
        $name = (new User())->getFullName($this->creator_id);
        return $name;
    }

    /**
     * @return int
     */
    public function getCreatorId(): int
    {
        return $this->creator_id;
    }

    /**
     * @param null|string $creator_id
     * @return Project
     */
    public function setCreatorId(?string $creator_id): Project
    {
        $this->creator_id = $creator_id;
        return $this;
    }

    /**
     * @return null|string
     */
    public function getCreationDate(): ?string
    {
        return $this->creation_date;
    }

    /**
     * @param null|string $creation_date
     * @return Project
     */
    public function setCreationDate(?string $creation_date): Project
    {
        $this->creation_date = $creation_date;
        return $this;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @param string $url
     * @return Project
     */
    public function setUrl(string $url): Project
    {
        $this->url = $url;
        return $this;
    }
    /**
     * @return string
     */
    public function getPictureId(): string
    {
        return $this->picture_id;
    }

    /**
     * @param string $picture_id
     * @return Project
     */
    public function setPictureId(string $picture_id): Project
    {
        $this->picture_id = $picture_id;
        return $this;
    }
    /**
     * @return bool
     */
    public function getPublic(): bool
    {
        return $this->public;
    }

    /**
     * @param bool $public
     * @return Project
     */
    public function setPublic(bool $public): Project
    {
        $this->public = $public;
        return $this;
    }

    /**
     * @return array
     */
    public function getCollections(): array
    {
        return $this->collections;
    }

    /**
     * @param array $collections
     * @return Project
     */
    public function setCollections(array $collections): Project
    {
        $this->collections = $collections;
        return $this;
    }

    /**
     * @return int
     */
    public function getPermission(): int
    {
        return $this->permission;
    }

    /**
     * @param null|string $permission
     * @return Project
     */
    public function setPermission(?string $permission): Project
    {
        $this->permission = $permission;
        return $this;
    }
}
