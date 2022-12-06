<?php

namespace BioSounds\Provider;

use BioSounds\Entity\Sound;

class SoundProvider extends BaseProvider
{
    public function get()
    {
        $query = 'SELECT * FROM sound ORDER BY sound_id';
        $this->database->prepareQuery($query);
        $result = $this->database->executeSelect();
        $data = [];
        foreach ($result as $item) {
            $data[] = (new Sound())
                ->setSoundId($item['sound_id'])
                ->setPhony($item['phony'])
                ->setSoundType($item['sound_type']);
        }
        return $data;
    }

    /**
     * @param $id
     * @return array|int
     * @throws \Exception
     */
    public function delete($id)
    {
        $this->database->prepareQuery('DELETE FROM sound WHERE sound_id = :id' );
        return $this->database->executeDelete([':id' => $id]);
    }
}