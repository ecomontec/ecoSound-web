<?php

namespace BioSounds\Provider;

use BioSounds\Entity\SoundType;

class SoundTypeProvider extends BaseProvider
{
    /**
     * @return array
     * @throws \Exception
     */
    public function getAllList()
    {
        $list = [];

        $this->database->prepareQuery('SELECT * FROM sound_type ORDER BY `name`');

        if (!empty($result = $this->database->executeSelect())) {
            foreach ($result as $soundType) {
                $list[] = (new SoundType())
                    ->setSoundTypeId($soundType['sound_type_id'])
                    ->setName($soundType['name'])
                    ->setTaxonClass($soundType['taxon_class'])
                    ->setTaxonOrder($soundType['taxon_order']);
            }
        }
        return $list;
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getList(string $taxon_class, string $taxon_order)
    {
        $list = [];

        $this->database->prepareQuery('SELECT sound_type_id, `name` FROM sound_type WHERE taxon_class = :taxon_class AND taxon_order = :taxon_order ORDER BY `name`');

        if (!empty($result = $this->database->executeSelect(
            [
                'taxon_class' => $taxon_class,
                'taxon_order' => $taxon_order,
            ]
        ))) {
            foreach ($result as $soundType) {
                $list[] = (new SoundType())
                    ->setSoundTypeId($soundType['sound_type_id'])
                    ->setName($soundType['name']);
            }
        }
        return $list;
    }
}
