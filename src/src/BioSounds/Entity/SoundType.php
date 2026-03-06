<?php

namespace BioSounds\Entity;

use BioSounds\Provider\BaseProvider;

class SoundType extends BaseProvider
{
    const TABLE_NAME = 'sound_type';
    const ID = 'sound_type_id';
    const NAME = 'name';
    const TAXON_CLASS = 'taxon_class';
    const TAXON_ORDER = 'taxon_order';

    public function getAll()
    {
        $query = 'SELECT * FROM ' . self::TABLE_NAME . ' ORDER BY ' . self::NAME . ' ASC';
        $this->database->prepareQuery($query);
        $result = $this->database->executeSelect();
        return $result;
    }

    public function getById(int $id)
    {
        $query = 'SELECT * FROM ' . self::TABLE_NAME . ' WHERE ' . self::ID . ' = :id';
        $this->database->prepareQuery($query);
        $result = $this->database->executeSelect([':id' => $id]);
        return !empty($result) ? $result[0] : null;
    }

    public function insert(array $data)
    {
        $fields = [];
        $values = [];
        $placeholders = [];

        foreach ($data as $key => $value) {
            $fields[] = $key;
            $placeholders[] = ':' . $key;
            $values[':' . $key] = $value;
        }

        $query = 'INSERT INTO ' . self::TABLE_NAME . ' (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $placeholders) . ')';
        $this->database->prepareQuery($query);
        return $this->database->executeInsert($values);
    }

    public function update(array $data, int $id)
    {
        $setParts = [];
        $values = [];

        foreach ($data as $key => $value) {
            $setParts[] = $key . ' = :' . $key;
            $values[':' . $key] = $value;
        }

        $values[':id'] = $id;
        $query = 'UPDATE ' . self::TABLE_NAME . ' SET ' . implode(', ', $setParts) . ' WHERE ' . self::ID . ' = :id';
        $this->database->prepareQuery($query);
        return $this->database->executeUpdate($values);
    }

    public function delete(int $id)
    {
        $query = 'DELETE FROM ' . self::TABLE_NAME . ' WHERE ' . self::ID . ' = :id';
        $this->database->prepareQuery($query);
        return $this->database->executeUpdate([':id' => $id]);
    }

    public function getColumns()
    {
        $sql = "SELECT COLUMN_NAME FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = '" . self::TABLE_NAME . "' ORDER BY ordinal_position";
        $this->database->prepareQuery($sql);
        return $this->database->executeSelect();
    }
}