<?php


namespace BioSounds\Provider;


use BioSounds\Entity\Species;
use BioSounds\Entity\Tag;
use BioSounds\Entity\User;
use BioSounds\Utils\Auth;

class TagProvider extends BaseProvider
{
    const TABLE_NAME = "tag";

    /**
     * @param int $tagId
     * @return Tag
     * @throws \Exception
     */
    public function get(int $tagId): Tag
    {
        $query = 'SELECT ' . Tag::ID . ', ' . Tag::RECORDING_ID . ', ' . User::FULL_NAME . ', ';
        $query .= self::TABLE_NAME . '.' . Tag::USER_ID . ' as user_id, ' . Tag::MIN_TIME . ', ' . Tag::MAX_TIME . ', ';
        $query .= Tag::MIN_FREQ . ', ' . Tag::MAX_FREQ . ', ';
        $query .= Tag::UNCERTAIN . ', ' . Tag:: REFERENCE_CALL . ', ' . Tag::CALL_DISTANCE . ', ';
        $query .= Tag::DISTANCE_NOT_ESTIMABLE . ', ' . Tag:: NUMBER_INDIVIDUALS . ', ' . Tag::COMMENTS . ', ';
        $query .= Tag::TYPE . ', ' . self::TABLE_NAME . '.' . Tag::SPECIES_ID . ', ' . Species::BINOMIAL . ' as species_name ';
        $query .= 'FROM ' . self::TABLE_NAME . ' ';
        $query .= 'LEFT JOIN ' . Species::TABLE_NAME . ' ON ';
        $query .= self::TABLE_NAME . '.' . Tag::SPECIES_ID . ' = ' . Species::TABLE_NAME . '.' . Species::ID . ' ';
        $query .= 'LEFT JOIN user ON ' . self::TABLE_NAME . '.' . Tag::USER_ID . ' = ';
        $query .= User::TABLE_NAME . '.' . User::ID . ' ';
        $query .= 'WHERE ' . self::TABLE_NAME . '.' . Tag::ID . ' = :tagId';

        $this->database->prepareQuery($query);
        if (empty($result = $this->database->executeSelect([':tagId' => $tagId]))) {
            throw new \Exception("Tag $tagId doesn't exist.");
        }

        return (new Tag())->createFromValues($result[0]);
    }

    /**
     * @param int $recordingId
     * @param int|null $userId
     * @return Tag[]
     * @throws \Exception
     */
    public function getList(int $recordingId, int $userId = null): array
    {
        $result = [];

        $query = 'SELECT tag_id, recording_id, min_time, max_time, min_freq, max_freq, user_id, uncertain, ';
        $query .= 'binomial as species_name, call_distance_m, distance_not_estimable, ';
        $query .= '(SELECT COUNT(*) FROM tag_review WHERE tag_id = tag.tag_id) AS review_number, ';
        $query .= '(( max_time - min_time ) + (max_freq - min_time )) AS time ';
        $query .= 'FROM tag LEFT JOIN species ON tag.species_id = species.species_id ';
        $query .= 'WHERE recording_id = :recordingId';

        $values[':recordingId'] = $recordingId;

        if (!empty($userId)) {
            $query .= ' AND user_id = :userId';
            $values[':userId'] = $userId;
        }
        $query .= ' ORDER BY time';

        $this->database->prepareQuery($query);
        foreach ($this->database->executeSelect($values) as $tag) {
            $result[] = (new Tag())->createFromValues($tag);
        }
        return $result;
    }

    /**
     * @param $data
     * @return int
     * @throws \Exception
     */
    public function insert($data): int
    {
        if (empty($data)) {
            return false;
        }

        $query = 'INSERT INTO tag %s VALUES %s';

        $fields = '( ';
        $valuesNames = '( ';
        $values = [];
        $i = 1;
        foreach ($data as $key => $value) {
            $fields .= $key;
            $valuesNames .= ':' . $key;
            $values[':' . $key] = $value;
            if ($i < count($data)) {
                $fields .= ', ';
                $valuesNames .= ', ';
            } else {
                $fields .= ' )';
                $valuesNames .= ' )';
            }
            $i++;
        }

        $this->database->prepareQuery(sprintf($query, $fields, $valuesNames));
        return $this->database->executeInsert($values);
    }

    /**
     * @param $data
     * @return array|bool|int
     * @throws \Exception
     */
    public function update($data)
    {
        if (empty($data) || empty($data['tag_id'])) {
            return false;
        }

        $query = 'UPDATE tag SET %s WHERE tag_id = :tagId';

        $fields = [];
        $values[':tagId'] = $data['tag_id'];
        unset($data['tag_id']);

        foreach ($data as $key => $value) {
            $fields[] = $key . '= :' . $key;
            $values[':' . $key] = $value;
        }

        $this->database->prepareQuery(sprintf($query, implode(', ', $fields)));
        return $this->database->executeUpdate($values);
    }

    /**
     * @param int $tagId
     * @return array|int
     * @throws \Exception
     */
    public function delete(int $tagId)
    {
        $this->database->prepareQuery('DELETE FROM tag WHERE tag_id = :tagId');
        return $this->database->executeDelete([':tagId' => $tagId]);
    }

    public function getTagPages(int $limit, int $offSet): array
    {
        $this->database->prepareQuery(
            "SELECT t.*,s.binomial AS speciesName,r.`name` AS recordingName,u.`name` AS userName,st.`name` AS typeName FROM tag t 
            INNER JOIN recording r ON r.recording_id = t.recording_id
            LEFT JOIN species s ON s.species_id = t.species_id
            LEFT JOIN collection c ON c.collection_id = r.col_id
            LEFT JOIN user u ON u.user_id = t.user_id
            LEFT JOIN sound_type st ON st.sound_type_id = t.type
            WHERE t.user_id = :user_id1 OR c.user_id = :user_id2
            ORDER BY t.tag_id LIMIT :limit OFFSET :offset"
        );

        $result = $this->database->executeSelect([
            ':limit' => $limit,
            ':offset' => $offSet,
            ":user_id1" => Auth::getUserLoggedID(),
            ":user_id2" => Auth::getUserLoggedID()
        ]);

        $data = [];
        foreach ($result as $item) {
            $data[] = (new Tag())
                ->setId($item['tag_id'])
                ->setSpeciesName($item['speciesName'])
                ->setRecordingName($item['recordingName'])
                ->setUserName($item['userName'])
                ->setMinTime($item['min_time'])
                ->setMaxTime($item['max_time'])
                ->setMinFrequency($item['min_freq'])
                ->setMaxFrequency($item['max_freq'])
                ->setUncertain($item['uncertain'])
                ->setCallDistance($item['call_distance_m'])
                ->setDistanceNotEstimable(isset($item['distance_not_estimable']) ? $item['distance_not_estimable'] : 0)
                ->setNumberIndividuals($item['number_of_individuals'])
                ->setType($item['typeName'])
                ->setReferenceCall($item['reference_call'])
                ->setComments($item['comments'])
                ->setCreationDate($item['creation_date']);
        }
        return $data;
    }

    /**
     * @return Tag[]
     * @throws \Exception
     */
    public function getListByTags(): array
    {
        $data = [];
        $this->database->prepareQuery(
            "SELECT t.*,s.binomial AS speciesName,r.`name` AS recordingName,u.`name` AS userName,st.`name` AS typeName FROM tag t 
            INNER JOIN recording r ON r.recording_id = t.recording_id
            LEFT JOIN species s ON s.species_id = t.species_id
            LEFT JOIN collection c ON c.collection_id = r.col_id
            LEFT JOIN user u ON u.user_id = t.user_id
            LEFT JOIN sound_type st ON st.sound_type_id = t.type
            WHERE t.user_id = :user_id1 OR c.user_id = :user_id2
            ORDER BY t.tag_id"
        );
        $result = $this->database->executeSelect([":user_id1" => Auth::getUserLoggedID(), ":user_id2" => Auth::getUserLoggedID()]);

        foreach ($result as $item) {
            $data[] = (new Tag())
                ->setId($item['tag_id'])
                ->setSpeciesName($item['speciesName'])
                ->setRecordingName($item['recordingName'])
                ->setUserName($item['userName'])
                ->setMinTime($item['min_time'])
                ->setMaxTime($item['max_time'])
                ->setMinFrequency($item['min_freq'])
                ->setMaxFrequency($item['max_freq'])
                ->setUncertain($item['uncertain'])
                ->setCallDistance($item['call_distance_m'])
                ->setDistanceNotEstimable(isset($item['distance_not_estimable']) ? $item['distance_not_estimable'] : 0)
                ->setNumberIndividuals($item['number_of_individuals'])
                ->setType($item['typeName'])
                ->setReferenceCall($item['reference_call'])
                ->setComments($item['comments'])
                ->setCreationDate($item['creation_date']);
        }

        return $data;
    }

    public function countTags(): int
    {
        $this->database->prepareQuery(
            "SELECT COUNT(*) AS num FROM tag t 
            INNER JOIN recording r ON t.recording_id = r.recording_id
            LEFT JOIN collection c ON c.collection_id = r.col_id
            WHERE t.user_id = :user_id1 OR c.user_id = :user_id2"
        );

        if (empty($result = $this->database->executeSelect([":user_id1" => Auth::getUserLoggedID(), ":user_id2" => Auth::getUserLoggedID()]))) {
            return 0;
        }
        return $result[0]['num'];
    }
}