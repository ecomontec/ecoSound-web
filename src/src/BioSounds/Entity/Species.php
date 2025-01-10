<?php

namespace BioSounds\Entity;

use BioSounds\Provider\BaseProvider;

class Species extends BaseProvider
{
    const TABLE_NAME = 'species';
    const ID = 'species_id';
    const BINOMIAL = 'binomial';
    const GENUS = 'genus';
    const FAMILY = 'family';
    const NAME = 'common_name';
    const ORDER = 'taxon_order';
    const SPECIES_CLASS = 'class';
    const LEVEL = 'level';
    const REGION = 'region';

    public function get()
    {
        $query = 'SELECT * FROM ' . self::TABLE_NAME;
        $this->database->prepareQuery($query);
        $result = $this->database->executeSelect();
        return $result;
    }

    public function getByName(string $name)
    {
        $query = 'SELECT * FROM species WHERE binomial= :name ';
        $this->database->prepareQuery($query);
        $result = $this->database->executeSelect([':name' => $name]);
        return $result;
    }

    /**
     * @param array $names
     * @return array|int
     * @throws \Exception
     */
    public function getList(array $names)
    {
        $query = 'SELECT * FROM ' . self::TABLE_NAME;

        $fields = [];
        if (isset($names)) {
            if (count($names) == 1) {
                $query .= ' WHERE ' . self::BINOMIAL . ' LIKE :binomial OR ' . self::NAME . ' LIKE :name ';
                $fields = [
                    ':binomial' => "%$names[0]%",
                    ':name' => "%$names[0]%"
                ];
            } else {
                $query .= ' WHERE (' . self::BINOMIAL . ' LIKE :binomial1 AND ';
                $query .= self::BINOMIAL . ' LIKE :binomial2) ';
                $query .= 'OR (' . self::NAME . ' LIKE :name1 AND ' . self::NAME . ' LIKE :name2) ';
                $fields = [
                    ':binomial1' => "%$names[0]%",
                    ':binomial2' => "%$names[1]%",
                    ':name1' => "%$names[0]%",
                    ':name2' => "%$names[1]%"
                ];
            }
        }
        $query .= 'ORDER BY ' . self::BINOMIAL . ' ASC LIMIT 0,15';

        $this->database->prepareQuery($query);
        $result = $this->database->executeSelect($fields);

        return $result;
    }
}
