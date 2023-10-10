<?php

namespace BioSounds\Provider;

use BioSounds\Entity\Recording;
use BioSounds\Entity\Site;
use BioSounds\Entity\User;
use BioSounds\Exception\Database\NotFoundException;
use BioSounds\Controller\BaseController;

class RecordingProvider extends BaseProvider
{
    /**
     * @return Recording[]
     * @throws \Exception
     */
    public function getList(): array
    {
        $query = 'SELECT recording_id, name, filename, col_id, directory, sensor_id, site_id, ';
        $query .= 'sound_id, file_size, bitrate, channel_num, DATE_FORMAT(file_date, \'%Y-%m-%d\') ';
        $query .= 'AS file_date, DATE_FORMAT(file_time, \'%H:%i:%s\') AS file_time, sampling_rate, doi, license_id ';
        $query .= 'FROM recording';

        $this->database->prepareQuery($query);
        $result = $this->database->executeSelect();

        $data = [];
        foreach ($result as $item) {
            $data[] = (new Recording())
                ->setId($item['recording_id'])
                ->setName($item['name'])
                ->setCollection($item['col_id'])
                ->setDirectory($item['directory'])
                ->setSensor($item['sensor_id'])
                ->setSite($item['site_id'])
                ->setSound($item['sound_id'])
                ->setFileName($item['filename'])
                ->setFileDate($item['file_date'])
                ->setFileTime($item['file_time'])
                ->setFileSize($item['file_size'])
                ->setBitrate($item['bitrate'])
                ->setChannelNum($item['channel_num'])
                ->setSamplingRate($item['sampling_rate'])
                ->setDoi($item['doi'])
                ->setLicense($item['license_id'])
                ->setLicenseName($item['license_name']);
        }
        return $data;
    }

    /**
     * @param int $colId
     * @param int $steId
     * @return Recording[]
     * @throws \Exception
     */
    public function getListByCollection(int $colId, int $userId, string $sites = null): array
    {
        $values = [
            ':colId' => $colId,
            ':usrId' => $userId,
            ':userId' => $userId,
        ];

        $query = 'SELECT recording.recording_id,recording.data_type, recording.name,recording.sampling_rate, recording.filename, col_id, recording.directory, recording.sensor_id, recording.site_id, recording.user_id,recorder.modal AS recorderName,recorder.brand AS brand,recorder.recorder_id,microphone.name AS microphoneName,microphone.microphone_id,';
        $query .= 'recording.type, recording.medium, recording.note, user.name AS user_name,  file_size, bitrate, channel_num, duration, site.name as site_name, license.license_id, license.name as license_name, ';
        $query .= 'lba.label_id, lba.name as label_name,e1.`name` as realm,e2.`name` as biome,e3.`name` as functionalType,site.longitude_WGS84_dd_dddd AS longitude,site.latitude_WGS84_dd_dddd AS latitude,';
        $query .= "CONCAT(file_date,' ', file_time) AS start_date,DATE_ADD(STR_TO_DATE(CONCAT(file_date ,' ',file_time),'%Y-%m-%d %H:%i:%S'),INTERVAL duration second) AS end_date,";
        $query .= 'DATE_FORMAT(file_date, \'%Y-%m-%d\') AS file_date, ';
        $query .= 'DATE_FORMAT(file_time, \'%H:%i:%s\') AS file_time, recording.doi, file_upload.path FROM recording ';
        $query .= 'LEFT JOIN 
                ( SELECT up.collection_id 
                  FROM user_permission up, permission p 
                  WHERE up.permission_id = p.permission_id 
                  AND (p.name = "Access" OR p.name = "View" OR p.name = "Review") 
                  AND up.user_id = :userId ) as coll on recording.col_id = coll.collection_id ';

        //default view for site and license
        $query .= 'LEFT JOIN site ON ( recording.site_id) = ( site.site_id) ';
        $query .= 'LEFT JOIN file_upload ON file_upload.recording_id = recording.recording_id ';
        $query .= 'LEFT JOIN user ON ( recording.user_id) = ( user.user_id) ';
        $query .= 'LEFT JOIN license ON recording.license_id = license.license_id ';
        $query .= 'LEFT JOIN 
                ( SELECT label.label_id, label.name, label_association.recording_id FROM label LEFT JOIN label_association 
                ON label.label_id = label_association.label_id WHERE label_association.user_id = :usrId) 
            AS lba ON recording.recording_id = lba.recording_id ';
        $query .= 'LEFT JOIN iucn_get e1 ON site.realm_id = e1.iucn_get_id
                   LEFT JOIN iucn_get e2 ON site.biome_id = e2.iucn_get_id
                   LEFT JOIN iucn_get e3 ON site.functional_type_id = e3.iucn_get_id ';
        $query .= 'LEFT JOIN recorder ON recording.recorder_id = recorder.recorder_id
                   LEFT JOIN microphone ON recording.microphone_id = microphone.microphone_id';

        $query .= " WHERE col_id = :colId ";

        if ($sites) {
            $query .= " AND (site.site_id in ($sites) OR site.site_id is null) ";
        }

        $query .= ' ORDER BY recording.name';
        $this->database->prepareQuery($query);
        $result = $this->database->executeSelect($values);

        $data = [];

        foreach ($result as $item) {
            $recording = (new Recording())->createFromValues($item);
            if (!empty($recording->getUserId())) {
                $recording->setUserFullName((new User())->getFullName($recording->getUserId()));
            }
            $data[] = $recording;
        }

        return $data;
    }

    /**
     * @param int $id
     * @return array
     * @throws \Exception
     */
    public function get(int $id): array
    {
        $query = 'SELECT r.*,s.longitude_WGS84_dd_dddd,s.latitude_WGS84_dd_dddd, (SELECT spectrogram.filename FROM spectrogram ';
        $query .= 'WHERE r.recording_id = spectrogram.recording_id ';
        $query .= 'AND spectrogram.type = \'spectrogram-player\') AS ImageFile ';
        $query .= 'FROM recording r ';
        $query .= 'LEFT JOIN site s ON s.site_id = r.site_id ';
        $query .= 'WHERE r.recording_id = :id';

        $this->database->prepareQuery($query);
        if (empty($result = $this->database->executeSelect([':id' => $id]))) {
            throw new NotFoundException($id);
        }
        return $result[0];
    }

    /**
     * @param int $id
     * @return array
     * @throws \Exception
     */
    public function getByCollection(int $id): array
    {
        $query = 'SELECT *, (SELECT filename FROM spectrogram ';
        $query .= 'WHERE ' . Recording::TABLE_NAME . '.' . Recording::ID . ' = spectrogram.recording_id ';
        $query .= 'AND type = \'spectrogram-player\') AS ImageFile ';
        $query .= 'FROM ' . Recording::TABLE_NAME . ' ';
        $query .= 'WHERE ' . Recording::TABLE_NAME . '.' . Recording::COL_ID . ' = :id';

        $this->database->prepareQuery($query);
        $result = $this->database->executeSelect([':id' => $id]);

        return $result;
    }

    /**
     * @param int $id
     * @return array
     * @throws \Exception
     */
    public function getBasic(int $id): array
    {
        $query = 'SELECT * FROM recording WHERE ' . Recording::ID . ' = :id';

        $this->database->prepareQuery($query);
        if (empty($result = $this->database->executeSelect([':id' => $id]))) {
            throw new \Exception("Recording $id doesn't exist.");
        }
        return $result[0];
    }

    /**
     * TODO: This method must substitute getBasic
     * @param int $id
     * @return Recording
     * @throws \Exception
     */
    public function getSimple(int $id): Recording
    {
        $query = 'SELECT * FROM recording WHERE ' . Recording::ID . ' = :id';

        $this->database->prepareQuery($query);
        if (empty($result = $this->database->executeSelect([':id' => $id]))) {
            throw new \Exception("Recording $id doesn't exist.");
        }
        return (new Recording())->createFromValues($result[0]);
    }

    /**
     * @param string $fileHash
     * @return array|null
     * @throws \Exception
     */
    public function getByHash(string $fileHash, $collection_id): ?array
    {
        $this->database->prepareQuery('SELECT * FROM ' . Recording::TABLE_NAME . ' WHERE ' . Recording::MD5_HASH . ' = :md5Hash AND col_id = ' . $collection_id);
        if (empty($result = $this->database->executeSelect([':md5Hash' => $fileHash]))) {
            return null;
        }
        return $result[0];
    }

    public function getNullCount(int $id): int
    {
        $query = 'SELECT * FROM recording WHERE col_id = :id AND site_id IS NULL';


        $this->database->prepareQuery($query);
        $result = $this->database->executeSelect([':id' => $id]);

        return count($result);
    }

    /**
     * @param $data
     * @return bool|int|null
     * @throws \Exception
     */
    public function insert($data)
    {
        if (empty($data)) {
            return false;
        }


        $fields = '( ';
        $valuesNames = '( ';
        $values = [];
        end($data);
        $lastKey = key($data);

        foreach ($data as $key => $value) {
            $fields .= $key;
            $valuesNames .= ":" . $key;
            $values[":" . $key] = $value;
            if ($lastKey !== $key) {
                $fields .= ", ";
                $valuesNames .= ", ";
            }
        }
        $fields .= ' )';
        $valuesNames .= ' )';

        $this->database->prepareQuery('INSERT INTO ' . Recording::TABLE_NAME . " $fields VALUES $valuesNames");
        return $this->database->executeInsert($values);
    }

    /**
     * @param $data
     * @return bool|int|null
     * @throws \Exception
     */
    public function update($data)
    {
        if (empty($data)) {
            return false;
        }

        $id = $data["itemID"];
        unset($data["itemID"]);
        $fields = [];
        $values = [];

        foreach ($data as $key => $value) {
            $fields[] = $key . " = :" . $key;
            $values[":" . $key] = $value;
        }

        $values[":id"] = $id;

        $query = 'UPDATE ' . Recording::TABLE_NAME . ' SET ' . implode(", ", $fields) . ' ';
        $query .= 'WHERE ' . Recording::ID . '= :id';

        $this->database->prepareQuery($query);
        return $this->database->executeUpdate($values);
    }

    /**
     * @param int $id
     * @throws \Exception
     */
    public function delete(int $id): void
    {
        $this->database->prepareQuery('DELETE FROM ' . Recording::TABLE_NAME . ' WHERE ' . Recording::ID . ' = :id');
        $this->database->executeDelete([':id' => $id]);
    }
}
