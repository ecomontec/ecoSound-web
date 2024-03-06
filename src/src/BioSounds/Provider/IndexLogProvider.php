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

    public function getId(): int
    {

        $sql = "SELECT IFNULL(MAX(log_id), 0) + 1 AS log_id FROM index_log";
        $this->database->prepareQuery($sql);
        return $this->database->executeSelect()[0]['log_id'];
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
        $sql = "SELECT i.log_id,
    i.recording_id,
    i.user_id,
    i.index_id,
    i.version,
    i.min_time,
    i.max_time,
    i.min_frequency,
    i.max_frequency,
      MAX(CASE WHEN variable_type = 'input' AND variable_order = 1 THEN variable_value ELSE NULL END) AS channel,
    MAX(CASE WHEN variable_type = 'input' AND variable_order = 2 THEN variable_name ELSE NULL END) AS input_name_1,
    MAX(CASE WHEN variable_type = 'input' AND variable_order = 2 THEN variable_value ELSE NULL END) AS input_value_1,
    MAX(CASE WHEN variable_type = 'input' AND variable_order = 3 THEN variable_name ELSE NULL END) AS input_name_2,
    MAX(CASE WHEN variable_type = 'input' AND variable_order = 3 THEN variable_value ELSE NULL END) AS input_value_2,
    MAX(CASE WHEN variable_type = 'input' AND variable_order = 4 THEN variable_name ELSE NULL END) AS input_name_3,
    MAX(CASE WHEN variable_type = 'input' AND variable_order = 4 THEN variable_value ELSE NULL END) AS input_value_3,
    MAX(CASE WHEN variable_type = 'input' AND variable_order = 5 THEN variable_name ELSE NULL END) AS input_name_4,
    MAX(CASE WHEN variable_type = 'input' AND variable_order = 5 THEN variable_value ELSE NULL END) AS input_value_4,
    MAX(CASE WHEN variable_type = 'input' AND variable_order = 6 THEN variable_name ELSE NULL END) AS input_name_5,
    MAX(CASE WHEN variable_type = 'input' AND variable_order = 6 THEN variable_value ELSE NULL END) AS input_value_5,
    MAX(CASE WHEN variable_type = 'input' AND variable_order = 7 THEN variable_name ELSE NULL END) AS input_name_6,
    MAX(CASE WHEN variable_type = 'input' AND variable_order = 7 THEN variable_value ELSE NULL END) AS input_value_6,
    MAX(CASE WHEN variable_type = 'output' AND variable_order = 1 THEN variable_name ELSE NULL END) AS output_name_1,
    MAX(CASE WHEN variable_type = 'output' AND variable_order = 1 THEN variable_value ELSE NULL END) AS output_value_1,
    MAX(CASE WHEN variable_type = 'output' AND variable_order = 2 THEN variable_name ELSE NULL END) AS output_name_2,
    MAX(CASE WHEN variable_type = 'output' AND variable_order = 2 THEN variable_value ELSE NULL END) AS output_value_2,
    MAX(CASE WHEN variable_type = 'output' AND variable_order = 3 THEN variable_name ELSE NULL END) AS output_name_3,
    MAX(CASE WHEN variable_type = 'output' AND variable_order = 3 THEN variable_value ELSE NULL END) AS output_value_3,
    MAX(CASE WHEN variable_type = 'output' AND variable_order = 4 THEN variable_name ELSE NULL END) AS output_name_4,
    MAX(CASE WHEN variable_type = 'output' AND variable_order = 4 THEN variable_value ELSE NULL END) AS output_value_4,
    MAX(CASE WHEN variable_type = 'output' AND variable_order = 5 THEN variable_name ELSE NULL END) AS output_name_5,
    MAX(CASE WHEN variable_type = 'output' AND variable_order = 5 THEN variable_value ELSE NULL END) AS output_value_5,
    MAX(CASE WHEN variable_type = 'output' AND variable_order = 6 THEN variable_name ELSE NULL END) AS output_name_6,
    MAX(CASE WHEN variable_type = 'output' AND variable_order = 6 THEN variable_value ELSE NULL END) AS output_value_6,
    i.creation_date,r.`name` AS recordingName,u.`name` AS userName,it.`name` AS indexName FROM index_log i 
            LEFT JOIN recording r ON r.recording_id = i.recording_id
            LEFT JOIN user u ON u.user_id = i.user_id
            LEFT JOIN index_type it ON it.index_id = i.index_id ";
        if (!Auth::isUserAdmin()) {
            $sql = $sql . ' WHERE i.user_id = ' . Auth::getUserLoggedID();
        }
        $sql = $sql . 'GROUP BY i.log_id,i.user_id,i.recording_id,i.index_id,i.version,i.min_time,i.max_time,i.min_frequency,i.max_frequency,i.creation_date';
        $this->database->prepareQuery($sql);
        return $this->database->executeSelect();
    }

    public function getFilterCount(string $search): int
    {
        $sql = "SELECT * FROM (SELECT i.log_id,
    i.recording_id,
    i.user_id,
    i.index_id,
    i.version,
    i.min_time,
    i.max_time,
    i.min_frequency,
    i.max_frequency,
       MAX(CASE WHEN variable_type = 'input' AND variable_order = 1 THEN variable_value ELSE NULL END) AS channel,
    MAX(CASE WHEN variable_type = 'input' AND variable_order = 2 THEN variable_name ELSE NULL END) AS input_name_1,
    MAX(CASE WHEN variable_type = 'input' AND variable_order = 2 THEN variable_value ELSE NULL END) AS input_value_1,
    MAX(CASE WHEN variable_type = 'input' AND variable_order = 3 THEN variable_name ELSE NULL END) AS input_name_2,
    MAX(CASE WHEN variable_type = 'input' AND variable_order = 3 THEN variable_value ELSE NULL END) AS input_value_2,
    MAX(CASE WHEN variable_type = 'input' AND variable_order = 4 THEN variable_name ELSE NULL END) AS input_name_3,
    MAX(CASE WHEN variable_type = 'input' AND variable_order = 4 THEN variable_value ELSE NULL END) AS input_value_3,
    MAX(CASE WHEN variable_type = 'input' AND variable_order = 5 THEN variable_name ELSE NULL END) AS input_name_4,
    MAX(CASE WHEN variable_type = 'input' AND variable_order = 5 THEN variable_value ELSE NULL END) AS input_value_4,
    MAX(CASE WHEN variable_type = 'input' AND variable_order = 6 THEN variable_name ELSE NULL END) AS input_name_5,
    MAX(CASE WHEN variable_type = 'input' AND variable_order = 6 THEN variable_value ELSE NULL END) AS input_value_5,
    MAX(CASE WHEN variable_type = 'input' AND variable_order = 7 THEN variable_name ELSE NULL END) AS input_name_6,
    MAX(CASE WHEN variable_type = 'input' AND variable_order = 7 THEN variable_value ELSE NULL END) AS input_value_6,
    MAX(CASE WHEN variable_type = 'output' AND variable_order = 1 THEN variable_name ELSE NULL END) AS output_name_1,
    MAX(CASE WHEN variable_type = 'output' AND variable_order = 1 THEN variable_value ELSE NULL END) AS output_value_1,
    MAX(CASE WHEN variable_type = 'output' AND variable_order = 2 THEN variable_name ELSE NULL END) AS output_name_2,
    MAX(CASE WHEN variable_type = 'output' AND variable_order = 2 THEN variable_value ELSE NULL END) AS output_value_2,
    MAX(CASE WHEN variable_type = 'output' AND variable_order = 3 THEN variable_name ELSE NULL END) AS output_name_3,
    MAX(CASE WHEN variable_type = 'output' AND variable_order = 3 THEN variable_value ELSE NULL END) AS output_value_3,
    MAX(CASE WHEN variable_type = 'output' AND variable_order = 4 THEN variable_name ELSE NULL END) AS output_name_4,
    MAX(CASE WHEN variable_type = 'output' AND variable_order = 4 THEN variable_value ELSE NULL END) AS output_value_4,
    MAX(CASE WHEN variable_type = 'output' AND variable_order = 5 THEN variable_name ELSE NULL END) AS output_name_5,
    MAX(CASE WHEN variable_type = 'output' AND variable_order = 5 THEN variable_value ELSE NULL END) AS output_value_5,
    MAX(CASE WHEN variable_type = 'output' AND variable_order = 6 THEN variable_name ELSE NULL END) AS output_name_6,
    MAX(CASE WHEN variable_type = 'output' AND variable_order = 6 THEN variable_value ELSE NULL END) AS output_value_6,
    i.creation_date,r.`name` AS recordingName,u.`name` AS userName,it.`name` AS indexName FROM index_log i 
            LEFT JOIN recording r ON r.recording_id = i.recording_id
            LEFT JOIN `user` u ON u.user_id = i.user_id
            LEFT JOIN index_type it ON it.index_id = i.index_id ";
        if (!Auth::isUserAdmin()) {
            $sql = $sql . ' WHERE i.user_id = ' . Auth::getUserLoggedID();
        }
        $sql = $sql . 'GROUP BY i.log_id,i.user_id,i.recording_id,i.index_id,i.version,i.min_time,i.max_time,i.min_frequency,i.max_frequency,i.creation_date)a ';
        if ($search) {
            $sql .= " WHERE CONCAT(IFNULL(log_id,''), IFNULL(recordingName,''), IFNULL(userName,''), IFNULL(indexName,''), IFNULL(min_time,''), IFNULL(max_time,''), IFNULL(min_frequency,''), IFNULL(max_frequency,''), IFNULL(channel,''), IFNULL(input_name_1,''), IFNULL(input_value_1,''), IFNULL(input_name_2,''), IFNULL(input_value_2,''), IFNULL(input_name_3,''), IFNULL(input_value_3,''), IFNULL(input_name_4,''), IFNULL(input_value_4,''), IFNULL(input_name_5,''), IFNULL(input_value_5,''), IFNULL(input_name_6,''), IFNULL(input_value_6,''), IFNULL(output_name_1,''), IFNULL(output_value_1,''), IFNULL(output_name_2,''), IFNULL(output_value_2,''), IFNULL(output_name_3,''), IFNULL(output_value_3,''), IFNULL(output_name_4,''), IFNULL(output_value_4,''), IFNULL(output_name_5,''), IFNULL(output_value_5,''), IFNULL(output_name_6,''), IFNULL(output_value_6,''), IFNULL(creation_date,''), IFNULL(version,'')) LIKE '%$search%' ";
        }
        $this->database->prepareQuery($sql);
        $count = count($this->database->executeSelect());
        return $count;
    }

    public function getListByPage(string $start = '0', string $length = '8', string $search = null, string $column = '0', string $dir = 'asc'): array
    {
        $arr = [];
        $sql = "SELECT * FROM (SELECT i.log_id,
    i.recording_id,
    i.user_id,
    i.index_id,
    i.version,
    i.min_time,
    i.max_time,
    i.min_frequency,
    i.max_frequency,
    MAX(CASE WHEN variable_type = 'input' AND variable_order = 1 THEN variable_value ELSE NULL END) AS channel,
    MAX(CASE WHEN variable_type = 'input' AND variable_order = 2 THEN variable_name ELSE NULL END) AS input_name_1,
    MAX(CASE WHEN variable_type = 'input' AND variable_order = 2 THEN variable_value ELSE NULL END) AS input_value_1,
    MAX(CASE WHEN variable_type = 'input' AND variable_order = 3 THEN variable_name ELSE NULL END) AS input_name_2,
    MAX(CASE WHEN variable_type = 'input' AND variable_order = 3 THEN variable_value ELSE NULL END) AS input_value_2,
    MAX(CASE WHEN variable_type = 'input' AND variable_order = 4 THEN variable_name ELSE NULL END) AS input_name_3,
    MAX(CASE WHEN variable_type = 'input' AND variable_order = 4 THEN variable_value ELSE NULL END) AS input_value_3,
    MAX(CASE WHEN variable_type = 'input' AND variable_order = 5 THEN variable_name ELSE NULL END) AS input_name_4,
    MAX(CASE WHEN variable_type = 'input' AND variable_order = 5 THEN variable_value ELSE NULL END) AS input_value_4,
    MAX(CASE WHEN variable_type = 'input' AND variable_order = 6 THEN variable_name ELSE NULL END) AS input_name_5,
    MAX(CASE WHEN variable_type = 'input' AND variable_order = 6 THEN variable_value ELSE NULL END) AS input_value_5,
    MAX(CASE WHEN variable_type = 'input' AND variable_order = 7 THEN variable_name ELSE NULL END) AS input_name_6,
    MAX(CASE WHEN variable_type = 'input' AND variable_order = 7 THEN variable_value ELSE NULL END) AS input_value_6,
    MAX(CASE WHEN variable_type = 'output' AND variable_order = 1 THEN variable_name ELSE NULL END) AS output_name_1,
    MAX(CASE WHEN variable_type = 'output' AND variable_order = 1 THEN variable_value ELSE NULL END) AS output_value_1,
    MAX(CASE WHEN variable_type = 'output' AND variable_order = 2 THEN variable_name ELSE NULL END) AS output_name_2,
    MAX(CASE WHEN variable_type = 'output' AND variable_order = 2 THEN variable_value ELSE NULL END) AS output_value_2,
    MAX(CASE WHEN variable_type = 'output' AND variable_order = 3 THEN variable_name ELSE NULL END) AS output_name_3,
    MAX(CASE WHEN variable_type = 'output' AND variable_order = 3 THEN variable_value ELSE NULL END) AS output_value_3,
    MAX(CASE WHEN variable_type = 'output' AND variable_order = 4 THEN variable_name ELSE NULL END) AS output_name_4,
    MAX(CASE WHEN variable_type = 'output' AND variable_order = 4 THEN variable_value ELSE NULL END) AS output_value_4,
    MAX(CASE WHEN variable_type = 'output' AND variable_order = 5 THEN variable_name ELSE NULL END) AS output_name_5,
    MAX(CASE WHEN variable_type = 'output' AND variable_order = 5 THEN variable_value ELSE NULL END) AS output_value_5,
    MAX(CASE WHEN variable_type = 'output' AND variable_order = 6 THEN variable_name ELSE NULL END) AS output_name_6,
    MAX(CASE WHEN variable_type = 'output' AND variable_order = 6 THEN variable_value ELSE NULL END) AS output_value_6,
    i.creation_date,r.`name` AS recordingName,u.`name` AS userName,it.`name` AS indexName FROM index_log i 
            LEFT JOIN recording r ON r.recording_id = i.recording_id
            LEFT JOIN `user` u ON u.user_id = i.user_id
            LEFT JOIN index_type it ON it.index_id = i.index_id ";
        if (!Auth::isUserAdmin()) {
            $sql = $sql . ' WHERE i.user_id = ' . Auth::getUserLoggedID();
        }
        $sql = $sql . 'GROUP BY i.log_id,i.user_id,i.recording_id,i.index_id,i.version,i.min_time,i.max_time,i.min_frequency,i.max_frequency,i.creation_date';
        $a = ['', 'i.log_id', 'r.name', 'u.name', 'it.name', 'i.version', 'i.min_time', 'i.max_time', 'i.min_frequency', 'i.max_frequency', 'channel', 'input_name_1', 'input_value_1', 'input_name_2', 'input_value_2', 'input_name_3', 'input_value_3', 'input_name_4', 'input_value_4', 'input_name_5', 'input_value_5', 'input_name_6', 'input_value_6', 'output_name_1', 'output_value_1', 'output_name_2', 'output_value_2', 'output_name_3', 'output_value_3', 'output_name_4', 'output_value_4', 'output_name_5', 'output_value_5', 'output_name_6', 'output_value_6', 'i.creation_date'];
        $sql .= " ORDER BY $a[$column] $dir LIMIT $length OFFSET $start)a ";
        if ($search) {
            $sql .= " WHERE CONCAT(IFNULL(log_id,''), IFNULL(recordingName,''), IFNULL(userName,''), IFNULL(indexName,''), IFNULL(min_time,''), IFNULL(max_time,''), IFNULL(min_frequency,''), IFNULL(max_frequency,''), IFNULL(channel,''), IFNULL(input_name_1,''), IFNULL(input_value_1,''), IFNULL(input_name_2,''), IFNULL(input_value_2,''), IFNULL(input_name_3,''), IFNULL(input_value_3,''), IFNULL(input_name_4,''), IFNULL(input_value_4,''), IFNULL(input_name_5,''), IFNULL(input_value_5,''), IFNULL(input_name_6,''), IFNULL(input_value_6,''), IFNULL(output_name_1,''), IFNULL(output_value_1,''), IFNULL(output_name_2,''), IFNULL(output_value_2,''), IFNULL(output_name_3,''), IFNULL(output_value_3,''), IFNULL(output_name_4,''), IFNULL(output_value_4,''), IFNULL(output_name_5,''), IFNULL(output_value_5,''), IFNULL(output_name_6,''), IFNULL(output_value_6,''), IFNULL(creation_date,''), IFNULL(version,'')) LIKE '%$search%' ";
        }
        $this->database->prepareQuery($sql);
        $result = $this->database->executeSelect();
        if (count($result)) {
            foreach ($result as $key => $value) {
                $arr[$key][] = "<input type='checkbox' class='js-checkbox'data-id='$value[log_id]' data-recording='$value[recording_id]' data-index='$value[index_id]' name='cb[$value[log_id]]' id='cb[$value[log_id]]'>";
                $arr[$key][] = $value['log_id'];
                $arr[$key][] = $value['recordingName'];
                $arr[$key][] = $value['userName'];
                $arr[$key][] = str_replace('_', ' ', $value['indexName']);
                $arr[$key][] = $value['version'];
                $arr[$key][] = $value['min_time'];
                $arr[$key][] = $value['max_time'];
                $arr[$key][] = $value['min_frequency'];
                $arr[$key][] = $value['max_frequency'];
                $arr[$key][] = $value['channel'];
                $arr[$key][] = $value['input_name_1'];
                $arr[$key][] = $value['input_value_1'] ? number_format(floatval($value['input_value_1']), 2, '.', ',') : '';
                $arr[$key][] = $value['input_name_2'];
                $arr[$key][] = $value['input_value_2'] ? number_format(floatval($value['input_value_2']), 2, '.', ',') : '';
                $arr[$key][] = $value['input_name_3'];
                $arr[$key][] = $value['input_value_3'] ? number_format(floatval($value['input_value_3']), 2, '.', ',') : '';
                $arr[$key][] = $value['input_name_4'];
                $arr[$key][] = $value['input_value_4'] ? number_format(floatval($value['input_value_4']), 2, '.', ',') : '';
                $arr[$key][] = $value['input_name_5'];
                $arr[$key][] = $value['input_value_5'] ? number_format(floatval($value['input_value_5']), 2, '.', ',') : '';
                $arr[$key][] = $value['input_name_6'];
                $arr[$key][] = $value['input_value_6'] ? number_format(floatval($value['input_value_6']), 2, '.', ',') : '';
                $arr[$key][] = $value['output_name_1'];
                $arr[$key][] = $value['output_value_1'] ? number_format(floatval($value['output_value_1']), 2, '.', ',') : '';
                $arr[$key][] = $value['output_name_2'];
                $arr[$key][] = $value['output_value_2'] ? number_format(floatval($value['output_value_2']), 2, '.', ',') : '';
                $arr[$key][] = $value['output_name_3'];
                $arr[$key][] = $value['output_value_3'] ? number_format(floatval($value['output_value_3']), 2, '.', ',') : '';
                $arr[$key][] = $value['output_name_4'];
                $arr[$key][] = $value['output_value_4'] ? number_format(floatval($value['output_value_4']), 2, '.', ',') : '';
                $arr[$key][] = $value['output_name_5'];
                $arr[$key][] = $value['output_value_5'] ? number_format(floatval($value['output_value_5']), 2, '.', ',') : '';
                $arr[$key][] = $value['output_name_6'];
                $arr[$key][] = $value['output_value_6'] ? number_format(floatval($value['output_value_6']), 2, '.', ',') : '';
                $arr[$key][] = $value['creation_date'];
            }
        }
        return $arr;
    }

    public function delete(string $id): void
    {
        $this->database->prepareQuery("DELETE FROM index_log WHERE (log_id,recording_id,index_id) IN ($id)");
        $this->database->executeDelete();
    }
}
