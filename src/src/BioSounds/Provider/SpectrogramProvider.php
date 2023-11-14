<?php

namespace BioSounds\Provider;

use BioSounds\Entity\Spectrogram;

class SpectrogramProvider extends BaseProvider
{
    /**
     * @param Spectrogram $spectrogram
     * @return array|int
     * @throws \Exception
     */
    public function insert(Spectrogram $spectrogram)
    {
        $query = 'INSERT INTO spectrogram (recording_id, filename, type, max_frequency) ';
        $query .= 'VALUES (:recordingId, :filename, :type, :maxFrequency)';
        $this->database->prepareQuery($query);
        return $this->database->executeInsert($spectrogram->getDatabaseValues());
    }

    /**
     * @param int $recordingId
     * @param string $imageType
     * @return Spectrogram|null
     * @throws \Exception
     */
    public function get(int $recordingId, string $type): ?Spectrogram
    {
        $query = 'SELECT filename FROM spectrogram WHERE type = :type AND ';
        $query .= 'recording_id = :recordingId';

        $this->database->prepareQuery($query);
        if (!empty($result = $this->database->executeSelect([':recordingId' => $recordingId, ':type' => $type]))) {
            return (new Spectrogram())
                ->setFilename($result[0]['filename']);
        }
        return null;
    }

    /**
     * @param int $id
     * @return Spectrogram[]
     * @throws \Exception
     */
    public function getListInRecording(string $id): array
    {
        $query = "SELECT recording_id, filename, type FROM spectrogram WHERE recording_id IN ($id)";
        $this->database->prepareQuery($query);

        $data = [];
        $list = $this->database->executeSelect();
        foreach ($list as $item) {
            $data[] = (new Spectrogram())
                ->setRecordingId($item['recording_id'])
                ->setFilename($item['filename'])
                ->setType($item['type']);
        }
        return $data;
    }

    /**
     * @param int $recordingId
     * @return array|int
     * @throws \Exception
     */
    public function deleteByRecording(int $recordingId)
    {
        $this->database->prepareQuery('DELETE FROM spectrogram WHERE recording_id = ' . $recordingId);
        return $this->database->executeDelete();
    }
}