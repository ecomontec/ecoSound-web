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
        $params = [];
        if (!Auth::isUserAdmin()) {
            $sql .= ' WHERE i.user_id = :user_id';
            $params[':user_id'] = Auth::getUserLoggedID();
        }
        $sql = $sql . " ORDER BY i.log_id";
        $this->database->prepareQuery($sql);
        return $this->database->executeSelect($params);
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
        $params = [];
        $ids = explode(',', $recording_id);
        $placeholders = [];
        foreach ($ids as $index => $value) {
            $placeholders[] = ":id$index";
            $params[":id$index"] = (int)$value;
        }
        $id_str = implode(', ', $placeholders);
        $this->database->prepareQuery("DELETE FROM index_log WHERE recording_id IN ($id_str)");
        return $this->database->executeDelete($params);
    }

    public function getIndexLog(): array
    {
        $sql = "SELECT i.*,r.`name` AS recordingName,u.`name` AS userName,it.`name` AS indexName FROM index_log i 
            LEFT JOIN recording r ON r.recording_id = i.recording_id
            LEFT JOIN user u ON u.user_id = i.user_id
            LEFT JOIN index_type it ON it.index_id = i.index_id ";
        $params = [];
        if (!Auth::isUserAdmin()) {
            $sql = $sql . ' WHERE i.user_id = :user_id ';
            $params[':user_id'] = Auth::getUserLoggedID();
        }
        $this->database->prepareQuery($sql);
        return $this->database->executeSelect($params);
    }

    public function getFilterCount(string $search): int
    {
        $sql = "SELECT i.*,r.`name` AS recordingName,u.`name` AS userName,it.`name` AS indexName FROM index_log i 
            LEFT JOIN recording r ON r.recording_id = i.recording_id
            LEFT JOIN `user` u ON u.user_id = i.user_id
            LEFT JOIN index_type it ON it.index_id = i.index_id ";
        $params = [];
        if (!Auth::isUserAdmin()) {
            $sql = $sql . ' WHERE i.user_id = :user_id ';
            $params[':user_id'] = Auth::getUserLoggedID();
        }
        if ($search) {
            $sql .= Auth::isUserAdmin() ? ' WHERE ' : ' AND ';
            $sql .= " CONCAT(IFNULL(i.log_id,''), IFNULL(r.name,''), IFNULL(u.name,''), IFNULL(it.name,''), IFNULL(i.min_time,''), IFNULL(i.max_time,''), IFNULL(i.min_frequency,''), IFNULL(i.max_frequency,''), IFNULL(i.variable_type,''), IFNULL(i.variable_order,''), IFNULL(i.variable_name,''), IFNULL(i.variable_value,''), IFNULL(i.creation_date,''), IFNULL(i.version,'')) LIKE :search ";
        }
        $this->database->prepareQuery($sql);
        if ($search) {
            $params[':search'] = '%' . $search . '%';
        }
        $count = count($this->database->executeSelect($params));
        return $count;
    }

    public function getListByPage(string $start = '0', string $length = '8', string $search = null, string $column = '0', string $dir = 'asc'): array
    {
        $arr = [];
        $dir = ($dir === 'asc' || $dir === 'desc') ? $dir : 'asc';
        $sql = "SELECT i.*,r.`name` AS recordingName,u.`name` AS userName,it.`name` AS indexName FROM index_log i 
            LEFT JOIN recording r ON r.recording_id = i.recording_id
            LEFT JOIN `user` u ON u.user_id = i.user_id
            LEFT JOIN index_type it ON it.index_id = i.index_id ";
        $params = [
            ':length' => $length,
            ':start' => $start,
        ];
        if (!Auth::isUserAdmin()) {
            $sql = $sql . ' WHERE i.user_id = :user_id ';
            $params[':user_id'] = Auth::getUserLoggedID();
        }
        if ($search) {
            $sql .= Auth::isUserAdmin() ? ' WHERE ' : ' AND ';
            $sql .= " CONCAT(IFNULL(i.log_id,''), IFNULL(r.name,''), IFNULL(u.name,''), IFNULL(it.name,''), IFNULL(i.min_time,''), IFNULL(i.max_time,''), IFNULL(i.min_frequency,''), IFNULL(i.max_frequency,''), IFNULL(i.variable_type,''), IFNULL(i.variable_order,''), IFNULL(i.variable_name,''), IFNULL(i.variable_value,''), IFNULL(i.creation_date,''), IFNULL(i.version,'')) LIKE :search ";
        }
        $a = ['', 'i.log_id', 'r.name', 'u.name', 'it.name', 'i.version', 'i.min_time', 'i.max_time', 'i.min_frequency', 'i.max_frequency', 'i.variable_type', 'i.variable_order', 'i.variable_name', 'i.variable_value', 'i.creation_date'];
        $sql .= " ORDER BY $a[$column] $dir LIMIT :length OFFSET :start ";
        $this->database->prepareQuery($sql);
        if ($search) {
            $params[':search'] = '%' . $search . '%';
        }
        $result = $this->database->executeSelect($params);
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
                $arr[$key][] = $value['variable_type'];
                $arr[$key][] = $value['variable_order'];
                $arr[$key][] = $value['variable_name'];
                $arr[$key][] = $value['variable_value'];
                $arr[$key][] = $value['creation_date'];
            }
        }
        return $arr;
    }

    public function delete(string $id): void
    {
        $id = trim($id);
        if (!preg_match('/^(\(\s*\d+\s*,\s*\d+\s*,\s*\d+\s*\)(\s*,\s*\(\s*\d+\s*,\s*\d+\s*,\s*\d+\s*\))*)$/', $id)) {
            return;
        } else {
            $this->database->prepareQuery("DELETE FROM index_log WHERE (log_id,recording_id,index_id) IN ($id)");
            $this->database->executeDelete();
        }
    }
}
