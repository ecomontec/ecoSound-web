<?php

namespace BioSounds\Provider;

use BioSounds\Entity\AbstractProvider;
use BioSounds\Entity\License;
use BioSounds\Entity\Microphone;
use BioSounds\Entity\Recorder;
use BioSounds\Entity\Recording;
use BioSounds\Entity\Site;
use BioSounds\Entity\User;
use BioSounds\Exception\Database\NotFoundException;
use BioSounds\Controller\BaseController;
use BioSounds\Utils\Auth;
use getID3;

class RecordingProvider extends AbstractProvider
{
    const TABLE_NAME = "recording";

    /**
     * @return Recording[]
     * @throws \Exception
     */
    public function getList(): array
    {
        $query = 'SELECT recording_id, name, filename, col_id, directory, site_id, ';
        $query .= 'file_size, bitdepth, channel_num, DATE_FORMAT(file_date, \'%Y-%m-%d\') ';
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
                ->setSite($item['site_id'])
                ->setFileName($item['filename'])
                ->setFileDate($item['file_date'])
                ->setFileTime($item['file_time'])
                ->setFileSize($item['file_size'])
                ->setBitdepth($item['bitdepth'])
                ->setChannelNum($item['channel_num'])
                ->setSamplingRate($item['sampling_rate'])
                ->setDoi($item['doi'])
                ->setLicense($item['license_id']);
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
        $params = [
            ':colId' => $colId,
            ':usrId' => $userId,
            ':userId' => $userId,
        ];

        $query = 'SELECT recording.recording_id,recording.data_type,site.iho,recording.duty_cycle_recording,recording.duty_cycle_period, recording.name,recording.sampling_rate, recording.filename, col_id, recording.directory, recording.site_id, recording.user_id,recorder.model AS recorderName,recorder.brand AS brand,recorder.recorder_id,microphone.name AS microphoneName,microphone.microphone_id,';
        $query .= 'recording.type, recording.medium, recording.note, user.name AS user_name,  file_size, bitdepth, channel_num, duration, site.name as site_name, license.license_id, license.name as license_name, ';
        $query .= 'lba.label_id, lba.name as label_name,e1.`name` as realm,e2.`name` as biome,e3.`name` as functionalType,e1.`iucn_get_id` as realm_id,e2.`iucn_get_id` as biome_id,e3.`iucn_get_id` as functionalType_id,site.longitude_WGS84_dd_dddd AS longitude,site.latitude_WGS84_dd_dddd AS latitude,';
        $query .= "CONCAT(file_date,' ', file_time) AS start_date,DATE_FORMAT(DATE_ADD(STR_TO_DATE(CONCAT(file_date ,' ',file_time),'%Y-%m-%d %H:%i:%S'),INTERVAL duration second),'%Y-%m-%d %H:%i:%s') AS end_date,";
        $query .= 'DATE_FORMAT(file_date, \'%Y-%m-%d\') AS file_date, ';
        $query .= 'DATE_FORMAT(file_time, \'%H:%i:%s\') AS file_time, recording.doi, file_upload.path FROM recording ';
        $query .= 'LEFT JOIN 
                ( SELECT up.collection_id 
                  FROM user_permission up, permission p 
                  WHERE up.permission_id = p.permission_id 
                  AND (p.name = "Access" OR p.name = "View" OR p.name = "Review") 
                  AND up.user_id = :userId ) as coll on recording.col_id = coll.collection_id ';
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
            $siteIds = explode(',', $sites);
            $placeholders = [];
            foreach ($siteIds as $index => $siteId) {
                $placeholders[] = ":siteId$index";
                $params[":siteId$index"] = (int)$siteId;
            }
            $placeholdersStr = implode(', ', $placeholders);
            $query .= " AND (site.site_id in ($placeholdersStr) OR site.site_id is null) ";
        }

        $query .= ' ORDER BY recording.name';
        $this->database->prepareQuery($query);
        $result = $this->database->executeSelect($params);

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
    public function get(string $id): array
    {
        $ids = explode(',', $id);
        $params = [];
        $placeholders = [];
        foreach ($ids as $index => $value) {
            $placeholders[] = ":id$index";
            $params[":id$index"] = (int)$value;
        }
        $id_str = implode(', ', $placeholders);
        $query = 'SELECT r.*,s.longitude_WGS84_dd_dddd,s.latitude_WGS84_dd_dddd, (SELECT spectrogram.filename FROM spectrogram ';
        $query .= 'WHERE r.recording_id = spectrogram.recording_id ';
        $query .= 'AND spectrogram.type = \'spectrogram-player\') AS ImageFile ';
        $query .= 'FROM recording r ';
        $query .= 'LEFT JOIN site s ON s.site_id = r.site_id ';
        $query .= "WHERE r.recording_id IN ($id_str)";
        $this->database->prepareQuery($query);
        if (empty($result = $this->database->executeSelect($params))) {
            throw new NotFoundException($id);
        }
        return $result;
    }

    /**
     * @param string $id
     * @return array
     * @throws \Exception
     */
    public function getByCollection(string $id, string $site = null): array
    {
        $params = [];
        $ids = explode(',', $id);
        $placeholders = [];
        foreach ($ids as $index => $value) {
            $placeholders[] = ":id$index";
            $params[":id$index"] = (int)$value;
        }
        $id_str = implode(', ', $placeholders);
        $query = 'SELECT *, (SELECT filename FROM spectrogram ';
        $query .= 'WHERE ' . Recording::TABLE_NAME . '.' . Recording::ID . ' = spectrogram.recording_id ';
        $query .= 'AND type = \'spectrogram-player\') AS ImageFile ';
        $query .= 'FROM ' . Recording::TABLE_NAME . ' ';
        $query .= 'WHERE ' . Recording::TABLE_NAME . '.' . Recording::COL_ID . " IN ( $id_str )";
        if ($site) {
            $siteIds = explode(',', $site);
            $placeholders = [];
            foreach ($siteIds as $index => $siteId) {
                $placeholders[] = ":siteId$index";
                $params[":siteId$index"] = (int)$siteId;
            }
            $site_str = implode(', ', $placeholders);
            $query .= " AND (recording.site_id IN ( $site_str ) OR recording.site_id IS NULL) ";
        }
        $this->database->prepareQuery($query);
        $result = $this->database->executeSelect($params);
        return $result;
    }

    public function getModel(): array
    {
        $query = 'SELECT * FROM models';
        $this->database->prepareQuery($query);
        return $this->database->executeSelect();
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

    public function getCount()
    {
        $query = 'SELECT COUNT(*) AS count FROM recording';
        $this->database->prepareQuery($query);
        return $this->database->executeSelect()[0]['count'];
    }

    public function getCountByCollection(string $id, string $site = null)
    {
        $query = "SELECT COUNT(*) AS count FROM recording ";
        $params = [];
        if ($id != '') {
            $ids = explode(',', $id);
            $placeholders = [];
            foreach ($ids as $index => $value) {
                $placeholders[] = ":id$index";
                $params[":id$index"] = (int)$value;
            }
            $id_str = implode(', ', $placeholders);
            $query .= " WHERE col_id IN ($id_str) ";
        } else {
            $query .= ' WHERE 1=1';
        }
        if ($site) {
            if ($site == 'null') {
                $query .= " AND (site_id = 0 OR site_id IS NULL)";
            } else {
                $siteIds = explode(',', $site);
                $placeholders = [];
                foreach ($siteIds as $index => $siteId) {
                    $placeholders[] = ":siteId$index";
                    $params[":siteId$index"] = (int)$siteId;
                }
                $site_str = implode(', ', $placeholders);
                $query .= " AND (site_id IN ( $site_str ) OR site_id = 0 OR site_id IS NULL) ";
            }
        }
        $this->database->prepareQuery($query);
        return $this->database->executeSelect($params)[0]['count'];
    }

    /**
     * @param string $fileHash
     * @param int $collection_id
     * @return array|null
     * @throws \Exception
     */
    public function getByHash(string $fileHash, int $collection_id): ?array
    {
        $this->database->prepareQuery('SELECT * FROM ' . Recording::TABLE_NAME . ' WHERE ' . Recording::MD5_HASH . ' = :md5Hash AND col_id = :collection_id ');
        if (empty($result = $this->database->executeSelect([':md5Hash' => $fileHash, ':collection_id' => $collection_id]))) {
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
        $this->database->prepareQuery('DELETE FROM ' . Recording::TABLE_NAME . ' WHERE ' . Recording::ID . " IN ($id_str) ");
        $this->database->executeDelete($params);
    }

    public function getRecording(string $collectionId): array
    {
        $sql = "SELECT r.*,u.`name` AS username,s.`name` AS site,re.model,m.`name` AS microphone,l.`name` AS license,DATE_FORMAT(r.file_date, '%Y-%m-%d') AS file_date, DATE_FORMAT(r.file_time, '%H:%i:%s') AS file_time FROM recording r LEFT JOIN user u ON u.user_id = r.user_id LEFT JOIN site s ON s.site_id = r.site_id LEFT JOIN recorder re ON r.recorder_id = re.recorder_id LEFT JOIN microphone m ON r.microphone_id = m.microphone_id LEFT JOIN license l ON r.license_id = l.license_id WHERE col_id = :collectionId";
        $this->database->prepareQuery($sql);
        return $this->database->executeSelect([':collectionId' => $collectionId]);
    }

    public function getFilterCount(string $collectionId, string $search): int
    {
        $sql = "SELECT r.*,u.`name` AS username,s.`name` AS site,re.model,m.`name` AS microphone,l.`name` AS license,DATE_FORMAT(r.file_date, '%Y-%m-%d') AS file_date, DATE_FORMAT(r.file_time, '%H:%i:%s') AS file_time FROM recording r LEFT JOIN user u ON u.user_id = r.user_id LEFT JOIN site s ON s.site_id = r.site_id LEFT JOIN recorder re ON r.recorder_id = re.recorder_id LEFT JOIN microphone m ON r.microphone_id = m.microphone_id LEFT JOIN license l ON r.license_id = l.license_id WHERE col_id = :collectionId";
        if ($search) {
            $sql .= " AND CONCAT(IFNULL(r.recording_id,''), IFNULL(r.data_type,''), IFNULL(r.filename,''), IFNULL(r.name,''), IFNULL(u.name,''), IFNULL(s.name,''), IFNULL(re.model,''), IFNULL(r.recording_gain,''), IFNULL(m.name,''), IFNULL(l.name,''), IFNULL(r.type,''), IFNULL(r.medium,''), IFNULL(r.duty_cycle_recording,''), IFNULL(r.duty_cycle_period,''), IFNULL(r.note,''),IFNULL(r.DOI,''), IFNULL(r.creation_date,'')) LIKE '%$search%' ";
        }
        $this->database->prepareQuery($sql);
        $params = [
            ':collectionId' => $collectionId,
        ];
        if ($search) {
            $params[':search'] = '%' . $search . '%';
        }
        $count = count($this->database->executeSelect($params));
        return $count;
    }

    public function getListByPage(string $projectId, string $collectionId, string $start = '0', string $length = '8', string $search = null, string $column = '0', string $dir = 'asc'): array
    {
        $getID3 = new getID3();
        $arr = [];
        $dir = ($dir === 'asc' || $dir === 'desc') ? $dir : 'asc';
        $sql = "SELECT r.*,u.`name` AS username,s.`name` AS site,re.model,m.`name` AS microphone,l.`name` AS license,DATE_FORMAT(r.file_date, '%Y-%m-%d') AS file_date, DATE_FORMAT(r.file_time, '%H:%i:%s') AS file_time,CONCAT(r.col_id,'/',r.directory,'/',r.filename) AS path FROM recording r LEFT JOIN user u ON u.user_id = r.user_id LEFT JOIN site s ON s.site_id = r.site_id LEFT JOIN recorder re ON r.recorder_id = re.recorder_id LEFT JOIN microphone m ON r.microphone_id = m.microphone_id LEFT JOIN license l ON r.license_id = l.license_id LEFT JOIN file_upload f ON f.recording_id = r.recording_id WHERE col_id = :collectionId";
        if ($search) {
            $sql .= " AND CONCAT(IFNULL(r.recording_id,''), IFNULL(r.data_type,''), IFNULL(r.filename,''), IFNULL(r.name,''), IFNULL(u.name,''), IFNULL(s.name,''), IFNULL(re.model,''), IFNULL(r.recording_gain,''), IFNULL(m.name,''), IFNULL(l.name,''), IFNULL(r.type,''), IFNULL(r.medium,''), IFNULL(r.duty_cycle_recording,''), IFNULL(r.duty_cycle_period,''), IFNULL(r.note,''),IFNULL(r.DOI,''), IFNULL(r.creation_date,'')) LIKE :search ";
        }
        $a = ['', 'r.recording_id', 'r.data_type', 'r.filename', 'r.name', 'u.name', 's.name', 're.model', 'r.recording_gain', 'm.name', 'l.name', 'r.type', 'r.medium', 'r.duty_cycle_recording', 'r.duty_cycle_period', 'r.note', 'r.DOI', 'file_date', 'file_time'];
        $sql .= " ORDER BY $a[$column] $dir LIMIT :length OFFSET :start";
        $this->database->prepareQuery($sql);
        $params = [
            ':collectionId' => $collectionId,
            ':length' => $length,
            ':start' => $start,
        ];
        if ($search) {
            $params[':search'] = '%' . $search . '%';
        }
        $result = $this->database->executeSelect($params);
        $users = (new User())->getUserList();
        $sites = (new SiteProvider())->getList($projectId, $collectionId);
        $recorders = (new Recorder())->getBasicList();
        $licenses = (new License())->getBasicList();
        if (count($result)) {
            foreach ($result as $key => $value) {
                if ($value['data_type'] == 'audio data') {
                    $filePath = 'sounds/sounds/' . $value['path'];
                    $fileMeta = $getID3->analyze($filePath);
                }
                $str_user = '';
                $str_site = '';
                $str_recorder = '';
                $str_license = '';
                foreach ($users as $user) {
                    $str_user .= "<option value='$user[user_id]' " . ($user['user_id'] == $value['user_id'] ? 'selected' : '') . ">$user[name]</option>";
                }
                foreach ($sites as $site) {
                    $str_site .= "<option value='$site[site_id]' " . ($site['site_id'] == $value['site_id'] ? 'selected' : '') . " data-lat='$site[latitude_WGS84_dd_dddd]' data-lon='$site[longitude_WGS84_dd_dddd]'>$site[name]</option>";
                }
                foreach ($recorders as $recorder) {
                    $str_recorder .= "<option value='$recorder[recorder_id]' data-microphone='$recorder[microphone]' " . ($recorder['recorder_id'] == $value['recorder_id'] ? 'selected' : '') . ">" . (($recorder['brand'] == null || $recorder['brand'] == '') ? $recorder['model'] : ($recorder['model'] . '|' . $recorder['brand'])) . "</option>";
                }
                foreach ($licenses as $license) {
                    $str_license .= "<option value='$license[license_id]' " . ($license['license_id'] == $value['license_id'] ? 'selected' : '') . ">$license[name]</option>";
                }
                if ($value['data_type'] == 'audio data') {
                    $arr[$key][] = "<input type='checkbox' class='js-checkbox' data-id='$value[recording_id]' data-type='$value[data_type]' name='cb[$value[recording_id]]' id='cb[$value[recording_id]]'><a id='download$value[recording_id]' href='" . APP_URL . "/sounds/sounds/$value[col_id]/$value[directory]/" . preg_replace('/\.[^.]+$/', '.wav', $value['filename']) . "' download hidden></a>";
                } else {
                    $arr[$key][] = "<input type='checkbox' class='js-checkbox' data-id='$value[recording_id]' data-type='$value[data_type]' name='cb[$value[recording_id]]' id='cb[$value[recording_id]]'>";
                }
                $arr[$key][] = "$value[recording_id]
                        <input type='hidden' name='itemID' value='$value[recording_id]'>
                        <input id='old_id$value[recording_id]' type='hidden' value='$value[recording_id]'>
                        <input id='old_name$value[recording_id]' type='hidden' value='$value[username]'>
                        <input id='directory$value[recording_id]' type='hidden' value='$value[directory]'>
                        <input id='filename$value[recording_id]' type='hidden' value='$value[filename]'>
                        <input id='channel_num$value[recording_id]' type='hidden' value='$value[channel_num]' >
                        <input id='path$value[recording_id]' type='hidden' value='$value[path]' >
                        <input id='max_time$value[recording_id]' type='hidden' value='$value[duration]' >
                        <input id='max_freq$value[recording_id]' type='hidden' value='" . ($value['sampling_rate'] / 2) . "'>";
                $arr[$key][] = $value['data_type'];
                $arr[$key][] = $value['filename'];
                $arr[$key][] = "<input type='text' id='name_$value[recording_id]' class='form-control form-control-sm' style='width:200px;' title='Name' name='name' value='$value[name]'>";
                $arr[$key][] = "<select id='user_id$value[recording_id]' name='user_id' style='width:120px;' class='form-control form-control-sm'>$str_user</select>";
                $arr[$key][] = "<select id='site_id$value[recording_id]' name='site_id' style='width:120px;' class='form-control form-control-sm'><option value='0' ></option>$str_site</select>";
                $arr[$key][] = "<select name='recorder_id' id='recorder_$value[recording_id]' style='width:250px;' class='form-control form-control-sm'><option value='0' ></option>$str_recorder</select>";
                $arr[$key][] = "<select name='microphone_id' id='microphone_$value[recording_id]' style='width:250px;' class='form-control form-control-sm'><option value='$value[microphone_id]' selected>$value[microphone]</option></select>";
                $arr[$key][] = "<input type='number' class='form-control form-control-sm' name='recording_gain' data-id='$value[recording_id]' value='$value[recording_gain]' min='1' step='1'><small id='recordingGainValid$value[recording_id]' class='text-danger'></small>";
                $arr[$key][] = "<select name='license_id' style='width:140px;' class='form-control form-control-sm'><option value='0'></option>$str_license</select>";
                $arr[$key][] = "<select name='type' style='width:100px;' class='form-control form-control-sm'>
                            <option value='0'></option>
                            <option " . ($value['type'] == 'Passive' ? 'selected' : '') . ">Passive</option>
                            <option " . ($value['type'] == 'Focal' ? 'selected' : '') . ">Focal</option>
                            <option " . ($value['type'] == 'Enclosure' ? 'selected' : '') . ">Enclosure</option>
                        </select>";
                $arr[$key][] = "<select name='medium' style='width:80px;' class='form-control form-control-sm'>
                            <option value='0'></option>
                            <option " . ($value['medium'] == 'Air' ? 'selected' : '') . ">Air</option>
                            <option " . ($value['medium'] == 'Water' ? 'selected' : '') . ">Water</option>
                        </select>";
                $arr[$key][] = ($value['data_type'] == 'audio data') ? '' : "<input type='number' class='form-control form-control-sm' title='Duty cycle recording' name='duty_cycle_recording' value='$value[duty_cycle_recording]'>";
                $arr[$key][] = ($value['data_type'] == 'audio data') ? '' : "<input type='number' class='form-control form-control-sm' title='Duty cycle period' name='duty_cycle_period' value='$value[duty_cycle_period]'>";
                $arr[$key][] = "<input type='text' class='form-control form-control-sm' style='width:200px;' title='Note' name='note' value='$value[note]'>";
                $arr[$key][] = "<input type='text' class='form-control form-control-sm' style='width:200px;' title='DOI' name='DOI' value='$value[DOI]'>";
                $arr[$key][] = "<input type='date' id='file_date$value[recording_id]' class='form-control form-control-sm' title='Date' name='file_date' value='$value[file_date]'>";
                $arr[$key][] = "<input type='time' class='form-control form-control-sm' title='Time' name='file_time' min='00:00:00' max='23:59:59' step='1' value='$value[file_time]'>";
                if ($value['data_type'] == 'audio data') {
                    if ($fileMeta['fileformat'] == 'ogg') {
                        $arr[$key][] = isset($fileMeta['tags']['vorbiscomment']['title']) ? $fileMeta['tags']['vorbiscomment']['title'][0] . ' (Vorbis) ' : '';
                        $arr[$key][] = isset($fileMeta['tags']['vorbiscomment']['artist']) ? $fileMeta['tags']['vorbiscomment']['artist'][0] . ' (Vorbis) ' : '';
                        $arr[$key][] = isset($fileMeta['tags']['vorbiscomment']['album']) ? $fileMeta['tags']['vorbiscomment']['album'][0] . ' (Vorbis) ' : '';
                        $arr[$key][] = isset($fileMeta['tags']['vorbiscomment']['date']) ? $fileMeta['tags']['vorbiscomment']['date'][0] . ' (Vorbis) ' : '';
                        $arr[$key][] = isset($fileMeta['tags']['vorbiscomment']['comment']) ? $fileMeta['tags']['vorbiscomment']['comment'][0] . ' (Vorbis) ' : '';
                    } else if ($fileMeta['fileformat'] == 'wav') {
                        $arr[$key][] = (isset($fileMeta['tags']['id3v2']['title']) ? $fileMeta['tags']['id3v2']['title'][0] . ' (Id3v2) ' : '') . (isset($fileMeta['tags']['riff']['title']) ? $fileMeta['tags']['riff']['title'][0] . ' (RIFF) ' : '');
                        $arr[$key][] = (isset($fileMeta['tags']['id3v2']['artist']) ? $fileMeta['tags']['id3v2']['artist'][0] . ' (Id3v2) ' : '') . (isset($fileMeta['tags']['riff']['artist']) ? $fileMeta['tags']['riff']['artist'][0] . ' (RIFF) ' : '');
                        $arr[$key][] = (isset($fileMeta['tags']['id3v2']['album']) ? $fileMeta['tags']['id3v2']['album'][0] . ' (Id3v2) ' : '') . (isset($fileMeta['tags']['riff']['product']) ? $fileMeta['tags']['riff']['product'][0] . ' (RIFF) ' : '');
                        $arr[$key][] = (isset($fileMeta['tags']['id3v2']['year']) ? $fileMeta['tags']['id3v2']['year'][0] . ' (Id3v2) ' : '') . (isset($fileMeta['tags']['riff']['creationdate']) ? $fileMeta['tags']['riff']['creationdate'][0] . ' (RIFF) ' : '');
                        $arr[$key][] = (isset($fileMeta['tags']['id3v2']['comment']) ? $fileMeta['tags']['id3v2']['comment'][0] . ' (Id3v2) ' : '') . (isset($fileMeta['tags']['riff']['comment']) ? $fileMeta['tags']['riff']['comment'][0] . ' (RIFF) ' : '');
                    } else if ($fileMeta['fileformat'] == 'mp3') {
                        $arr[$key][] = isset($fileMeta['tags']['id3v2']['title']) ? $fileMeta['tags']['id3v2']['title'][0] . ' (Id3v2) ' : '';
                        $arr[$key][] = isset($fileMeta['tags']['id3v2']['artist']) ? $fileMeta['tags']['id3v2']['artist'][0] . ' (Id3v2) ' : '';
                        $arr[$key][] = isset($fileMeta['tags']['id3v2']['album']) ? $fileMeta['tags']['id3v2']['album'][0] . ' (Id3v2) ' : '';
                        $arr[$key][] = isset($fileMeta['tags']['id3v2']['year']) ? $fileMeta['tags']['id3v2']['year'][0] . ' (Id3v2) ' : '';
                        $arr[$key][] = isset($fileMeta['tags']['id3v2']['comment']) ? $fileMeta['tags']['id3v2']['comment'][0] . ' (Id3v2) ' : '';
                    } else if ($fileMeta['fileformat'] == 'flac') {
                        $arr[$key][] = (isset($fileMeta['tags']['id3v2']['title']) ? $fileMeta['tags']['id3v2']['title'][0] . ' (Id3v2) ' : '') . (isset($fileMeta['tags']['vorbiscomment']['title']) ? $fileMeta['tags']['vorbiscomment']['title'][0] . ' (Vorbis) ' : '');
                        $arr[$key][] = (isset($fileMeta['tags']['id3v2']['artist']) ? $fileMeta['tags']['id3v2']['artist'][0] . ' (Id3v2) ' : '') . (isset($fileMeta['tags']['vorbiscomment']['artist']) ? $fileMeta['tags']['vorbiscomment']['artist'][0] . ' (Vorbis) ' : '');
                        $arr[$key][] = (isset($fileMeta['tags']['id3v2']['album']) ? $fileMeta['tags']['id3v2']['album'][0] . ' (Id3v2) ' : '') . (isset($fileMeta['tags']['vorbiscomment']['album']) ? $fileMeta['tags']['vorbiscomment']['album'][0] . ' (Vorbis) ' : '');
                        $arr[$key][] = (isset($fileMeta['tags']['id3v2']['year']) ? $fileMeta['tags']['id3v2']['year'][0] . ' (Id3v2) ' : '') . (isset($fileMeta['tags']['vorbiscomment']['date']) ? $fileMeta['tags']['vorbiscomment']['date'][0] . ' (Vorbis) ' : '');
                        $arr[$key][] = (isset($fileMeta['tags']['id3v2']['comment']) ? $fileMeta['tags']['id3v2']['comment'][0] . ' (Id3v2) ' : '') . (isset($fileMeta['tags']['vorbiscomment']['comment']) ? $fileMeta['tags']['vorbiscomment']['comment'][0] . ' (Vorbis) ' : '');
                    } else {
                        $arr[$key][] = '';
                        $arr[$key][] = '';
                        $arr[$key][] = '';
                        $arr[$key][] = '';
                        $arr[$key][] = '';
                    }
                } else {
                    $arr[$key][] = '';
                    $arr[$key][] = '';
                    $arr[$key][] = '';
                    $arr[$key][] = '';
                    $arr[$key][] = '';
                }
            }
        }
        return $arr;
    }
}
