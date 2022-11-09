<?php

namespace BioSounds\Provider;

use BioSounds\Entity\Project;
use BioSounds\Exception\Database\NotFoundException;
use BioSounds\Utils\Auth;

class ProjectProvider extends BaseProvider
{
    public function getList(): array
    {
        $sql = "SELECT * FROM project WHERE active = 1";

        $this->database->prepareQuery($sql);
        $result = $this->database->executeSelect();

        $data = [];
        foreach ($result as $item) {
            $data[] = (new Project())
                ->setId($item['project_id'])
                ->setName($item['name'])
                ->setDescription($item['description'])
                ->setCreatorId($item['creator_id'])
                ->setCreationDate($item['creation_date'])
                ->setUrl($item['url'])
                ->setPictureId($item['picture_id'] ? $item['picture_id'] : '')
                ->setPublic($item['public']);
        }

        return $data;
    }

    public function get(int $id): ?Project
    {
        $this->database->prepareQuery('SELECT * FROM project WHERE project_id = :id');

        if (empty($result = $this->database->executeSelect([':id' => $id]))) {
            throw new NotFoundException($id);
        }

        $result = $result[0];

        return (new Project())
            ->setId($result['project_id'])
            ->setName($result['name'])
            ->setDescription($result['description'])
            ->setCreatorId($result['creator_id'])
            ->setCreationDate($result['creation_date'])
            ->setUrl($result['url'])
            ->setPictureId($result['picture_id'] ? $result['picture_id'] : '')
            ->setPublic($result['public']);
    }

    public function getWithPermission($userId, int $disalbe = 1): array
    {
        if (Auth::isUserAdmin()) {
            $sql = "SELECT p.*,MAX( u.permission_id ) AS permission_id FROM project p LEFT JOIN collection c ON p.project_id = c.project_id LEFT JOIN user_permission u ON u.collection_id = c.collection_id AND u.user_id = :userId GROUP BY p.project_id ORDER BY p.name";
        } else {
            $str = $disalbe ? ' WHERE u1.permission_id = 4 ' : ' WHERE u1.permission_id IS NOT NULL';
            $sql = "SELECT p.*,MAX( u2.permission_id ) AS permission_id FROM project p LEFT JOIN collection c ON p.project_id = c.project_id LEFT JOIN user_permission u1 ON u1.collection_id = c.collection_id AND u1.user_id = " . Auth::getUserID() . " LEFT JOIN user_permission u2 ON u2.collection_id = c.collection_id AND u2.user_id = :userId " . $str . " GROUP BY p.project_id ORDER BY p.name";
        }
        $this->database->prepareQuery($sql);
        $result = $this->database->executeSelect([':userId' => $userId]);

        $data = [];
        foreach ($result as $item) {
            $data[] = (new Project())
                ->setId($item['project_id'])
                ->setName($item['name'])
                ->setDescription($item['description'])
                ->setCreatorId($item['creator_id'])
                ->setCreationDate($item['creation_date'])
                ->setUrl($item['url'])
                ->setPictureId($item['picture_id'] ? $item['picture_id'] : '')
                ->setPublic($item['public'])
                ->setCollections((new CollectionProvider())->getByProject($item['project_id'], $userId))
                ->setPermission($item['permission_id'] == null ? 0 : $item['permission_id'])
                ->setActive($item['active']);
        }

        return $data;
    }

    /**
     * @param array $Data
     * @return int
     * @throws \Exception
     */
    public function insert(array $Data): int
    {
        if (empty($Data)) {
            return false;
        }

        $fields = "( ";
        $valuesNames = "( ";
        $values = array();

        foreach ($Data as $key => $value) {
            $fields .= $key;
            $valuesNames .= ":" . $key;
            $values[":" . $key] = $value;
            $fields .= ",";
            $valuesNames .= ",";
        }
        $fields = substr($fields, 0, strlen($fields) - 1) . ' )';
        $valuesNames = substr($valuesNames, 0, strlen($valuesNames) - 1) . ' )';

        $this->database->prepareQuery("INSERT INTO project $fields VALUES $valuesNames");
        return $this->database->executeInsert($values);
    }


    /**
     * @param array $Data
     * @return bool
     * @throws \Exception
     */
    public function update(array $Data): bool
    {
        if (empty($Data)) {
            return false;
        }

        $projectId = $Data["projectId"];
        unset($Data["projectId"]);
        $fields = '';
        $values = [];

        foreach ($Data as $key => $value) {
            $fields .= $key . ' = :' . $key;
            $values[':' . $key] = $value;
            $fields .= ",";
        }
        $fields = substr($fields, 0, strlen($fields) - 1);

        $values[':projectId'] = $projectId;

        $this->database->prepareQuery("UPDATE project SET $fields WHERE project_id = :projectId");
        return $this->database->executeUpdate($values);
    }
}
