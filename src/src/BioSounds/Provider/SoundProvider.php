<?php

namespace BioSounds\Provider;

use BioSounds\Entity\Sound;

class SoundProvider extends BaseProvider
{
    public function get()
    {
        $query = 'SELECT soundscape_component FROM sound GROUP BY soundscape_component';
        $this->database->prepareQuery($query);
        $result = $this->database->executeSelect();
        $data = [];
        foreach ($result as $item) {
            $data[] = (new Sound())
                ->setSoundscapeComponent($item['soundscape_component']);
        }
        return $data;
    }

    public function getAll()
    {
        $query = 'SELECT * FROM sound ORDER BY sound_type';
        $this->database->prepareQuery($query);
        return $this->database->executeSelect();
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