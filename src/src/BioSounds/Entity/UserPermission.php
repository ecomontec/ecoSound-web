<?php

namespace BioSounds\Entity;

use BioSounds\Provider\BaseProvider;

class UserPermission extends BaseProvider
{
    const TABLE_NAME = "user_permission";
    const USER = "user_id";
    const COLLECTION = "collection_id";
    const PERMISSION = "permission_id";

    /**
     * @param int $userId
     * @param int $colId
     * @return int|null
     * @throws \Exception
     */
    public function getUserColPermission(int $userId, int $colId): ?int
    {
        $this->database->prepareQuery(
            'SELECT permission_id FROM user_permission WHERE user_id = :userId AND collection_id = :colId'
        );

        if (empty($result = $this->database->executeSelect([":userId" => $userId, ":colId" => $colId]))) {
            return null;
        }
        return $result[0][self::PERMISSION];
    }

    /**
     * @param int $userId
     * @return array
     * @throws \Exception
     */
    public function getColPermissionsByProject(int $projectId): array
    {
        $this->database->prepareQuery(
            'SELECT collection.collection_id, collection.name, user_permission.permission_id,collection.public ' .
            'FROM collection LEFT JOIN user_permission ON user_permission.collection_id = ' .
            Collection::TABLE_NAME . '.' . Collection::PRIMARY_KEY . ' AND collection.project_id = :projectId ORDER BY ' .
            Collection::TABLE_NAME . '.' . Collection::PRIMARY_KEY
        );
        return $this->database->executeSelect([':projectId' => $projectId]);
    }

    /**
     * @param array $permissionData
     * @return int|null
     * @throws \Exception
     */
    public function insert(array $permissionData): ?int
    {
        if (empty($permissionData)) {
            return false;
        }

        $fields = '( ';
        $valuesNames = '( ';
        $values = [];

        foreach ($permissionData as $key => $value) {
            $fields .= $key;
            $valuesNames .= ':' . $key;
            $values[':' . $key] = $value;
            $fields .= ",";
            $valuesNames .= ",";
        }
        $fields = substr($fields, 0, strlen($fields) - 1) . ' )';
        $valuesNames = substr($valuesNames, 0, strlen($valuesNames) - 1) . ' )';
        $this->database->prepareQuery("INSERT INTO user_permission $fields VALUES $valuesNames");
        return $this->database->executeInsert($values);
    }

    /**
     * @param int $userId
     * @param int $colId
     * @return int|null
     * @throws \Exception
     */
    public function delete(int $userId, int $colId): ?int
    {
        $this->database->prepareQuery('DELETE FROM user_permission WHERE user_id = :userId AND collection_id =:colId');
        return $this->database->executeDelete([':userId' => $userId, ':colId' => $colId]);
    }

    /**
     * @param int $colId
     * @return int|null
     * @throws \Exception
     */
    public function deleteByCollection(int $colId): ?int
    {
        $this->database->prepareQuery('DELETE FROM user_permission WHERE collection_id =:colId');
        return $this->database->executeDelete([':colId' => $colId]);
    }

    public function updatePermission($collection_id)
    {
        $this->database->prepareQuery("SELECT user_id, MAX(permission_id) AS permission_id FROM user_permission WHERE collection_id IN (SELECT collection_id FROM collection WHERE project_id = (SELECT project_id FROM collection WHERE collection_id = $collection_id)) GROUP BY user_id");
        $result = $this->database->executeSelect();
        foreach ($result as $r) {
            $r['permission_id'] = $r['permission_id'] == 4 ? 4 : 3;
            $this->database->prepareQuery("INSERT INTO user_permission (user_id, collection_id, permission_id) VALUES (" . $r['user_id'] . "," . $collection_id . "," . $r['permission_id'] . ")");
            $this->database->executeInsert();
        }
    }
}
