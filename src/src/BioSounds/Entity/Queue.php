<?php

namespace BioSounds\Entity;

use BioSounds\Provider\BaseProvider;

class Queue extends BaseProvider
{
    const TABLE_NAME = "queue";
    const PRIMARY_KEY = "queue_id";

    public function getById($id): array
    {
        if (isset($_SESSION['index_id'])) {
            $_SESSION['index_id'] = $_SESSION['index_id'] + 1;
            return $_SESSION['index_id'];
        } else {
            $this->database->prepareQuery("SELECT * FROM queue WHERE queue_id = " . $id);
            return $this->database->executeSelect()[0];
        }
    }

    public function insert(array $data)
    {
        if (empty($data)) {
            return false;
        }

        $fields = "( ";
        $valuesNames = "( ";
        $values = array();

        foreach ($data as $key => $value) {
            $fields .= $key;
            $valuesNames .= ":" . $key;
            $values[":" . $key] = $value;
            $fields .= ",";
            $valuesNames .= ",";
        }
        $fields = substr($fields, 0, strlen($fields) - 1) . ' )';
        $valuesNames = substr($valuesNames, 0, strlen($valuesNames) - 1) . ' )';

        $this->database->prepareQuery("INSERT INTO queue $fields VALUES $valuesNames");
        return $this->database->executeInsert($values);
    }

    public function update(array $data)
    {
        if (empty($data)) {
            return false;
        }

        $queue_id = $data["queue_id"];
        unset($data["queue_id"]);
        $fields = '';
        $values = [];

        foreach ($data as $key => $value) {
            $fields .= $key . ' = :' . $key;
            $values[':' . $key] = $value;
            $fields .= ",";
        }
        $fields = substr($fields, 0, strlen($fields) - 1);

        $values[':queue_id'] = $queue_id;
        $this->database->prepareQuery("UPDATE queue SET $fields WHERE queue_id = :queue_id");
        return $this->database->executeUpdate($values);
    }
}
