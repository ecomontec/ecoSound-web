<?php

namespace BioSounds\Entity;

use BioSounds\Provider\BaseProvider;

class LabelAssociation extends BaseProvider
{
    const TABLE_NAME = "label_association";
    const LABEL_ID = "label_id";
    const RECORDING_ID = "recording_id";
    const USER_ID = 'user_id';

    /**
     * @var int
     */
    private $labelId;

    /**
     * @var int
     */
    private $recordingId;

    /**
     * @var int
     */
    private $userId;

    /**
     * @return int
     */
    public function getLabelId(): int
    {
        return $this->labelId;
    }

    /**
     * @param int $labelId
     * @return labelAssoc
     */
    public function setLabelId(int $labelId): LabelAssociation
    {
        $this->labelId = $labelId;
        return $this;
    }

    /**
     * @return int
     */
    public function getRecordingId(): int
    {
        return $this->recordingId;
    }

    /**
     * @param int $recordingId
     * @return labelAssoc
     */
    public function setRecordingId(int $recordingId): LabelAssociation
    {
        $this->recordingId = $recordingId;
        return $this;
    }

    /**
     * @return int
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * @param string $userId
     * @return labelAssoc
     */
    public function setCreationDate(int $userId): LabelAssociation
    {
        $this->userId = $userId;
        return $this;
    }

    /**
     * @param array $labelAssocData
     * @return bool
     * @throws \Exception
     */
    public function insert(array $labelAssocData): bool
    {
        if (empty($labelAssocData)) {
            return false;
        }

        $fields = "( ";
        $valuesNames = "( ";
        $values = array();

        foreach ($labelAssocData as $key => $value) {
            $fields .= $key;
            $valuesNames .= ":" . $key;
            $values[":" . $key] = $value;
            $fields .= ",";
            $valuesNames .= ",";
        }
        $fields = substr($fields, 0, strlen($fields) - 1).' )';
        $valuesNames = substr($valuesNames, 0, strlen($valuesNames) - 1).' )';

        $this->database->prepareQuery("INSERT INTO label_association $fields VALUES $valuesNames");
        return $this->database->executeInsert($values);
    }


    /**
     * @param int $recId
     * @param int $userId
     * @param int $labelId
     * @return bool
     * @throws \Exception
     */
    public function setEntry(int $recId, int $userId, int $labelId): bool
    {
        $setSql =
            "REPLACE INTO label_association(recording_id, user_id, label_id)
            VALUES(:recording_id, :user_id, :label_id)";
        $this->database->prepareQuery($setSql);
        return $this->database->executeInsert([':recording_id' => $recId, ':user_id' => $userId, ':label_id' => $labelId]);
    }
}
