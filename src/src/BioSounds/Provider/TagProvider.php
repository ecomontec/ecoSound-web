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
        $query = 'SELECT tag.*,sound.phony,sound.sound_type, species.taxon_order, species.class, user.name ';
        $query .= ', ' . Species::BINOMIAL . ' as species_name , c.public_tags ';
        $query .= 'FROM ' . self::TABLE_NAME . ' ';
        $query .= 'LEFT JOIN ' . Species::TABLE_NAME . ' ON ';
        $query .= self::TABLE_NAME . '.' . Tag::SPECIES_ID . ' = ' . Species::TABLE_NAME . '.' . Species::ID . ' ';
        $query .= 'LEFT JOIN user ON ' . self::TABLE_NAME . '.' . Tag::USER_ID . ' = ';
        $query .= User::TABLE_NAME . '.' . User::ID . ' ';
        $query .= 'LEFT JOIN sound ON sound.sound_id = tag.sound_id ';
        $query .= 'LEFT JOIN recording r ON r.recording_id = tag.recording_id ';
        $query .= 'LEFT JOIN collection c ON c.collection_id = r.col_id ';
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

        $query = 'SELECT tag.tag_id, tag.recording_id, tag.min_time, tag.max_time, tag.min_freq, tag.max_freq, tag.user_id, tag.uncertain,sound.phony,sound.sound_type, ';
        $query .= 'species.binomial as species_name, tag.sound_distance_m, tag.distance_not_estimable, ';
        $query .= '(SELECT COUNT(*) FROM tag_review WHERE tag_id = tag.tag_id) AS review_number, ';
        $query .= '(( tag.max_time - tag.min_time ) + (tag.max_freq - tag.min_time )) AS time ';
        $query .= 'FROM tag LEFT JOIN species ON tag.species_id = species.species_id ';
        $query .= 'LEFT JOIN sound ON tag.sound_id = sound.sound_id ';
        $query .= 'LEFT JOIN recording r ON r.recording_id = tag.recording_id ';
        $query .= 'LEFT JOIN collection c ON c.collection_id = r.col_id ';
        $query .= 'WHERE tag.recording_id = :recordingId';

        $values[':recordingId'] = $recordingId;

        if (!empty($userId)) {
            $query .= ' AND (tag.user_id = :userId OR c.public_tags = 1) ';
            $values[':userId'] = $userId;
        }
        $query .= ' ORDER BY tag.min_time,tag.max_time,tag.tag_id';
        $this->database->prepareQuery($query);
        foreach ($this->database->executeSelect($values) as $tag) {
            $result[] = (new Tag())->createFromValues($tag);
        }
        return $result;
    }
    /**
     * @param int $recordingId
     * @param int|null $userId
     * @return Tag[]
     * @throws \Exception
     */
    public function getListByTime(int $recordingId, int $userId = null): array
    {
        $result = [];

        $query = 'SELECT tag.tag_id, tag.recording_id, tag.min_time, tag.max_time, tag.min_freq, tag.max_freq, tag.user_id, tag.uncertain,sound.phony,sound.sound_type, ';
        $query .= 'species.binomial as species_name, tag.sound_distance_m, tag.distance_not_estimable, ';
        $query .= '(SELECT COUNT(*) FROM tag_review WHERE tag_id = tag.tag_id) AS review_number, ';
        $query .= '(( tag.max_time - tag.min_time ) + (tag.max_freq - tag.min_time )) AS time ';
        $query .= 'FROM tag LEFT JOIN species ON tag.species_id = species.species_id ';
        $query .= 'LEFT JOIN sound ON tag.sound_id = sound.sound_id ';
        $query .= 'LEFT JOIN recording r ON r.recording_id = tag.recording_id ';
        $query .= 'LEFT JOIN collection c ON c.collection_id = r.col_id ';
        $query .= 'WHERE tag.recording_id = :recordingId';

        $values[':recordingId'] = $recordingId;

        if (!empty($userId)) {
            $query .= ' AND (tag.user_id = :userId OR c.public_tags = 1) ';
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

    public function getTagPagesByCollection(int $colId): array
    {
        $sql = "SELECT t.*,sound.phony,sound.sound_type,s.binomial AS speciesName,r.`name` AS recordingName,u.`name` AS userName,st.`name` AS typeName,s.taxon_order AS TaxonOrder,s.class AS TaxonClass FROM tag t 
            INNER JOIN recording r ON r.recording_id = t.recording_id
            LEFT JOIN species s ON s.species_id = t.species_id
            LEFT JOIN collection c ON c.collection_id = r.col_id
            LEFT JOIN user u ON u.user_id = t.user_id
            LEFT JOIN sound ON sound.sound_id = t.sound_id
            LEFT JOIN sound_type st ON st.sound_type_id = t.animal_sound_type WHERE c.collection_id = :colId ";
        if (!Auth::isManage()) {
            $sql .= " AND t.user_id = " . Auth::getUserID() ;
        }
        $sql .= " ORDER BY t.tag_id";
        $this->database->prepareQuery($sql);

        $result = $this->database->executeSelect([":colId" => $colId,]);

        $data = [];
        foreach ($result as $item) {
            $data[] = (new Tag())
                ->setId($item['tag_id'])
                ->setSpeciesId($item['species_id'])
                ->setSpeciesName($item['speciesName'])
                ->setRecording($item['recording_id'])
                ->setRecordingName($item['recordingName'])
                ->setUserName($item['userName'])
                ->setMinTime($item['min_time'])
                ->setMaxTime($item['max_time'])
                ->setMinFrequency($item['min_freq'])
                ->setMaxFrequency($item['max_freq'])
                ->setUncertain(isset($item['uncertain']) ? $item['uncertain'] : 0)
                ->setCallDistance($item['sound_distance_m'])
                ->setDistanceNotEstimable(isset($item['distance_not_estimable']) ? $item['distance_not_estimable'] : 0)
                ->setNumberIndividuals($item['individuals'])
                ->setTypeId($item['animal_sound_type'])
                ->setType($item['typeName'])
                ->setReferenceCall($item['reference_call'])
                ->setComments($item['comments'])
                ->setCreationDate($item['creation_date'])
                ->setTaxonOrder($item['TaxonOrder'])
                ->setTaxonClass($item['TaxonClass'])
                ->setPhony($item['phony'])
                ->setSoundId($item['sound_id'])
                ->setSoundType($item['sound_type']);
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
            LEFT JOIN sound_type st ON st.sound_type_id = t.animal_sound_type
            WHERE t.user_id = :user_id1 OR c.user_id = :user_id2
            ORDER BY t.tag_id"
        );
        $result = $this->database->executeSelect([":user_id1" => Auth::getUserLoggedID(), ":user_id2" => Auth::getUserLoggedID()]);

        foreach ($result as $item) {
            $data[] = (new Tag())
                ->setId($item['tag_id'])
                ->setSpeciesName($item['speciesName'])
                ->setRecording($item['recording_id'])
                ->setRecordingName($item['recordingName'])
                ->setUserName($item['userName'])
                ->setMinTime($item['min_time'])
                ->setMaxTime($item['max_time'])
                ->setMinFrequency($item['min_freq'])
                ->setMaxFrequency($item['max_freq'])
                ->setUncertain($item['uncertain'])
                ->setCallDistance($item['sound_distance_m'])
                ->setDistanceNotEstimable(isset($item['distance_not_estimable']) ? $item['distance_not_estimable'] : 0)
                ->setNumberIndividuals($item['individuals'])
                ->setType($item['typeName'])
                ->setReferenceCall($item['reference_call'])
                ->setComments($item['comments'])
                ->setCreationDate($item['creation_date']);
        }

        return $data;
    }
}