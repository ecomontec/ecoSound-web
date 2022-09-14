<?php

namespace BioSounds\Entity;

class Explore extends AbstractProvider
{
    const TABLE_NAME = "explore";
    const PRIMARY_KEY = "explore_id";
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
     * @return Explore
     */
    public function setId(int $id): Explore
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
     * @return Explore
     */
    public function setName(string $name): Explore
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
    public function setPid(int $pid): Explore
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
     * @return Explore
     */
    public function setLevel(string $level): Explore
    {
        $this->level = $level;
        return $this;
    }

    public function getExplores(int $pid = 0):array
    {
        $this->database->prepareQuery("SELECT * FROM explore WHERE pid = $pid ORDER BY `name`");
        return $this->database->executeSelect();
    }
    public function getAllExplores():array
    {
        $this->database->prepareQuery("SELECT * FROM explore ORDER BY `name`");
        return $this->database->executeSelect();
    }
}
