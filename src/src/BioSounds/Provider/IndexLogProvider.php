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
        return $this->database->executeSelect();
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
            $sql .= " CONCAT(IFNULL(i.log_id,''), IFNULL(r.name,''), IFNULL(u.name,''), IFNULL(it.name,''), IFNULL(i.minTime,''), IFNULL(i.maxTime,''), IFNULL(i.minFrequency,''), IFNULL(i.maxFrequency,''), IFNULL(i.input_name0,''), IFNULL(i.input_value0,''), IFNULL(i.input_name1,''), IFNULL(i.input_value1,''), IFNULL(i.input_name2,''), IFNULL(i.input_value2,''), IFNULL(i.input_name3,''), IFNULL(i.input_value3,''), IFNULL(i.input_name4,''), IFNULL(i.input_value4,''), IFNULL(i.input_name5,''), IFNULL(i.input_value5,''), IFNULL(i.input_name6,''), IFNULL(i.input_value6,''), IFNULL(i.output_name0,''), IFNULL(i.output_value0,''), IFNULL(i.output_name1,''), IFNULL(i.output_value1,''), IFNULL(i.output_name2,''), IFNULL(i.output_value2,''), IFNULL(i.output_name3,''), IFNULL(i.output_value3,''), IFNULL(i.output_name4,''), IFNULL(i.output_value4,''), IFNULL(i.output_name5,''), IFNULL(i.output_value5,''), IFNULL(i.creation_date,'')) LIKE '%$search%' ";
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
            $sql .= " CONCAT(IFNULL(i.log_id,''), IFNULL(r.name,''), IFNULL(u.name,''), IFNULL(it.name,''), IFNULL(i.minTime,''), IFNULL(i.maxTime,''), IFNULL(i.minFrequency,''), IFNULL(i.maxFrequency,''), IFNULL(i.input_name0,''), IFNULL(i.input_value0,''), IFNULL(i.input_name1,''), IFNULL(i.input_value1,''), IFNULL(i.input_name2,''), IFNULL(i.input_value2,''), IFNULL(i.input_name3,''), IFNULL(i.input_value3,''), IFNULL(i.input_name4,''), IFNULL(i.input_value4,''), IFNULL(i.input_name5,''), IFNULL(i.input_value5,''), IFNULL(i.input_name6,''), IFNULL(i.input_value6,''), IFNULL(i.output_name0,''), IFNULL(i.output_value0,''), IFNULL(i.output_name1,''), IFNULL(i.output_value1,''), IFNULL(i.output_name2,''), IFNULL(i.output_value2,''), IFNULL(i.output_name3,''), IFNULL(i.output_value3,''), IFNULL(i.output_name4,''), IFNULL(i.output_value4,''), IFNULL(i.output_name5,''), IFNULL(i.output_value5,''), IFNULL(i.creation_date,'')) LIKE '%$search%' ";
        }
        $a = ['', 'i.log_id', 'r.name', 'u.name', 'it.name', 'i.minTime', 'i.maxTime', 'i.minFrequency', 'i.maxFrequency', 'i.input_name0', 'i.input_value0', 'i.input_name1', 'i.input_value1', 'i.input_name2', 'i.input_value2', 'i.input_name3', 'i.input_value3', 'i.input_name4', 'i.input_value4', 'i.input_name5', 'i.input_value5', 'i.input_name6', 'i.input_value6', 'i.output_name0', 'i.output_value0', 'i.output_name1', 'i.output_value1', 'i.output_name2', 'i.output_value2', 'i.output_name3', 'i.output_value3', 'i.output_name4', 'i.output_value4', 'i.output_name5', 'i.output_value5', 'i.creation_date'];
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
                $arr[$key][] = $value['input_name0'];
                $arr[$key][] = $value['input_value0'];
                $arr[$key][] = $value['input_name1'];
                $arr[$key][] = $value['input_value1'] ? number_format(floatval($value['input_value1']), 2, '.', ',') : '';
                $arr[$key][] = $value['input_name2'];
                $arr[$key][] = $value['input_value2'] ? number_format(floatval($value['input_value2']), 2, '.', ',') : '';
                $arr[$key][] = $value['input_name3'];
                $arr[$key][] = $value['input_value3'] ? number_format(floatval($value['input_value3']), 2, '.', ',') : '';
                $arr[$key][] = $value['input_name4'];
                $arr[$key][] = $value['input_value4'] ? number_format(floatval($value['input_value4']), 2, '.', ',') : '';
                $arr[$key][] = $value['input_name5'];
                $arr[$key][] = $value['input_value5'] ? number_format(floatval($value['input_value5']), 2, '.', ',') : '';
                $arr[$key][] = $value['input_name6'];
                $arr[$key][] = $value['input_value6'] ? number_format(floatval($value['input_value6']), 2, '.', ',') : '';
                $arr[$key][] = $value['output_name0'];
                $arr[$key][] = $value['output_value0'] ? number_format(floatval($value['output_value0']), 2, '.', ',') : '';
                $arr[$key][] = $value['output_name1'];
                $arr[$key][] = $value['output_value1'] ? number_format(floatval($value['output_value1']), 2, '.', ',') : '';
                $arr[$key][] = $value['output_name2'];
                $arr[$key][] = $value['output_value2'] ? number_format(floatval($value['output_value2']), 2, '.', ',') : '';
                $arr[$key][] = $value['output_name3'];
                $arr[$key][] = $value['output_value3'] ? number_format(floatval($value['output_value3']), 2, '.', ',') : '';
                $arr[$key][] = $value['output_name4'];
                $arr[$key][] = $value['output_value4'] ? number_format(floatval($value['output_value4']), 2, '.', ',') : '';
                $arr[$key][] = $value['output_name5'];
                $arr[$key][] = $value['output_value5'] ? number_format(floatval($value['output_value5']), 2, '.', ',') : '';
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
