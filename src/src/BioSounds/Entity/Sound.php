<?php

namespace BioSounds\Entity;

use BioSounds\Provider\BaseProvider;

class Sound extends BaseProvider
{
    const TABLE_NAME = 'sound';
    const ID = 'sound_id';
    const SOUNDSCAPE_COMPONENT = 'soundscape_component';
    const SOUND_TYPE = 'sound_type';

    private $sound_id;
    private $soundscape_component;
    private $sound_type;

    public function getSoundId()
    {
        return $this->sound_id;
    }

    public function setSoundId($sound_id)
    {
        $this->sound_id = $sound_id;
        return $this;
    }

    public function getSoundscapeComponent()
    {
        return $this->soundscape_component;
    }

    public function setSoundscapeComponent($soundscape_component)
    {
        $this->soundscape_component = $soundscape_component;
        return $this;
    }

    public function getSoundType()
    {
        return $this->sound_type;
    }

    public function setSoundType($sound_type)
    {
        $this->sound_type = $sound_type;
        return $this;
    }

    public function getAll()
    {
        $query = 'SELECT * FROM ' . self::TABLE_NAME . ' ORDER BY ' . self::SOUNDSCAPE_COMPONENT . ' ASC';
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
