<?php

namespace BioSounds\Provider;

use BioSounds\Entity\File;

class FileProvider extends BaseProvider
{
    /**
     * @param File $file
     * @return int|null
     * @throws \Exception
     */
    public function insert(File $file)
    {
        $query = 'INSERT INTO file_upload (path, filename,`name`, `date`, `time`, site_id, collection_id, directory, recording_gain, ';
        $query .= 'recorder_id, microphone_id, recording_id, user_id, species_id, sound_type_id, subtype, rating,note, doi, license_id,type,medium) ';
        $query .= 'VALUES (:path,:name, :filename, :date, :time, :site, :collection, :directory, :recording_gain, :recorder, :microphone, ';
        $query .= ':recording, :user, :species, :soundType, :subtype, :rating,:note, :doi, :license,:type,:medium)';
        $this->database->prepareQuery($query);
        return $this->database->executeInsert($file->getDatabaseValues());
    }

    /**
     * @param int $fileId
     * @return null|File
     * @throws \Exception
     */
    public function get(int $fileId): ?File
    {
        $this->database->prepareQuery("SELECT * FROM file_upload WHERE file_upload_id = $fileId");

        if (!empty($result = $this->database->executeSelect())) {
            $result = $result[0];
            return (new File())
                ->setPath($result['path'])
                ->setName($result['name'])
                ->setFilename($result['filename'])
                ->setDate($result['date'])
                ->setTime($result['time'])
                ->setSite($result['site_id'])
                ->setCollection($result['collection_id'])
                ->setDirectory($result['directory'])
                ->setRecorder($result['recorder_id'])
                ->setMicrophone($result['microphone_id'])
                ->setRecordingGain($result['recording_gain'])
                ->setId($result['file_upload_id'])
                ->setUser($result['user_id'])
                ->setSpecies($result['species_id'])
                ->setSoundType($result['sound_type_id'])
                ->setSubtype($result['subtype'])
                ->setRating($result['rating'])
                ->setDoi($result['doi'])
                ->setLicense($result['license_id'])
                ->setType($result['type'])
                ->setMedium($result['medium'])
                ->setNote($result['note']);
        }
        return null;
    }

    /**
     * @param File $file
     * @throws \Exception
     */
    public function update(File $file)
    {
        $query = 'UPDATE file_upload SET ';
        $query .= "error = '" . $file->getError() . "', ";
        $query .= "status = " . $file->getStatus() . " ";

        if (!empty($file->getRecording())) {
            $query .= ", recording_id = " . $file->getRecording() . " ";
        }

        $query .= "WHERE file_upload_id = " . $file->getId();
        $this->database->prepareQuery($query);
        $this->database->executeUpdate();
    }
}
