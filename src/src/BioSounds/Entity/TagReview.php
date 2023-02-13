<?php

namespace BioSounds\Entity;

use BioSounds\Database\Database;
use BioSounds\Provider\BaseProvider;

class TagReview extends BaseProvider
{
    const TABLE_NAME = 'tag_review';
    const TAG = 'tag_id';
    const USER = 'user_id';
    const STATUS = 'tag_review_status_id';
    const SPECIES = 'species_id';
    const COMMENTS = 'note';
    const DATE = 'creation_date';

    //TODO: Create ReviewStatus model
    const STATUS_TABLE_NAME = 'tag_review_status';
    const STATUS_NAME = 'name';

    /**
     * @param int $tagId
     * @return array
     * @throws \Exception
     */
    public function getListByTag(int $tagId): array
    {
        $query = 'SELECT user.name as reviewer, species.binomial, tag_review_status.name as status, tag_review.' .
            self::DATE . ' FROM ' . self::TABLE_NAME . ' LEFT JOIN ' . Species::TABLE_NAME . ' ON ' .
            Species::TABLE_NAME . '.' . Species::ID . ' = tag_review.' . self::SPECIES .
            ' LEFT JOIN ' . User::TABLE_NAME . ' ON ' . User::TABLE_NAME . '.' . User::ID .
            ' = tag_review.' . self::USER . ' LEFT JOIN ' . self::STATUS_TABLE_NAME .
            ' ON ' . self::STATUS_TABLE_NAME . '.tag_review_status_id = tag_review.' . self::STATUS .
            ' WHERE ' . self::TAG . ' = :tagId ORDER BY tag_review.' . self::DATE;

        $this->database->prepareQuery($query);
        return $this->database->executeSelect([':tagId' => $tagId]);
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
}
