<?php

namespace BioSounds\Entity;

use BioSounds\Provider\BaseProvider;

class Collection extends BaseProvider
{

    const TABLE_NAME = "collection";
    const PRIMARY_KEY = "collection_id";
    const NAME = "name";
    const AUTHOR = "author";
    const DOI = "doi";
    const NOTE = "note";
    const SPHERE = "sphere";
    const GALLERY_VIEW = 'gallery';
    const LIST_VIEW = 'list';

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
    private $user_id;

    /**
     * @var string
     */
    private $doi;

    /**
     * @var string
     */
    private $note;

    /**
     * @var string
     */
    private $sphere;

    /**
     * @var string
     */
    private $external_recording_url;

    /**
     * @var string
     */
    private $project_url;

    /**
     * @var string
     */
    private $view = self::GALLERY_VIEW;

    /**
     * @var int
     */
    private $project;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return Collection
     */
    public function setId(int $id): Collection
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
     * @return Collection
     */
    public function setName(string $name): Collection
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getAuthor(): string
    {
        $author = (new User())->getFullName($this->user_id);
        return $author;
    }

    /**
     * @return null|string
     */
    public function getOrcid(): ?string
    {
        $orcid = (new User())->getOrcid($this->user_id);
        return $orcid;
    }

    public function getEmail(): ?string
    {
        $orcid = (new User())->getEmail($this->user_id);
        return $orcid;
    }

    /**
     * @param int $user_id
     * @return Collection
     */
    public function setUserId(string $user_id): Collection
    {
        $this->user_id = $user_id;
        return $this;
    }

    /**
     * @return int
     */
    public function getProject(): int
    {
        return $this->project;
    }

    /**
     * @param int $id
     * @return Collection
     */
    public function setProject(int $id): Collection
    {
        $this->project = $id;
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
     * @param int $permission
     * @return Collection
     */
    public function setPermission(int $permission): Collection
    {
        $this->permission = $permission;
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
     * @return Collection
     */
    public function setDoi(?string $doi): Collection
    {
        $this->doi = $doi;
        return $this;
    }

    /**
     * @return string
     */
    public function getSphere(): string
    {
        return $this->sphere;
    }

    /**
     * @param string $sphere
     * @return Collection
     */
    public function setSphere(string $sphere): Collection
    {
        $this->sphere = $sphere;
        return $this;
    }

    /**
     * @return string
     */
    public function getRecordingUrl(): ?string
    {
        return $this->external_recording_url;
    }

    /**
     * @param string $external_recording_url
     * @return Collection
     */
    public function setRecordingUrl(?string $external_recording_url): Collection
    {
        $this->external_recording_url = $external_recording_url;
        return $this;
    }

    /**
     * @return string
     */
    public function getProjectUrl(): ?string
    {
        return $this->project_url;
    }

    /**
     * @param string $project_url
     * @return Collection
     */
    public function setProjectUrl(?string $project_url): Collection
    {
        $this->project_url = $project_url;
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
     * @return Collection
     */
    public function setNote(?string $note): Collection
    {
        $this->note = $note;
        return $this;
    }

    /**
     * @return string
     */
    public function getView(): string
    {
        return $this->view;
    }

    /**
     * @param string $view
     * @return Collection
     */
    public function setView(string $view): Collection
    {
        $this->view = $view;
        return $this;
    }

    /**
     * @return string
     */
    public function getCreationDate(): string
    {
        return $this->creationDate;
    }

    /**
     * @param string $creationDate
     * @return Collection
     */
    public function setCreationDate(string $creationDate): Collection
    {
        $this->creationDate = $creationDate;
        return $this;
    }

    /**
     * @return bool
     */
    public function getPublicAccess(): bool
    {
        return $this->public_access;
    }

    /**
     * @param bool $public_access
     * @return Collection
     */
    public function setPublicAccess(bool $public_access): Collection
    {
        $this->public_access = $public_access;
        return $this;
    }

    /**
     * @return bool
     */
    public function getPublicTags(): bool
    {
        return $this->public_tags;
    }

    /**
     * @param bool $public_tags
     * @return Collection
     */
    public function setPublicTags(bool $public_tags): Collection
    {
        $this->public_tags = $public_tags;
        return $this;
    }


    /**
     * @param array $collData
     * @return bool
     * @throws \Exception
     */
    public function insertColl(array $collData): int
    {
        if (empty($collData)) {
            return false;
        }

        $fields = "( ";
        $valuesNames = "( ";
        $values = array();

        foreach ($collData as $key => $value) {
            $fields .= $key;
            $valuesNames .= ":" . $key;
            $values[":" . $key] = $value;

            if ($key !== "user_id") {
                $fields .= ", ";
                $valuesNames .= ", ";
            }
        }
        $fields .= " )";
        $valuesNames .= " )";

        $this->database->prepareQuery("INSERT INTO collection $fields VALUES $valuesNames");
        return $this->database->executeInsert($values);
    }


    /**
     * @param array $collData
     * @return bool
     * @throws \Exception
     */
    public function updateColl(array $collData): bool
    {
        if (empty($collData)) {
            return false;
        }

        $collId = $collData["collId"];
        unset($collData["collId"]);
        $fields = '';
        $values = [];

        foreach ($collData as $key => $value) {
            $fields .= $key . ' = :' . $key;
            $values[':' . $key] = $value;
            $fields .= ",";
        }
        $fields = substr($fields, 0, strlen($fields) - 1);

        $values[':collectionId'] = $collId;
        $this->database->prepareQuery("UPDATE collection SET $fields WHERE collection_id = :collectionId");
        return $this->database->executeUpdate($values);
    }

    public function isValid($project_id, $str, $collection_id)
    {

        $sql = "SELECT * FROM collection WHERE project_id = :project_id AND `name` = :name";
        if (isset($collection_id)) {
            $sql = $sql . " and collection_id != $collection_id";
        }
        $this->database->prepareQuery($sql);
        $params = [
            ':project_id' => $project_id,
            ':name' => $str
        ];
        if (isset($collection_id)) {
            $params[':collection_id'] = $collection_id;
        }
        $result = $this->database->executeSelect($params);
        if (count($result) > 0) {
            return true;
        }
        return false;
    }
}
