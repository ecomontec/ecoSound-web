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
        $params = [':permission_id' => $permissionData['permission_id']];
        $collection_ids = explode(',', $permissionData['collection_id']);
        $placeholders = [];
        foreach ($collection_ids as $index => $collection_id) {
            $placeholders[] = ":collection_id$index";
            $params[":collection_id$index"] = (int)$collection_id;
        }
        $collection_str = implode(', ', $placeholders);
        $user_ids = explode(',', $permissionData['user_id']);
        $placeholders = [];
        foreach ($user_ids as $index => $user_id) {
            $placeholders[] = ":user_id$index";
            $params[":user_id$index"] = (int)$user_id;
        }
        $user_str = implode(', ', $placeholders);
        $sql = "INSERT INTO user_permission (collection_id, user_id, permission_id) 
            SELECT c.collection_id, u.user_id, :permission_id
            FROM collection c
            CROSS JOIN user u
            WHERE c.collection_id IN ($collection_str)
            AND u.user_id IN ($user_str)";

        $this->database->prepareQuery($sql);
        return $this->database->executeInsert($params);
    }

    /**
     * @param int $userId
     * @param int $colId
     * @return int|null
     * @throws \Exception
     */
    public function delete(string $userId, string $colId): ?int
    {
        $params = [];
        $collection_ids = explode(',', $colId);
        $placeholders = [];
        foreach ($collection_ids as $index => $collection_id) {
            $placeholders[] = ":collection_id$index";
            $params[":collection_id$index"] = (int)$collection_id;
        }
        $collection_str = implode(', ', $placeholders);
        $user_ids = explode(',', $userId);
        $placeholders = [];
        foreach ($user_ids as $index => $user_id) {
            $placeholders[] = ":user_id$index";
            $params[":user_id$index"] = (int)$user_id;
        }
        $user_str = implode(', ', $placeholders);
        $this->database->prepareQuery("DELETE FROM user_permission WHERE user_id IN ($user_str) AND collection_id IN ($collection_str)");
        return $this->database->executeDelete($params);
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

    public function updatePermission($collection_id, $project_id)
    {
        $this->database->prepareQuery("SELECT user_id FROM user_permission WHERE collection_id IN (SELECT collection_id FROM collection WHERE project_id = (SELECT project_id FROM collection WHERE collection_id = :collection_id)) GROUP BY user_id");
        $result = $this->database->executeSelect([':collection_id' => $collection_id]);
        foreach ($result as $r) {
            if ((new User)->isProjectManageCreate($r['user_id'], $project_id)) {
                $this->database->prepareQuery("INSERT INTO user_permission (user_id, collection_id, permission_id) VALUES (:user_id,:collection_id,4)");
                $this->database->executeInsert([':user_id' => $r['user_id'], ':collection_id' => $collection_id]);
            } else if ((new User)->isAllReview($r['user_id'], $collection_id)) {
                $this->database->prepareQuery("INSERT INTO user_permission (user_id, collection_id, permission_id) VALUES (:user_id,:collection_id,2)");
                $this->database->executeInsert([':user_id' => $r['user_id'], ':collection_id' => $collection_id]);
            } else if ((new User)->isAllView($r['user_id'], $collection_id)) {
                $this->database->prepareQuery("INSERT INTO user_permission (user_id, collection_id, permission_id) VALUES (:user_id,:collection_id,1)");
                $this->database->executeInsert([':user_id' => $r['user_id'], ':collection_id' => $collection_id]);
            } else if ((new User)->isAllAccess($r['user_id'], $collection_id)) {
                $this->database->prepareQuery("INSERT INTO user_permission (user_id, collection_id, permission_id) VALUES (:user_id,:collection_id,3)");
                $this->database->executeInsert([':user_id' => $r['user_id'], ':collection_id' => $collection_id]);
            }
        }
    }
}
