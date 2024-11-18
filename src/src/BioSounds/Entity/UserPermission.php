<?php

namespace BioSounds\Entity;

use BioSounds\Provider\BaseProvider;
use BioSounds\Utils\Auth;

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

        $sql = "INSERT INTO user_permission (collection_id, user_id, permission_id) 
            SELECT c.collection_id, u.user_id, $permissionData[permission_id]
            FROM collection c
            CROSS JOIN user u
            WHERE c.collection_id IN ($permissionData[collection_id])
            AND u.user_id IN ($permissionData[user_id])";

        $this->database->prepareQuery($sql);
        return $this->database->executeInsert();
    }

    /**
     * @param int $userId
     * @param int $colId
     * @return int|null
     * @throws \Exception
     */
    public function delete(string $userId, string $colId): ?int
    {
        $this->database->prepareQuery("DELETE FROM user_permission WHERE user_id IN ($userId) AND collection_id IN ($colId)");
        return $this->database->executeDelete();
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
        $this->database->prepareQuery("SELECT user_id FROM user_permission WHERE collection_id IN (SELECT collection_id FROM collection WHERE project_id = (SELECT project_id FROM collection WHERE collection_id = $collection_id)) GROUP BY user_id");
        $result = $this->database->executeSelect();
        foreach ($result as $r) {
            if ((new User)->isProjectManage($r['user_id'], $collection_id)) {
                $this->database->prepareQuery("INSERT INTO user_permission (user_id, collection_id, permission_id) VALUES (" . $r['user_id'] . "," . $collection_id . "," . 4 . ")");
                $this->database->executeInsert();
            } else if ((new User)->isAllView($r['user_id'], $collection_id)) {
                $this->database->prepareQuery("INSERT INTO user_permission (user_id, collection_id, permission_id) VALUES (" . $r['user_id'] . "," . $collection_id . "," . 3 . ")");
                $this->database->executeInsert();
            }
        }
    }
}
