<?php

namespace BioSounds\Provider;

use BioSounds\Entity\AbstractProvider;
use BioSounds\Entity\queue;
use BioSounds\Utils\Auth;
use Cassandra\Varint;
use DateTime;

class queueProvider extends AbstractProvider
{
    const TABLE_NAME = "queue";

    /**
     * @return array
     * @throws \Exception
     */
    public function getList(): array
    {
        $sql = "SELECT * FROM queue WHERE user_id = " . Auth::getUserLoggedID() . " AND error != '-1'";
        $this->database->prepareQuery($sql);
        return $this->database->executeSelect();
    }

    /**
     * @param int $tagId
     * @return array|int
     * @throws \Exception
     */
    public function delete(string $queue_id)
    {
        $this->database->prepareQuery("UPDATE queue SET error = '-1' WHERE status!=0 AND queue_id IN ($queue_id)");
        $this->database->executeUpdate();
        $this->database->prepareQuery("UPDATE queue SET status='-2' WHERE status=0 AND queue_id IN ($queue_id)");
        return $this->database->executeUpdate();
    }

    public function getQueue(): array
    {
        $sql = "SELECT * FROM queue WHERE user_id = " . Auth::getUserLoggedID() . " AND (error != '-1' OR error IS NULL)";
        $this->database->prepareQuery($sql);
        return $this->database->executeSelect();
    }

    public function getFilterCount(string $search): int
    {
        $sql = "SELECT * FROM queue WHERE user_id = " . Auth::getUserLoggedID() . " AND (error != '-1' OR error IS NULL)";
        if ($search) {
            $sql .= " AND CONCAT(IFNULL(queue_id,''), IFNULL(type,''), IFNULL(completed,''), IFNULL(total,''), IFNULL(status,''), IFNULL(start_time,''), IFNULL(stop_time,''), IFNULL(warning,''), IFNULL(error,'')) LIKE '%$search%' ";
        }
        $this->database->prepareQuery($sql);
        $count = count($this->database->executeSelect());
        return $count;
    }

    public function getListByPage(string $start = '0', string $length = '8', string $search = null, string $column = '0', string $dir = 'asc'): array
    {
        $arr = [];
        $sql = "SELECT * FROM queue WHERE user_id = " . Auth::getUserLoggedID() . " AND (error != '-1' OR error IS NULL)";
        if ($search) {
            $sql .= " AND CONCAT(IFNULL(queue_id,''), IFNULL(type,''), IFNULL(completed,''), IFNULL(total,''), IFNULL(status,''), IFNULL(start_time,''), IFNULL(stop_time,''), IFNULL(warning,''), IFNULL(error,'')) LIKE '%$search%' ";
        }
        $a = ['', 'queue_id', 'type', 'completed', 'total', 'status', 'start_time', 'stop_time', 'warning', 'error'];
        $sql .= " ORDER BY $a[$column] $dir LIMIT $length OFFSET $start";
        $this->database->prepareQuery($sql);
        $result = $this->database->executeSelect();
        if (count($result)) {
            foreach ($result as $key => $value) {
                $arr[$key][] = "<input type='checkbox' class='js-checkbox'data-id='$value[queue_id]' name='cb[$value[queue_id]]' id='cb[$value[queue_id]]'>";
                $arr[$key][] = $value['queue_id'];
                $arr[$key][] = $value['type'];
                $arr[$key][] = $value['completed'] . '/' . $value['total'];
                if ($value['status'] == '-2') {
                    if ($value['stop_time']) {
                        $arr[$key][] = '<div class="text-warning">cancelled</div>';
                    } else {
                        $arr[$key][] = '<div class="text-dark">being cancelled</div>';
                    }
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
