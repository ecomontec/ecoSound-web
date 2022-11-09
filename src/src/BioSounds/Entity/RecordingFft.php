<?php

namespace BioSounds\Entity;

use BioSounds\Provider\BaseProvider;

class RecordingFft extends BaseProvider
{
    const TABLE_NAME = "recording_fft";
    const USER = "user_id";
    const RECORDING = "recording_id";
    const FFT = "fft";

    /**
     * @param int $userId
     * @param int $recording_id
     * @return int|null
     * @throws \Exception
     */
    public function getUserRecordingFft($userId, $recording_id)
    {
        $this->database->prepareQuery(
            'SELECT fft FROM recording_fft WHERE user_id = :userId AND recording_id = :recording_id'
        );
        if (empty($result = $this->database->executeSelect([":userId" => $userId, ":recording_id" => $recording_id]))) {
            return null;
        }
        return $result[0][self::FFT];
    }

    /**
     * @param array $data
     * @return int|null
     * @throws \Exception
     */
    public function insert(array $data): ?int
    {
        if (empty($data)) {
            return false;
        }

        $fields = '( ';
        $valuesNames = '( ';
        $values = [];

        foreach ($data as $key => $value) {
            $fields .= $key;
            $valuesNames .= ':' . $key;
            $values[':' . $key] = $value;
            $fields .= ",";
            $valuesNames .= ",";
        }
        $fields = substr($fields, 0, strlen($fields) - 1) . ' )';
        $valuesNames = substr($valuesNames, 0, strlen($valuesNames) - 1) . ' )';
        $this->database->prepareQuery("INSERT INTO recording_fft $fields VALUES $valuesNames");
        return $this->database->executeInsert($values);
    }

    /**
     * @param int $userId
     * @param int $recording_id
     * @return int|null
     * @throws \Exception
     */
    public function delete(int $userId, int $recording_id): ?int
    {
        $this->database->prepareQuery('DELETE FROM recording_fft WHERE user_id = :userId AND recording_id =:recording_id');
        return $this->database->executeDelete([':userId' => $userId, ':recording_id' => $recording_id]);
    }

    /**
     * @param int $recording_id
     * @return int|null
     * @throws \Exception
     */
    public function deleteByRecroding(int $recording_id): ?int
    {
        $this->database->prepareQuery('DELETE FROM recording_fft WHERE recording_id =:recording_id');
        return $this->database->executeDelete([':recording_id' => $recording_id]);
    }
}
