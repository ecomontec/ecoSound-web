<?php

namespace BioSounds\Entity;

use BioSounds\Database\Database;
use BioSounds\Provider\BaseProvider;
use BioSounds\Utils\Auth;

class TagReview extends AbstractProvider
{
    const TABLE_NAME = 'tag_review';
    const TAG = 'tag_id';
    const USER = 'user_id';
    const STATUS = 'tag_review_status_id';
    const SPECIES = 'species_id';
    const COMMENTS = 'note';
    const DATE = 'creation_date';
    const STATUS_TABLE_NAME = 'tag_review_status';
    const STATUS_NAME = 'name';

    /**
     * @param int $tagId
     * @return array
     * @throws \Exception
     */
    public function getListByTag(int $tagId): array
    {
        $query = 'SELECT user.name as reviewer,user.user_id as reviewer_id, taxon.binomial, tag_review_status.name as status, tag_review.' .
            self::DATE . ' FROM ' . self::TABLE_NAME . ' LEFT JOIN ' . Species::TABLE_NAME . ' ON ' .
            Species::TABLE_NAME . '.' . Species::ID . ' = tag_review.' . self::SPECIES .
            ' LEFT JOIN ' . User::TABLE_NAME . ' ON ' . User::TABLE_NAME . '.' . User::ID .
            ' = tag_review.' . self::USER . ' LEFT JOIN ' . self::STATUS_TABLE_NAME .
            ' ON ' . self::STATUS_TABLE_NAME . '.tag_review_status_id = tag_review.' . self::STATUS .
            ' WHERE ' . self::TAG . ' = :tagId ORDER BY tag_review.' . self::DATE;

        $this->database->prepareQuery($query);
        return $this->database->executeSelect([':tagId' => $tagId]);
    }

    public function getReview(int $collection_id): array
    {
        $sql = "SELECT tr.*,u.name AS user, trs.name AS tag_review_status, s.binomial AS species FROM tag_review tr 
            LEFT JOIN tag t ON tr.tag_id = t.tag_id
            LEFT JOIN recording r ON r.recording_id = t.recording_id
            LEFT JOIN taxon s ON s.taxon_id = tr.species_id
            LEFT JOIN user u ON u.user_id = tr.user_id
            LEFT JOIN tag_review_status trs ON tr.tag_review_status_id = trs.tag_review_status_id WHERE r.col_id = $collection_id";
        if (!Auth::isManage()) {
            $sql .= " AND t.user_id = " . Auth::getUserID();
        }
        $sql .= ' ORDER BY t.tag_id';

        $this->database->prepareQuery($sql);
        return $this->database->executeSelect();
    }

    /**
     * @param int $userId
     * @param int $tagId
     * @return bool
     * @throws \Exception
     */
    public function hasUserReviewed(?int $userId, int $tagId): bool
    {
        $this->database->prepareQuery(
            'SELECT COUNT(*) as countReviews FROM ' . self::TABLE_NAME .
            ' WHERE ' . self::USER . '= :userId AND ' . self::TAG . '= :tagId'
        );
        $result = $this->database->executeSelect([':userId' => $userId, ':tagId' => $tagId]);
        if (!empty($result) && $result[0]['countReviews'] > 0) {
            return true;
        }
        return false;
    }

    /**
     * @param array $data
     * @return int
     * @throws \Exception
     */
    public function insert(array $data): int
    {
        if (empty($data)) {
            return false;
        }

        $query = 'INSERT INTO tag_review (%s) VALUES (%s)';

        $fields = [];
        $values = [];
        foreach ($data as $key => $value) {
            $fields[$key] = ':' . $key;
            $values[':' . $key] = $value;
        }
        $this->database->prepareQuery(sprintf($query, implode(', ', array_keys($fields)), implode(', ', $fields)));
        return $this->database->executeInsert($values);
    }

    public function update($data)
    {
        if (empty($data)) {
            return false;
        }

        $fields = [];
        $values = [];

        foreach ($data as $key => $value) {
            $key = substr($key, 0, strrpos($key, '_'));
            if ($key == 'species_id' && $value == '') {
                continue;
            }
            if ($key == '') {
                continue;
            }
            $fields[] = $key . " = :" . $key;
            $values[":" . $key] = $value;

        }

        $query = 'UPDATE tag_review SET ' . implode(", ", $fields) . ' ';
        $query .= "WHERE tag_id = $data[tag_id_hidden] AND user_id = $data[user_id_hidden]";

        $this->database->prepareQuery($query);
        return $this->database->executeUpdate($values);
    }

    public function delete(string $str)
    {
        $data = explode(',', $str);
        $sql = "DELETE FROM tag_review WHERE ";
        foreach ($data as $key => $d) {
            $value = explode('-', $d);
            $sql .= " (tag_id = $value[0] AND user_id = $value[1]) ";
            if ($key < count($data) - 1) {
                $sql .= " OR ";
            }
        }
        $this->database->prepareQuery($sql);
        return $this->database->executeDelete();
    }
}
