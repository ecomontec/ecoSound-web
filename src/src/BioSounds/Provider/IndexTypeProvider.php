<?php

namespace BioSounds\Provider;

use BioSounds\Entity\IndexType;

class IndexTypeProvider extends BaseProvider
{
    public function get(int $indexId): IndexType
    {
        $query = "SELECT * FROM index_type WHERE index_id = :indexId";

        $this->database->prepareQuery($query);
        if (empty($result = $this->database->executeSelect([':indexId' => $indexId]))) {
            throw new \Exception("Index Type $indexId doesn't exist.");
        }
        return (new IndexType())->createFromValues($result[0]);
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getList(): array
    {
        $list = [];

        $this->database->prepareQuery('SELECT * FROM index_type ORDER BY name');

        if (!empty($result = $this->database->executeSelect())) {
            foreach ($result as $indexType) {
                $list[] = (new IndexType())
                    ->setIndexId($indexType['index_id'])
                    ->setName($indexType['name'])
                    ->setParam($indexType['param'])
                    ->setDescription($indexType['description'])
                    ->setURL($indexType['URL']);
            }
        }
        return $list;
    }
}
