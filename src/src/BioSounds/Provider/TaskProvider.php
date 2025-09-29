<?php

namespace BioSounds\Provider;

use BioSounds\Entity\AbstractProvider;
use BioSounds\Utils\Auth;
use const http\Client\Curl\AUTH_ANY;


class TaskProvider extends AbstractProvider
{
    const TABLE_NAME = 'task';

    public function getTask(): array
    {
        $sql = "SELECT t.*,assigner.name AS assigner,assignee.name AS assignee,r.name as recording FROM task t  
                LEFT JOIN user assigner ON assigner.user_id = t.assigner_id
                LEFT JOIN user assignee ON assignee.user_id = t.assignee_id
                LEFT JOIN tag ON tag.tag_id = t.assigned_id AND t.type='tag'
				LEFT JOIN recording r ON (r.recording_id = t.assigned_id AND t.type='recording') OR (r.recording_id = tag.recording_id AND t.type='tag')
                WHERE t.assigner_id = " . Auth::getUserLoggedID() . " OR t.assignee_id = " . Auth::getUserLoggedID();
        $this->database->prepareQuery($sql);
        return $this->database->executeSelect();
    }

    public function getFilterCount(string $search): int
    {
        $sql = "SELECT t.* FROM task t 
                LEFT JOIN user assigner ON assigner.user_id = t.assigner_id
                LEFT JOIN user assignee ON assignee.user_id = t.assignee_id
                LEFT JOIN tag ON tag.tag_id = t.assigned_id AND t.type='tag'
				LEFT JOIN recording r ON (r.recording_id = t.assigned_id AND t.type='recording') OR (r.recording_id = tag.recording_id AND t.type='tag')
                WHERE t.assigner_id = " . Auth::getUserLoggedID() . " OR t.assignee_id = " . Auth::getUserLoggedID();
        $params = [];
        if ($search) {
            $sql .= " WHERE CONCAT(IFNULL(t.task_id,''), IFNULL(assigner.name,''), IFNULL(assignee.name,''), IFNULL(t.datetime,''), IFNULL(t.type,''), IFNULL(t.assigned_id,''), IFNULL(r.name,''), IFNULL(t.comment,''), IFNULL(t.status,'')) LIKE :search ";
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
        $sql = "SELECT t.*,assigner.name AS assigner,assignee.name AS assignee,r.name as recording,tag.min_time,tag.max_time,tag.min_freq,tag.max_freq,r.recording_id FROM task t  
                LEFT JOIN user assigner ON assigner.user_id = t.assigner_id
                LEFT JOIN user assignee ON assignee.user_id = t.assignee_id
                LEFT JOIN tag ON tag.tag_id = t.assigned_id AND t.type='tag'
				LEFT JOIN recording r ON (r.recording_id = t.assigned_id AND t.type='recording') OR (r.recording_id = tag.recording_id AND t.type='tag')
                WHERE t.assigner_id = " . Auth::getUserLoggedID() . " OR t.assignee_id = " . Auth::getUserLoggedID();
        $params = [
            ':length' => $length,
            ':start' => $start,
        ];
        if ($search) {
            $sql .= " WHERE CONCAT(IFNULL(t.task_id,''), IFNULL(assigner.name,''), IFNULL(assignee.name,''), IFNULL(t.datetime,''), IFNULL(t.type,''), IFNULL(t.assigned_id,''), IFNULL(r.name,''), IFNULL(t.comment,''), IFNULL(t.status,'')) LIKE :search ";
        }
        $a = ['', 't.task_id', 't.type', 'r.name', 't.assigned_id', 'assigner.name', 'assignee.name', 't.status', 't.comment', 't.datetime'];
        $sql .= " ORDER BY $a[$column] $dir LIMIT :length OFFSET :start ";
        $this->database->prepareQuery($sql);
        if ($search) {
            $params[':search'] = '%' . $search . '%';
        }
        $result = $this->database->executeSelect($params);
        if (count($result)) {
            foreach ($result as $key => $value) {
                $arr[$key][] = "<input type='checkbox' class='js-checkbox' data-id='$value[assigned_id]' data-recording='$value[recording_id]' data-type='$value[type]' data-assigner='$value[assigner_id]' data-assignee='$value[assignee_id]' data-status='$value[status]' name='cb[$value[task_id]]' id='cb[$value[task_id]]' data-tmin='$value[min_time]' data-tmax='$value[max_time]' data-fmin='$value[min_freq]' data-fmax='$value[max_freq]'>";
                $arr[$key][] = $value['task_id'];
                $arr[$key][] = $value['type'];
                $arr[$key][] = $value['recording'];
                $arr[$key][] = $value['assigned_id'];
                $arr[$key][] = $value['assigner'];
                $arr[$key][] = $value['assignee'];
                $arr[$key][] = $value['status'];
                $arr[$key][] = $value['comment'];
                $arr[$key][] = $value['datetime'];
            }
        }
        return $arr;
    }

    /**
     * @param int $id
     * @throws \Exception
     */
    public function delete(string $id): void
    {
        $this->database->prepareQuery("DELETE FROM task WHERE task_id IN ($id)");
        $this->database->executeDelete();
    }

    public function status($assigned_id, $type)
    {
        $this->database->prepareQuery("UPDATE task SET status = 'reviewed' WHERE assignee_id = " . AUTH::getUserLoggedID() . " AND type = :type AND assigned_id = :assigned_id");
        $this->database->executeUpdate([':assigned_id' => $assigned_id, ':type' => $type]);
    }
}
