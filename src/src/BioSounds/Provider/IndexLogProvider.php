<?php

namespace BioSounds\Provider;

use BioSounds\Entity\AbstractProvider;
use BioSounds\Entity\IndexLog;
use BioSounds\Utils\Auth;
use Cassandra\Varint;

class IndexLogProvider extends AbstractProvider
{
    const TABLE_NAME = "index_log";

    /**
     * @return array
     * @throws \Exception
     */
    public function getList(): array
    {
        $list = [];

        $sql = "SELECT i.*,r.`name` AS recordingName,u.`name` AS userName,it.`name` AS indexName FROM index_log i 
            LEFT JOIN recording r ON r.recording_id = i.recording_id
            LEFT JOIN user u ON u.user_id = i.user_id
            LEFT JOIN index_type it ON it.index_id = i.index_id ";
        if (!Auth::isUserAdmin()) {
            $sql = $sql . ' WHERE i.user_id = ' . Auth::getUserLoggedID();
        }
        $sql = $sql . " ORDER BY i.log_id";
        $this->database->prepareQuery($sql);
        if (!empty($result = $this->database->executeSelect())) {
            foreach ($result as $indexLog) {
                $list[] = (new IndexLog())
                    ->setLogId($indexLog['log_id'])
                    ->setRecordingId($indexLog['recording_id'])
                    ->setRecordingName($indexLog['recordingName'])
                    ->setUserId($indexLog['user_id'])
                    ->setUserName($indexLog['userName'])
                    ->setIndexId($indexLog['index_id'])
                    ->setIndexName($indexLog['indexName'])
                    ->setMinTime($indexLog['minTime'])
                    ->setMaxTime($indexLog['maxTime'])
                    ->setMinFrequency($indexLog['minFrequency'])
                    ->setMaxFrequency($indexLog['maxFrequency'])
                    ->setValue($indexLog['value'])
                    ->setParam($indexLog['param'])
                    ->setDate($indexLog['creation_date']);
            }
        }
        return $list;
    }

    /**
     * @param int $tagId
     * @return array|int
     * @throws \Exception
     */
    public function deleteByRecording(string $recording_id)
    {
        $this->database->prepareQuery("DELETE FROM index_log WHERE recording_id IN ($recording_id)");
        return $this->database->executeDelete();
    }

    public function getIndexLog(): array
    {
        $sql = "SELECT i.*,r.`name` AS recordingName,u.`name` AS userName,it.`name` AS indexName FROM index_log i 
            LEFT JOIN recording r ON r.recording_id = i.recording_id
            LEFT JOIN user u ON u.user_id = i.user_id
            LEFT JOIN index_type it ON it.index_id = i.index_id ";
        if (!Auth::isUserAdmin()) {
            $sql = $sql . ' WHERE i.user_id = ' . Auth::getUserLoggedID();
        }
        $this->database->prepareQuery($sql);
        return $this->database->executeSelect();
    }

    public function getFilterCount(string $search): int
    {
        $sql = "SELECT i.*,r.`name` AS recordingName,u.`name` AS userName,it.`name` AS indexName FROM index_log i 
            LEFT JOIN recording r ON r.recording_id = i.recording_id
            LEFT JOIN user u ON u.user_id = i.user_id
            LEFT JOIN index_type it ON it.index_id = i.index_id ";
        if (!Auth::isUserAdmin()) {
            $sql = $sql . ' WHERE i.user_id = ' . Auth::getUserLoggedID();
        }
        if ($search) {
            $sql .= Auth::isUserAdmin() ? ' WHERE ' : ' AND ';
            $sql .= " CONCAT(IFNULL(i.log_id,''), IFNULL(r.name,''), IFNULL(u.name,''), IFNULL(it.name,''), IFNULL(i.minTime,''), IFNULL(i.maxTime,''), IFNULL(i.minFrequency,''), IFNULL(i.maxFrequency,''), IFNULL(i.param,''), IFNULL(i.value,''), IFNULL(i.creation_date,'')) LIKE '%$search%' ";
        }
        $this->database->prepareQuery($sql);
        $count = count($this->database->executeSelect());
        return $count;
    }

    public function getListByPage(string $start = '0', string $length = '8', string $search = null, string $column = '0', string $dir = 'asc'): array
    {
        $arr = [];
        $sql = "SELECT i.*,r.`name` AS recordingName,u.`name` AS userName,it.`name` AS indexName FROM index_log i 
            LEFT JOIN recording r ON r.recording_id = i.recording_id
            LEFT JOIN user u ON u.user_id = i.user_id
            LEFT JOIN index_type it ON it.index_id = i.index_id ";
        if (!Auth::isUserAdmin()) {
            $sql = $sql . ' WHERE i.user_id = ' . Auth::getUserLoggedID();
        }
        if ($search) {
            $sql .= Auth::isUserAdmin() ? ' WHERE ' : ' AND ';
            $sql .= " CONCAT(IFNULL(i.log_id,''), IFNULL(r.name,''), IFNULL(u.name,''), IFNULL(it.name,''), IFNULL(i.minTime,''), IFNULL(i.maxTime,''), IFNULL(i.minFrequency,''), IFNULL(i.maxFrequency,''), IFNULL(i.param,''), IFNULL(i.value,''), IFNULL(i.creation_date,'')) LIKE '%$search%' ";
        }
        $a = ['', 'i.log_id', 'r.name', 'u.name', 'it.name', 'i.minTime', 'i.maxTime', 'i.minFrequency', 'i.maxFrequency', 'i.param', 'i.value', 'i.creation_date'];
        $sql .= " ORDER BY $a[$column] $dir LIMIT $length OFFSET $start";
        $this->database->prepareQuery($sql);
        $result = $this->database->executeSelect();
        if (count($result)) {
            foreach ($result as $key => $value) {
                $arr[$key][] = "<input type='checkbox' class='js-checkbox'data-id='$value[log_id]' name='cb[$value[log_id]]' id='cb[$value[log_id]]'>";
                $arr[$key][] = $value['log_id'];
                $arr[$key][] = $value['recordingName'];
                $arr[$key][] = $value['userName'];
                $arr[$key][] = str_replace('_', ' ', $value['indexName']);
                $arr[$key][] = $value['minTime'];
                $arr[$key][] = $value['maxTime'];
                $arr[$key][] = $value['minFrequency'];
                $arr[$key][] = $value['maxFrequency'];
                $arr[$key][] = str_replace('@', ' ', str_replace('?', ':', $value['param']));
                $arr[$key][] = implode(' ', array_map(function ($v) {
                    $parts = explode('?', $v);
                    return $parts[0] . ': ' . number_format(floatval($parts[1]), 2, '.', ',');
                }, explode('!', $value['value'])));;
                $arr[$key][] = $value['creation_date'];
            }
        }
        return $arr;
    }

    public function delete(string $id): void
    {
        $this->database->prepareQuery("DELETE FROM index_log WHERE log_id IN ($id)");
        $this->database->executeDelete();
    }
}
