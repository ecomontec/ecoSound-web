<?php


namespace BioSounds\Provider;


use BioSounds\Entity\AbstractProvider;
use BioSounds\Entity\User;
use BioSounds\Utils\Auth;
use Cassandra\Varint;

class TagReviewProvider extends AbstractProvider
{
    const TABLE_NAME = "tag_review";

    public function getReview(string $collectionId): array
    {
        $sql = "SELECT tr.*,r.recording_id,r.`name` AS recording,u.`name` AS username,trs.`name` as state,s.binomial as specie FROM tag_review tr 
                LEFT JOIN tag t ON t.tag_id = tr.tag_id 
                LEFT JOIN recording r ON r.recording_id = t.recording_id 
                LEFT JOIN `user` u ON u.user_id = tr.user_id
                LEFT JOIN tag_review_status trs ON trs.tag_review_status_id = tr.tag_review_status_id
                LEFT JOIN species s ON s.species_id = tr.species_id WHERE r.col_id = $collectionId";
        if (!(new User())->isManage($_SESSION['user_id'], $collectionId)) {
            $sql .= " AND tr.user_id = " . Auth::getUserID();
        }
        $this->database->prepareQuery($sql);
        return $this->database->executeSelect();
    }

    public function getFilterCount(string $collectionId, string $search): int
    {
        $sql = "SELECT tr.*,r.`name` AS recording,u.`name` AS username,trs.`name` as state,s.binomial as specie FROM tag_review tr 
                LEFT JOIN tag t ON t.tag_id = tr.tag_id 
                LEFT JOIN recording r ON r.recording_id = t.recording_id 
                LEFT JOIN `user` u ON u.user_id = tr.user_id
                LEFT JOIN tag_review_status trs ON trs.tag_review_status_id = tr.tag_review_status_id
                LEFT JOIN species s ON s.species_id = tr.species_id WHERE r.col_id = $collectionId";
        if (!(new User())->isManage($_SESSION['user_id'], $collectionId)) {
            $sql .= " AND tr.user_id = " . Auth::getUserID();
        }
        if ($search) {
            $sql .= " AND CONCAT(IFNULL(tr.tag_id,''), IFNULL(r.`name`,''), IFNULL(u.`name`,''), IFNULL(trs.`name`,''), IFNULL(s.binomial,''), IFNULL(tr.note,'')) LIKE '%$search%' ";
        }
        $this->database->prepareQuery($sql);
        $count = count($this->database->executeSelect());
        return $count;
    }

    public function getStatus()
    {
        $sql = "SELECT * FROM tag_review_status ORDER BY tag_review_status_id";
        $this->database->prepareQuery($sql);
        return $this->database->executeSelect();
    }

    public function getListByPage(string $collectionId, string $start = '0', string $length = '8', string $search = null, string $column = '0', string $dir = 'asc'): array
    {
        $arr = [];
        $sql = "SELECT tr.*,t.species_id AS tag_species,t.min_time,t.max_time,t.min_freq,t.max_freq,r.`name` AS recording,r.recording_id,u.`name` AS username,trs.`name` as state,s.binomial as specie FROM tag_review tr 
                LEFT JOIN tag t ON t.tag_id = tr.tag_id 
                LEFT JOIN recording r ON r.recording_id = t.recording_id 
                LEFT JOIN `user` u ON u.user_id = tr.user_id
                LEFT JOIN tag_review_status trs ON trs.tag_review_status_id = tr.tag_review_status_id
                LEFT JOIN species s ON s.species_id = tr.species_id WHERE r.col_id = $collectionId";
        if (!(new User())->isManage($_SESSION['user_id'], $collectionId)) {
            $sql .= " AND tr.user_id = " . Auth::getUserID();
        }
        if ($search) {
            $sql .= " AND CONCAT(IFNULL(tr.tag_id,''), IFNULL(r.`name`,''), IFNULL(u.`name`,''), IFNULL(trs.`name`,''), IFNULL(s.binomial,''), IFNULL(tr.note,'')) LIKE '%$search%' ";
        }
        $a = ['', 't.tag_id', 'r.`name`', 'u.`name`', 'trs.`name`', 's.binomial', 'tr.note'];
        $sql .= " ORDER BY $a[$column] $dir LIMIT $length OFFSET $start";
        $this->database->prepareQuery($sql);
        $result = $this->database->executeSelect();
        $status = $this->getStatus();
        if (count($result)) {
            foreach ($result as $key => $value) {
                $str_status = '';
                foreach ($status as $s) {
                    $str_status .= "<option value='" . $s['tag_review_status_id'] . "' " . ($value['state'] == $s['name'] ? 'selected' : '') . ">" . $s['name'] . "</option>";
                }
                $arr[$key][] = "<input type='checkbox' class='js-checkbox' data-id='$value[tag_id]-$value[user_id]' data-recording-id='$value[recording_id]' data-tmin='$value[min_time]' data-tmax='$value[max_time]' data-fmin='$value[min_freq]' data-fmax='$value[max_freq]' id='cb[$value[tag_id]-$value[user_id]'>";
                $arr[$key][] = $value['tag_id'] .
                    "<input class='js-species-id$value[tag_id]-$value[user_id]' data-type='edit' name='species_id' type='hidden' value='$value[species_id]'>
                     <input id='old_id$value[tag_id]-$value[user_id]' type='hidden' value='$value[species_id]'>
                     <input id='old_name$value[tag_id]-$value[user_id]' type='hidden' value='$value[specie]'>
                     <input id='tag_species$value[tag_id]-$value[user_id]' type='hidden' value='$value[tag_species]'>
                     <input type='hidden' name='tag_id' value='$value[tag_id]'>
                     <input type='hidden' name='user_id' value='$value[user_id]'>";
                $arr[$key][] = $value['recording'];
                $arr[$key][] = $value['username'];
                $arr[$key][] = "<select name='tag_review_status_id' class='form-control form-control-sm' style='width:180px;'>$str_status</select>";
                $arr[$key][] = "<input id='speciesName_$value[tag_id]-$value[user_id]' style='width:150px;" . ($value['state'] != 'Corrected' ? 'display:none' : '') . "' data-type='edit' class='form-control form-control-sm js-species-autocomplete' type='text' size='30' value='$value[specie]'>";
                $arr[$key][] = "<input type='text' class='form-control form-control-sm' name='note' style='width:400px;' value='$value[note]'>";
                $arr[$key][] = $value['creation_date'];
            }
        }
        return $arr;
    }
}