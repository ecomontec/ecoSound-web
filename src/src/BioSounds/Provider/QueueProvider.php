<?php

namespace BioSounds\Provider;

use BioSounds\Entity\AbstractProvider;
use BioSounds\Entity\queue;
use BioSounds\Utils\Auth;
use Cassandra\Varint;
use DateTime;

class QueueProvider extends AbstractProvider
{
    const TABLE_NAME = "queue";

    /**
     * @return array
     * @throws \Exception
     */
    public function getList(): array
    {
        $sql = "SELECT * FROM queue WHERE user_id = :user_id AND error != '-1'";
        $this->database->prepareQuery($sql);
        return $this->database->executeSelect([':user_id' => Auth::getUserLoggedID()]);
    }

    /**
     * @param int $tagId
     * @return array|int
     * @throws \Exception
     */
    public function delete(string $queue_id)
    {
        $params = [];
        $ids = explode(',', $queue_id);
        $placeholders = [];
        foreach ($ids as $index => $value) {
            $placeholders[] = ":id$index";
            $params[":id$index"] = (int)$value;
        }
        $id_str = implode(', ', $placeholders);
        $this->database->prepareQuery("UPDATE queue SET error = '-1' WHERE (status!=0 OR error = 'being cancelled.') AND queue_id IN ($id_str)");
        $this->database->executeUpdate($params);
        $this->database->prepareQuery("UPDATE queue SET error = 'being cancelled.' WHERE status=0 AND queue_id IN ($id_str)");
        return $this->database->executeUpdate($params);
    }

    public function getQueue(): array
    {
        $sql = "SELECT * FROM queue WHERE user_id = :user_id AND (error != '-1' OR error IS NULL)";
        $this->database->prepareQuery($sql);
        return $this->database->executeSelect([':user_id' => Auth::getUserLoggedID()]);
    }

    public function getFilterCount(string $search): int
    {
        $sql = "SELECT * FROM queue WHERE user_id = :user_id AND (error != '-1' OR error IS NULL)";
        if ($search) {
            $sql .= " AND CONCAT(IFNULL(queue_id,''), IFNULL(type,''), IFNULL(completed,''), IFNULL(total,''), IFNULL(status,''), IFNULL(start_time,''), IFNULL(stop_time,''), IFNULL(warning,''), IFNULL(error,'')) LIKE ':search ";
        }
        $this->database->prepareQuery($sql);
        $params = [
            ':user_id' => Auth::getUserLoggedID(),
        ];
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
        $sql = "SELECT * FROM queue WHERE user_id = :user_id AND (error != '-1' OR error IS NULL)";
        if ($search) {
            $sql .= " AND CONCAT(IFNULL(queue_id,''), IFNULL(type,''), IFNULL(completed,''), IFNULL(total,''), IFNULL(status,''), IFNULL(start_time,''), IFNULL(stop_time,''), IFNULL(warning,''), IFNULL(error,'')) LIKE :search ";
        }
        $a = ['', 'queue_id', 'type', 'completed', 'total', 'status', 'start_time', 'stop_time', 'warning', 'error'];
        $sql .= " ORDER BY $a[$column] $dir";
        // Only add LIMIT if length is not -1 (DataTables "All" option sends -1)
        $params = [
            ':user_id' => Auth::getUserLoggedID(),
        ];
        if ($search) {
            $params[':search'] = '%' . $search . '%';
        }
        if ($length != '-1') {
            $sql .= " LIMIT :length OFFSET :start";
            $params[':length'] = (int)$length;
            $params[':start'] = (int)$start;
        }
        $this->database->prepareQuery($sql);
        $result = $this->database->executeSelect($params);
        if (count($result)) {
            foreach ($result as $key => $value) {
                $arr[$key][] = "<input type='checkbox' class='js-checkbox'data-id='$value[queue_id]' name='cb[$value[queue_id]]' id='cb[$value[queue_id]]'>";
                $arr[$key][] = $value['queue_id'];
                $arr[$key][] = $value['type'];
                $arr[$key][] = $value['completed'] . '/' . $value['total'];
                if ($value['status'] == '2') {
                    $arr[$key][] = '<div class="text-secondary">pending</div>';
                } else if ($value['status'] == '-2') {
                    $arr[$key][] = '<div class="text-warning">cancelled</div>';
                } elseif ($value['status'] == '1') {
                    $arr[$key][] = '<div class="text-success">finished</div>';
                } elseif ($value['status'] == '-1') {
                    $arr[$key][] = '<div class="text-danger">failed</div>';
                } else {
                    $arr[$key][] = '<div class="text-info">ongoing</div>';
                }
                $arr[$key][] = $value['start_time'];
                $arr[$key][] = $value['stop_time'];
                $time_diff = (new DateTime($value['start_time']))->diff(new DateTime($value['stop_time']));
                $arr[$key][] = $value['stop_time'] ? sprintf("%s%s%s", $time_diff->h > 0 ? $time_diff->h . "h " : "", $time_diff->i > 0 ? $time_diff->i . "m " : "", $time_diff->s . "s") : '';
                $arr[$key][] = $value['warning'];
                $arr[$key][] = $value['error'];
            }
        }
        return $arr;
    }
}
