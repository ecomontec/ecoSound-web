<?php

namespace BioSounds\Entity;

use BioSounds\Provider\BaseProvider;

class Microphone extends BaseProvider
{
    const TABLE_NAME = 'microphone';
    const ID = 'microphone_id';
    const NAME = 'name';
    const ELEMENT = 'microphone_element';
    const SENSITIVITY = 'sensitivity';
    const SNR = 'signal_to_noise_ratio';

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

    public function getBasicList()
    {
        $this->database->prepareQuery(
            'SELECT ' . self::ID . ', ' . self::NAME . ' FROM ' . self::TABLE_NAME . ' ORDER BY ' . self::NAME
        );
        return $this->database->executeSelect();
    }

    public function getColumns()
    {
        $sql = "SELECT COLUMN_NAME FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = '" . self::TABLE_NAME . "' ORDER BY ordinal_position";
        $this->database->prepareQuery($sql);
        return $this->database->executeSelect();
    }
}
