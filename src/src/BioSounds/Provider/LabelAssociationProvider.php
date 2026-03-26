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

        // Return null if no label association exists (no default label)
        if (!empty($data)) {
            return $data[0];
        } else {
            return null;
        }
    }

    /**
     * Get user labels for multiple recordings in a single query
     * @param array $recordingIds Array of recording IDs
     * @param int $loggedUserId
     * @return array Map of recording_id => Label (or null if no association)
     * @throws \Exception
     */
    public function getUserLabels(array $recordingIds, int $loggedUserId): array
    {
        if (empty($recordingIds)) {
            return [];
        }

        $labels = [];
        
        // Initialize all recordings with null
        foreach ($recordingIds as $recId) {
            $labels[$recId] = null;
        }

        // Build IN clause safely
        $placeholders = [];
        $params = [':user_id' => $loggedUserId];
        foreach ($recordingIds as $index => $recId) {
            $placeholders[] = ':rec_id_' . $index;
            $params[':rec_id_' . $index] = $recId;
        }
        $inClause = implode(',', $placeholders);

        $query = "SELECT l.label_id, l.name, l.creation_date, l.creator_id, l.type, la.recording_id
            FROM label l, label_association la
            WHERE la.label_id = l.label_id 
            AND la.user_id = :user_id
            AND la.recording_id IN ($inClause)";

        $this->database->prepareQuery($query);
        $result = $this->database->executeSelect($params);

        // Map results by recording_id
        foreach ($result as $item) {
            $labels[$item['recording_id']] = (new Label())
                ->setId($item['label_id'])
                ->setName($item['name'])
                ->setCreationDate($item['creation_date'])
                ->setCreatorId($item['creator_id'])
                ->setType($item['type']);
        }

        return $labels;
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
     * Delete label association for a specific user and recording
     * @param int $recordingId
     * @param int $userId
     * @return mixed
     */
    public function deleteUserEntry(int $recordingId, int $userId)
    {
        $this->database->prepareQuery('DELETE FROM label_association WHERE recording_id = :recording_id AND user_id = :user_id');
        return $this->database->executeDelete([':recording_id' => $recordingId, ':user_id' => $userId]);
    }

    /**
     * @param int $id
     * @throws \Exception
     */
    public function delete(string $id): void
    {
        $params = [];
        $ids = explode(',', $id);
        $placeholders = [];
        foreach ($ids as $index => $value) {
            $placeholders[] = ":id$index";
            $params[":id$index"] = (int)$value;
        }
        $id_str = implode(', ', $placeholders);
        $this->database->prepareQuery("DELETE FROM label_association WHERE recording_id IN ($id_str)");
        $this->database->executeDelete($params);
    }

}
