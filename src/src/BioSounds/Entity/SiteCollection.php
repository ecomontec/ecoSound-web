<?php

namespace BioSounds\Entity;

use BioSounds\Provider\BaseProvider;

class SiteCollection extends BaseProvider
{
    /**
     * @param string $project_id
     * @param string $site_id
     * @return int|null
     * @throws \Exception
     */
    public function insertByProject(string $project_id, string $site_id)
    {
        $sql = "SELECT $site_id AS site_id, collection_id FROM collection WHERE project_id = $project_id";
        $this->database->prepareQuery($sql);
        $result = $this->database->executeSelect();
        $valuesNames = '';
        $fields = '(site_id,collection_id)';
        foreach ($result as $value) {
            $valuesNames .= "(" . $value['site_id'] . "," . $value['collection_id'] . "),";
        }
        $valuesNames = substr($valuesNames, 0, strlen($valuesNames) - 1) . '';
        $this->database->prepareQuery("INSERT INTO site_collection $fields VALUES $valuesNames");
        return $this->database->executeInsert();
    }

    public function insertByCollection($project_id, $collection_id)
    {
        $sql = "SELECT site_id, COUNT(*) AS count, IF(COUNT(*) = (SELECT COUNT(*) FROM collection WHERE project_id = $project_id) - 1 AND (SELECT COUNT(*) FROM collection WHERE project_id = $project_id) > 0, 1, 0) AS i FROM site_collection WHERE collection_id IN (SELECT collection_id FROM collection WHERE project_id = $project_id) GROUP BY site_id";
        $this->database->prepareQuery($sql);
        $result = $this->database->executeSelect();
        $valuesNames = '';
        $fields = '(site_id,collection_id)';
        $i = 0;
        foreach ($result as $value) {
            if ($value['i'] == 1) {
                $valuesNames .= "(" . $value['site_id'] . "," . $collection_id . "),";
                $i = 1;
            }
        }
        if ($i == 1) {
            $valuesNames = substr($valuesNames, 0, strlen($valuesNames) - 1) . '';
            $this->database->prepareQuery("INSERT INTO site_collection $fields VALUES $valuesNames");
            $this->database->executeInsert();
        }
    }

    public function insert(string $collection_id, string $site_id)
    {
        $fields = '(site_id,collection_id)';
        $valuesNames = "($site_id,$collection_id)";
        $this->database->prepareQuery("INSERT INTO site_collection $fields VALUES $valuesNames");
        return $this->database->executeInsert();
    }

    public function delete(int $collection_id, int $site_id)
    {
        $this->database->prepareQuery('DELETE FROM site_collection WHERE site_id = :siteId AND collection_id =:colId');
        return $this->database->executeDelete([':siteId' => $site_id, ':colId' => $collection_id]);
    }
}
