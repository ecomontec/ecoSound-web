<?php

namespace BioSounds\Entity;

use BioSounds\Provider\BaseProvider;

class Api extends BaseProvider
{
    public function getApis()
    {
        $this->database->prepareQuery('SELECT * FROM api WHERE shared = 1 ORDER BY api');
        $result = $this->database->executeSelect();
        return $result;
    }

    public function isValid(string $api)
    {
        $sql = "SELECT * FROM api WHERE `api` = :api";
        $this->database->prepareQuery($sql);
        $result = $this->database->executeSelect([":api" => $api]);
        if (count($result) > 0) {
            return true;
        }
        return false;
    }

    public function isValidById(int $api_id)
    {
        $sql = "SELECT * FROM api WHERE `api_id` = :api_id";
        $this->database->prepareQuery($sql);
        $result = $this->database->executeSelect([":api_id" => $api_id]);
        if (count($result) > 0) {
            return true;
        }
        return false;
    }

    public function insertApi(array $apiData): int
    {
        if (empty($apiData)) {
            return false;
        }

        $fields = "( ";
        $valuesNames = "( ";
        $values = array();

        foreach ($apiData as $key => $value) {
            $fields .= $key;
            $valuesNames .= ":" . $key;
            $values[":" . $key] = $value;
            $fields .= ",";
            $valuesNames .= ",";
        }
        $fields = substr($fields, 0, strlen($fields) - 1) . ' )';
        $valuesNames = substr($valuesNames, 0, strlen($valuesNames) - 1) . ' )';

        $this->database->prepareQuery("INSERT INTO api $fields VALUES $valuesNames");
        return $this->database->executeInsert($values);
    }

    public function updateApi(array $apiData): bool
    {
        if (empty($apiData)) {
            return false;
        }

        $api = $apiData["api"];
        unset($apiData["api"]);
        $fields = '';
        $values = [];

        foreach ($apiData as $key => $value) {
            $fields .= $key . ' = :' . $key;
            $values[':' . $key] = $value;
            $fields .= ",";
        }
        $fields = substr($fields, 0, strlen($fields) - 1);
        $values[':api'] = $api;

        $this->database->prepareQuery("UPDATE api SET $fields WHERE api = :api");
        return $this->database->executeUpdate($values);
    }
}
