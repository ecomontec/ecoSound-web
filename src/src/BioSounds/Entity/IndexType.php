<?php

namespace BioSounds\Entity;


class IndexType
{
    const TABLE_NAME = 'index_log';

    /**
     * @var int
     */
    private $index_id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $param;

    /**
     * @var string
     */
    private $description;

    /**
     * @var string
     */
    private $URL;

    /**
     * @return int
     */
    public function getIndexId(): int
    {
        return $this->index_id;
    }

    /**
     * @param int $index_id
     * @return IndexType
     */
    public function setIndexId(int $index_id): IndexType
    {
        $this->index_id = $index_id;
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
     * @return IndexType
     */
    public function setName(string $name): IndexType
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getParam(): string
    {
        return $this->param;
    }

    /**
     * @param string $param
     * @return IndexType
     */
    public function setParam(string $param): IndexType
    {
        $this->param = $param;
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
     * @return IndexType
     */
    public function setDescription(string $description): IndexType
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return string
     */
    public function getURL(): string
    {
        return $this->URL;
    }

    /**
     * @param string $URL
     * @return IndexType
     */
    public function setURL(string $URL): IndexType
    {
        $this->URL = $URL;
        return $this;
    }

    /**
     * @return array
     */
    public function getTypeValues(): ?IndexType
    {
        return [
            ':index_id' => $this->getIndexId(),
            ':name' => $this->getName(),
            ':param' => $this->getParam(),
            ':description' => $this->getDescription(),
            ':URL' => $this->getURL(),
        ];
    }

    /**
     * @param array $values
     * @return $this
     */
    public function createFromValues(array $values)
    {
        $this->setIndexId($values['index_id']);
        $this->setName($values['name']);
        $this->setParam($values['param']);
        $this->setDescription($values['description']);
        $this->setURL($values['URL']);
        return $this;
    }
}
