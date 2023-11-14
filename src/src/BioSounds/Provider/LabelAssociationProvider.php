<?php

namespace BioSounds\Provider;

use BioSounds\Entity\Label;
use BioSounds\Exception\Database\NotFoundException;

class LabelAssociationProvider extends BaseProvider
{
    /**
     * @param int $recId
     * @param int $loggedUserId
     * @return Label or NULL
     * @throws \Exception
     */
    public function getUserLabel(int $recId, int $loggedUserId): ?Label
    {
        $data = [];
        $query =
            "SELECT l.label_id, l.name, l.creation_date, l.creator_id, l.type 
            FROM label l, label_association la
            WHERE la.label_id = l.label_id 
            AND la.user_id = :user_id
            AND la.recording_id = :recording_id ";

        $this->database->prepareQuery($query);
        $result = $this->database->executeSelect([':recording_id' => $recId, ':user_id' => $loggedUserId]);

        foreach ($result as $item) {
            $data[] = (new Label())
                ->setId($item['label_id'])
                ->setName($item['name'])
                ->setCreationDate($item['creation_date'])
                ->setCreatorId($item['creator_id'])
                ->setType($item['type']);
        }

        // TODO: 
        if (!empty($data)) {
            return $data[0];
        } else {
            // WORKAROUND: 1, 'not analysed'
            return (new Label())->setId(1)->setName('not analysed')->setCreatorId(-1)->setType('public');
        }
    }

    public function setEntry(array $repData)
    {
        if (empty($repData)) {
            return false;
        }

        $values = [];
        foreach ($repData as $key => $value) {
            $values[':' . $key] = $value;
        }

        $this->database->prepareQuery('REPLACE INTO label_association(recording_id, user_id, label_id) 
        VALUES (:recording_id, :user_id, :label_id)');
        return $this->database->executeUpdate($values);
    }

    /**
     * @param int $id
     * @throws \Exception
     */
    public function delete(string $id): void
    {
        $this->database->prepareQuery("DELETE FROM label_association WHERE recording_id IN ($id)");
        $this->database->executeDelete();
    }

}
