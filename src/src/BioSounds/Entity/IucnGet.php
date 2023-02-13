<?php

namespace BioSounds\Entity;

class IucnGet extends AbstractProvider
{
    const TABLE_NAME = "iucn_get";
    const PRIMARY_KEY = "iucn_get_id";
    const NAME = "name";
    const PID = "pid";
    const LEVEL = "level";

    /**
     * @var int
     */
    private $id;


    /**
     * @var int
     */
    private $pid;
    /**
     * @var string
     */
    private $name;

    /**
     * @var int
     */
    private $level;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return IucnGet
     */
    public function setId(int $id): IucnGet
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
     * @return IucnGet
     */
    public function setName(string $name): IucnGet
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return int
     */
    public function getPid(): int
    {
        return $this->pid;
    }

    /**
     * @param int $pid
     * @return site
     */
    public function setPid(int $pid): IucnGet
    {
        $this->pid = $pid;
        return $this;
    }

    /**
     * @return int
     */
    public function getLevel(): int
    {
        return $this->level;
    }

    /**
     * @param int $level
     * @return IucnGet
     */
    public function setLevel(string $level): IucnGet
    {
        $this->level = $level;
        return $this;
    }

    public function getIucnGets(int $pid = 0):array
    {
        $this->database->prepareQuery("SELECT * FROM iucn_get WHERE pid = $pid ORDER BY `name`");
        return $this->database->executeSelect();
    }
    public function getAllIucnGets():array
    {
        $this->database->prepareQuery("SELECT * FROM iucn_get ORDER BY `name`");
        return $this->database->executeSelect();
    }
}
