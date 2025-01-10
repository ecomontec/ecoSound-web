<?php

namespace BioSounds\Provider;

use BioSounds\Entity\Label;
use BioSounds\Exception\Database\NotFoundException;

class LabelProvider extends BaseProvider
{
    /**
     * @return Label[]
     * @throws \Exception
     */
    public function getBasicList(string $userId): array
    {
        return $this->getList($userId, 'label_id');
    }

    /**
     * @param string $order
     * @return Label[]
     * @throws \Exception
     */
    private function getList(string $userId, string $order = 'name'): array
    {
        $data = [];
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $order)) {
            $order = 'name';
        }
        $this->database->prepareQuery(
            "SELECT * FROM label WHERE creator_id = :userId or type = :type ORDER BY $order"
        );

        $result = $this->database->executeSelect(['userId' => $userId, ':type' => Label::DEFAULT_TYPE_PUBLIC]);

        foreach ($result as $item) {
            $data[] = (new Label())
                ->setId($item['label_id'])
                ->setName($item['name'])
                ->setCreationDate($item['creation_date'])
                ->setCreatorId($item['creator_id'])
                ->setType($item['type']);
        }

        return $data;
    }

    /**
     * @param int $siteId , $userId
     * @return Site|null
     * @throws \Exception
     */
    public function get(int $lblId): ?Label
    {
        $this->database->prepareQuery('SELECT * FROM site WHERE label_id = :labelId');

        if (empty($result = $this->database->executeSelect([':labelId' => $lblId]))) {
            throw new NotFoundException($lblId);
        }

        $result = $result[0];

        return (new Label())
            ->setId($result['label_id'])
            ->setName($result['name'])
            ->setCreationDate($result['creation_date'])
            ->setCreatorId($result['creatorId'])
            ->setType($result['type']);
    }

    public function newLabel(string $userId, string $lblName)
    {
        if (empty($lblName)) {
            return false;
        }

        $this->database->prepareQuery('INSERT INTO label(name, creator_id, type, creation_date) 
        VALUES (:name, :creatorId, :type, now())');
        return $this->database->executeInsert([':name' => $lblName, ':creatorId' => $userId, ':type' => Label::DEFAULT_TYPE_PRIVATE]);
    }

    /**
     * @param int $id
     * @throws \Exception
     */
    public function delete(int $id): void
    {
        $this->database->prepareQuery('DELETE FROM site WHERE ' . Label::PRIMARY_KEY . ' = :id');
        $this->database->executeDelete([':id' => $id]);
    }
}
