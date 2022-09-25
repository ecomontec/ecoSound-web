<?php

namespace BioSounds\Provider;

use BioSounds\Entity\Collection;
use BioSounds\Exception\Database\NotFoundException;
use BioSounds\Utils\Auth;

class CollectionProvider extends BaseProvider
{
    public function getCollectionPagesByPermission(): array
    {
        $sql = "SELECT c.* FROM collection c ";
        if (!Auth::isUserLogged()) {
            $sql = $sql . 'WHERE c.public = 1 ';
        } elseif (!Auth::isUserAdmin()) {
            $sql = $sql . 'WHERE c.public = 1 OR c.collection_id IN (SELECT up.collection_id FROM user_permission up, permission p WHERE up.permission_id = p.permission_id AND (p.name = "Access" OR p.name = "View" OR p.name = "Review") AND up.user_id = ' . Auth::getUserID() . ') ';
        }
        $sql = $sql . 'ORDER BY c.collection_id ';
        $this->database->prepareQuery($sql);

        $result = $this->database->executeSelect();

        $data = [];
        foreach ($result as $item) {
            $data[] = (new Collection())
                ->setId($item['collection_id'])
                ->setName($item['name'])
                ->setUserId($item['user_id'])
                ->setDoi($item['doi'])
                ->setNote($item['note'])
                ->setProject($item['project_id'])
                ->setCreationDate($item['creation_date'])
                ->setPublic($item['public'])
                ->setView($item['view']);
        }
        return $data;
    }

    /**
     * @param string $order
     * @return Collection[]
     * @throws \Exception
     */
    public function getList(string $order = 'name'): array
    {
        $data = [];
        $this->database->prepareQuery("SELECT * FROM collection ORDER BY $order");
        $result = $this->database->executeSelect();

        foreach ($result as $item) {
            $data[] = (new Collection())
                ->setId($item['collection_id'])
                ->setName($item['name'])
                ->setUserId($item['user_id'])
                ->setDoi($item['doi'])
                ->setNote($item['note'])
                ->setProject($item['project_id'])
                ->setCreationDate($item['creation_date'])
                ->setPublic($item['public'])
                ->setView($item['view']);
        }

        return $data;
    }

    /**
     * @param int $id
     * @return Collection|null
     * @throws \Exception
     */
    public function get(int $id): ?Collection
    {
        $this->database->prepareQuery('SELECT * FROM collection WHERE collection_id = :id');

        if (empty($result = $this->database->executeSelect([':id' => $id]))) {
            throw new NotFoundException($id);
        }

        $result = $result[0];

        return (new Collection())
            ->setId($result['collection_id'])
            ->setName($result['name'])
            ->setUserId($result['user_id'])
            ->setDoi($result['doi'])
            ->setNote($result['note'])
            ->setProject($result['project_id'])
            ->setCreationDate($result['creation_date'])
            ->setPublic($result['public'])
            ->setView($result['view']);
    }


    /**
     * @param string $order
     * @return Collection[]
     * @throws \Exception
     */
    public function getAccessedList(int $userId): array
    {
        $data = [];
        $this->database->prepareQuery('SELECT * FROM collection WHERE collection_id IN ( SELECT up.collection_id FROM user_permission up, permission p WHERE up.permission_id = p.permission_id AND (p.name = "Access" OR p.name = "View" OR p.name = "Review") AND up.user_id = :userId) ORDER BY name');

        $result = $this->database->executeSelect([':userId' => $userId]);

        foreach ($result as $item) {
            $data[] = (new Collection())
                ->setId($item['collection_id'])
                ->setName($item['name'])
                ->setUserId($item['user_id'])
                ->setDoi($item['doi'])
                ->setNote($item['note'])
                ->setProject($item['project_id'])
                ->setCreationDate($item['creation_date'])
                ->setPublic($item['public'])
                ->setView($item['view']);
        }

        return $data;
    }
    /**
     * @param int $id
     * @throws \Exception
     */
    public function delete(int $id): void
    {
        $this->database->prepareQuery('DELETE FROM ' . Collection::TABLE_NAME . ' WHERE collection_id = :id');
        $this->database->executeDelete([':id' => $id]);
    }
}
