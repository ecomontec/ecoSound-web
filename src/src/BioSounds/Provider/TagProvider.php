<?php


namespace BioSounds\Provider;


use BioSounds\Entity\AbstractProvider;
use BioSounds\Entity\Species;
use BioSounds\Entity\Tag;
use BioSounds\Entity\User;
use BioSounds\Utils\Auth;
use Cassandra\Varint;

class TagProvider extends AbstractProvider
{
    const TABLE_NAME = "tag";


    public function getAll(): array
    {
        $sql = 'SELECT COUNT(*) AS count FROM tag';
        $this->database->prepareQuery($sql);
        return $this->database->executeSelect();
    }

    /**
     * @param int $tagId
     * @return Tag
     * @throws \Exception
     */
    public function get(int $tagId): Tag
    {
        $query = 'SELECT tag.*,sound.soundscape_component,sound.sound_type, species.taxon_order, species.class, user.name ';
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
    public function getList(string $recordingId, int $userId = null): array
    {
        $result = [];

        $query = 'SELECT tag.tag_id, tag.recording_id, tag.min_time, tag.max_time, tag.min_freq, tag.max_freq, tag.user_id, tag.uncertain,sound.soundscape_component,sound.sound_type, ';
        $query .= 'species.binomial as species_name, tag.sound_distance_m, tag.distance_not_estimable, ';
        $query .= '(SELECT COUNT(*) FROM tag_review WHERE tag_id = tag.tag_id) AS review_number, ';
        $query .= '(( tag.max_time - tag.min_time ) + (tag.max_freq - tag.min_time )) AS time ';
        $query .= 'FROM tag LEFT JOIN species ON tag.species_id = species.species_id ';
        $query .= 'LEFT JOIN sound ON tag.sound_id = sound.sound_id ';
        $query .= 'LEFT JOIN recording r ON r.recording_id = tag.recording_id ';
        $query .= 'LEFT JOIN collection c ON c.collection_id = r.col_id ';
        $query .= "WHERE tag.recording_id IN ($recordingId)";

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

    public function getListByTask(): array
    {
        $result = [];
        $query = 'SELECT tag.tag_id, tag.recording_id, tag.min_time, tag.max_time, tag.min_freq, tag.max_freq, tag.user_id, tag.uncertain,sound.soundscape_component,sound.sound_type, ';
        $query .= 'species.binomial as species_name, tag.sound_distance_m, tag.distance_not_estimable, ';
        $query .= '(SELECT COUNT(*) FROM tag_review WHERE tag_id = tag.tag_id) AS review_number, ';
        $query .= '(( tag.max_time - tag.min_time ) + (tag.max_freq - tag.min_time )) AS time ';
        $query .= 'FROM tag LEFT JOIN species ON tag.species_id = species.species_id ';
        $query .= 'LEFT JOIN sound ON tag.sound_id = sound.sound_id ';
        $query .= 'LEFT JOIN recording r ON r.recording_id = tag.recording_id ';
        $query .= 'LEFT JOIN collection c ON c.collection_id = r.col_id ';
        $query .= "LEFT JOIN task ON task.type = 'tag' AND task.tag_id = tag.tag_id ";
        $query .= " WHERE task.status='assigned' AND task.assignee_id = " . Auth::getUserLoggedID();
        $query .= ' ORDER BY task.task_id';
        $this->database->prepareQuery($query);
        foreach ($this->database->executeSelect() as $tag) {
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

        $query = 'SELECT tag.tag_id, tag.recording_id, tag.min_time, tag.max_time, tag.min_freq, tag.max_freq, tag.user_id, tag.uncertain,sound.soundscape_component,sound.sound_type, ';
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

    public function insertArr($data): int
    {
        if (empty($data)) {
            return false;
        }
        $keys = [];
        $arr = [];

        $sql = 'INSERT INTO tag (';
        foreach ($data[0] as $key => $value) {
            $keys[] = $key;
        }
        $sql .= implode(',', $keys) . ') VALUES ';
        foreach ($data as $value) {
            $values = [];
            foreach ($value as $v) {
                $values[] = '"' . $v . '"';
            }
            $arr[] = '(' . implode(',', $values) . ')';
        }
        $sql .= implode(',', $arr);
        $this->database->prepareQuery($sql);
        return $this->database->executeInsert();
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
    public function delete(string $tagId)
    {
        $this->database->prepareQuery("DELETE FROM tag WHERE tag_id IN ($tagId)");
        return $this->database->executeDelete();
    }

    public function getTagPagesByCollection(int $colId): array
    {
        $sql = "SELECT t.*,sound.soundscape_component,sound.sound_type,s.binomial AS speciesName,r.`name` AS recordingName,u.`name` AS userName,st.`name` AS typeName,s.taxon_order AS TaxonOrder,s.class AS TaxonClass FROM tag t 
            INNER JOIN recording r ON r.recording_id = t.recording_id
            LEFT JOIN species s ON s.species_id = t.species_id
            LEFT JOIN collection c ON c.collection_id = r.col_id
            LEFT JOIN user u ON u.user_id = t.user_id
            LEFT JOIN sound ON sound.sound_id = t.sound_id
            LEFT JOIN sound_type st ON st.sound_type_id = t.animal_sound_type WHERE c.collection_id = :colId ";
        if (!Auth::isManage()) {
            $sql .= " AND t.user_id = " . Auth::getUserID();
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
                ->setSoundscapeComponent($item['soundscape_component'])
                ->setSoundId($item['sound_id'])
                ->setSoundType($item['sound_type'])
                ->setCreatorType(isset($item['creator_type']) ? $item['creator_type'] : null)
                ->setConfidence(isset($item['confidence']) ? $item['confidence'] : null);
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
                ->setCreationDate($item['creation_date'])
                ->setCreatorType(isset($item['creator_type']) ? $item['creator_type'] : null)
                ->setConfidence(isset($item['confidence']) ? $item['confidence'] : null);
        }
        return $data;
    }

    public function getTag(string $collectionId, string $recordingId): array
    {
        $sql = "SELECT t.*,sound.soundscape_component,sound.sound_type,s.binomial AS speciesName,r.`name` AS recordingName,u.`name` AS userName,st.`name` AS typeName,s.taxon_order AS TaxonOrder,s.class AS TaxonClass FROM tag t 
            INNER JOIN recording r ON r.recording_id = t.recording_id
            LEFT JOIN species s ON s.species_id = t.species_id
            LEFT JOIN collection c ON c.collection_id = r.col_id
            LEFT JOIN user u ON u.user_id = t.user_id
            LEFT JOIN sound ON sound.sound_id = t.sound_id
            LEFT JOIN sound_type st ON st.sound_type_id = t.animal_sound_type WHERE c.collection_id = $collectionId ";
        if (!(new User())->isManage($_SESSION['user_id'], $collectionId)) {
            $sql .= " AND t.user_id = " . Auth::getUserID();
        }
        if ($recordingId) {
            $sql .= " AND r.recording_id = $recordingId";
        }
        $sql .= ' ORDER BY t.tag_id';
        $this->database->prepareQuery($sql);
        return $this->database->executeSelect();
    }

    public function getExportTag(string $collectionId, string $recordingId): array
    {
        $sql = "SELECT t.*,sound.soundscape_component,sound.sound_type,s.binomial AS speciesName,r.`name` AS recordingName,u.`name` AS userName,st.`name` AS typeName,s.taxon_order AS TaxonOrder,s.class AS TaxonClass FROM tag t 
            INNER JOIN recording r ON r.recording_id = t.recording_id
            LEFT JOIN species s ON s.species_id = t.species_id
            LEFT JOIN collection c ON c.collection_id = r.col_id
            LEFT JOIN user u ON u.user_id = t.user_id
            LEFT JOIN sound ON sound.sound_id = t.sound_id
            LEFT JOIN sound_type st ON st.sound_type_id = t.animal_sound_type WHERE c.collection_id = $collectionId ";
        if (!(new User())->isManage($_SESSION['user_id'], $collectionId)) {
            $sql .= " AND (t.user_id = " . Auth::getUserID() . ' OR c.public_tags = 1)';
        }
        if ($recordingId) {
            $sql .= " AND r.recording_id = $recordingId";
        }
        $sql .= ' ORDER BY t.tag_id';
        $this->database->prepareQuery($sql);
        return $this->database->executeSelect();
    }

    public function getFilterCount(string $collectionId, string $recordingId, string $search): int
    {
        $sql = "SELECT t.*,sound.soundscape_component,sound.sound_type,s.binomial AS speciesName,r.`name` AS recordingName,u.`name` AS userName,st.`name` AS typeName,s.taxon_order AS TaxonOrder,s.class AS TaxonClass FROM tag t 
            INNER JOIN recording r ON r.recording_id = t.recording_id
            LEFT JOIN species s ON s.species_id = t.species_id
            LEFT JOIN collection c ON c.collection_id = r.col_id
            LEFT JOIN user u ON u.user_id = t.user_id
            LEFT JOIN sound ON sound.sound_id = t.sound_id
            LEFT JOIN sound_type st ON st.sound_type_id = t.animal_sound_type 
            WHERE c.collection_id = $collectionId ";
        if (!(new User())->isManage($_SESSION['user_id'], $collectionId)) {
            $sql .= " AND t.user_id = " . Auth::getUserID();
        }
        if ($recordingId) {
            $sql .= " AND r.recording_id = $recordingId";
        }
        if ($search) {
            $sql .= " AND CONCAT(IFNULL(t.tag_id,''), IFNULL(sound.soundscape_component,''), IFNULL(sound.sound_type,''), IFNULL(r.name,''), IFNULL(u.name,''), IFNULL(t.creator_type,''), IFNULL(t.confidence,''), IFNULL(t.min_time,''), IFNULL(t.max_time,''), IFNULL(t.min_freq,''), IFNULL(t.max_freq,''), IFNULL(s.binomial,''), IFNULL(t.sound_distance_m,''), IFNULL(t.individuals,''), IFNULL(st.name,''), IFNULL(t.comments,''), IFNULL(t.creation_date,'')) LIKE '%$search%' ";
        }
        $sql .= " GROUP BY t.tag_id";
        $this->database->prepareQuery($sql);
        $count = count($this->database->executeSelect());
        return $count;
    }

    public function getListByPage(string $collectionId, string $recordingId, string $start = '0', string $length = '8', string $search = null, string $column = '0', string $dir = 'asc'): array
    {
        $arr = [];
        $sql = "SELECT t.*,sound.soundscape_component,sound.sound_type,s.binomial AS speciesName,r.`name` AS recordingName,u.`name` AS userName,st.`name` AS typeName,s.taxon_order AS TaxonOrder,s.class AS TaxonClass FROM tag t 
            INNER JOIN recording r ON r.recording_id = t.recording_id
            LEFT JOIN species s ON s.species_id = t.species_id
            LEFT JOIN collection c ON c.collection_id = r.col_id
            LEFT JOIN user u ON u.user_id = t.user_id
            LEFT JOIN sound ON sound.sound_id = t.sound_id
            LEFT JOIN sound_type st ON st.sound_type_id = t.animal_sound_type 
            WHERE c.collection_id = $collectionId ";
        if (!(new User())->isManage($_SESSION['user_id'], $collectionId)) {
            $sql .= " AND t.user_id = " . Auth::getUserID();
        }
        if ($recordingId) {
            $sql .= " AND r.recording_id = $recordingId";
        }
        if ($search) {
            $sql .= " AND CONCAT(IFNULL(t.tag_id,''), IFNULL(sound.soundscape_component,''), IFNULL(sound.sound_type,''), IFNULL(r.name,''), IFNULL(u.name,''), IFNULL(t.creator_type,''), IFNULL(t.confidence,''), IFNULL(t.min_time,''), IFNULL(t.max_time,''), IFNULL(t.min_freq,''), IFNULL(t.max_freq,''), IFNULL(s.binomial,''), IFNULL(t.sound_distance_m,''), IFNULL(t.individuals,''), IFNULL(st.name,''), IFNULL(t.comments,''), IFNULL(t.creation_date,'')) LIKE '%$search%' ";
        }
        $sql .= " GROUP BY t.tag_id";
        $a = ['', 't.tag_id', 'sound.soundscape_component', 'sound.sound_type', 'r.name', 'u.name', 't.creator_type', 't.confidence', 't.min_time', 't.max_time', 't.min_freq', 't.max_freq', 's.binomial', 't.uncertain', 't.sound_distance_m', 't.distance_not_estimable', 't.individuals', 'st.name', 'reference_call', 't.comments', 't.creation_date'];
        $sql .= " ORDER BY $a[$column] $dir";
        // Only add LIMIT if length is not -1 (DataTables "All" option sends -1)
        if ($length != '-1') {
            $sql .= " LIMIT $length OFFSET $start";
        }
        $this->database->prepareQuery($sql);
        $result = $this->database->executeSelect();
        $soundscape_components = (new SoundProvider())->get();
        $sound_types = (new SoundProvider())->getAll();
        if (count($result)) {
            foreach ($result as $key => $value) {
                $str_soundscape_component = '';
                $str_sound_type = '';
                foreach ($soundscape_components as $soundscape_component) {
                    $str_soundscape_component .= "<option value='" . $soundscape_component->getSoundscapeComponent() . "' " . ($value['soundscape_component'] == $soundscape_component->getSoundscapeComponent() ? 'selected' : '') . ">" . $soundscape_component->getSoundscapeComponent() . "</option>";
                }
                foreach ($sound_types as $sound_type) {
                    if ($sound_type['soundscape_component'] == $value['soundscape_component']) {
                        $str_sound_type .= "<option value='$sound_type[sound_id]' " . ($value['sound_id'] == $sound_type['sound_id'] ? 'selected' : '') . ">$sound_type[sound_type]</option>";
                    }
                }
                $arr[$key][] = "<input type='checkbox' class='js-checkbox' data-id='$value[tag_id]' data-recording-id='$value[recording_id]' data-tmin='$value[min_time]' data-tmax='$value[max_time]' data-fmin='$value[min_freq]' data-fmax='$value[max_freq]' name='cb[$value[collection_id]]' id='cb[$value[collection_id]]'>";
                $arr[$key][] = "$value[tag_id]
                        <input type='hidden' name='tag_id' value='$value[tag_id]'>
                        <input class='js-species-id$value[tag_id]' data-type='edit' name='species_id' type='hidden' value='$value[species_id]'>
                        <input id='old_id$value[tag_id]' type='hidden' value='$value[species_id]'>
                        <input id='old_name$value[tag_id]' type='hidden' value='$value[speciesName]'>
                        <input id='taxon_order$value[tag_id]' type='hidden' value='$value[TaxonOrder]'>
                        <input id='taxon_class$value[tag_id]' type='hidden' value='$value[TaxonClass]'>";
                $arr[$key][] = "<select id='soundscape_component_$value[tag_id]' name='soundscape_component' class='form-control form-control-sm' style='width:180px;'>$str_soundscape_component</select>";
                $arr[$key][] = "<select id='sound_id$value[tag_id]' name='sound_id' class='form-control form-control-sm' style='width:180px;'>$str_sound_type</select>";
                $arr[$key][] = $value['recordingName'];
                $arr[$key][] = $value['userName'];
                $arr[$key][] = $value['creator_type'];
                $arr[$key][] = $value['confidence'];
                $arr[$key][] = "<input type='number' class='form-control form-control-sm' style='width:60px;' name='min_time' maxlength='100' value='$value[min_time]'>";
                $arr[$key][] = "<input type='number' class='form-control form-control-sm' style='width:60px;' name='max_time' maxlength='100' value='$value[max_time]'>";
                $arr[$key][] = "<input type='number' class='form-control form-control-sm' style='width:75px;' name='min_freq' maxlength='100' value='$value[min_freq]'>";
                $arr[$key][] = "<input type='number' class='form-control form-control-sm' style='width:75px;' name='max_freq' maxlength='100' value='$value[max_freq]'>";
                $arr[$key][] = "<input class='soundscape_component_$value[tag_id] form-control form-control-sm js-species-autocomplete' type='text' id='speciesName_$value[tag_id]' style='width:150px;' data-type='edit' size='30' value='$value[speciesName]'" . ($value['soundscape_component'] != 'biophony' ? 'hidden' : '') . "><div class='invalid-feedback'>Please select a species from the list.</div>";
                $arr[$key][] = "<input class='soundscape_component_$value[tag_id]' name='uncertain' type='checkbox' " . ($value['uncertain'] ? 'checked' : '') . ($value['soundscape_component'] != 'biophony' ? 'hidden' : '') . ">";
                $arr[$key][] = "<input class='soundscape_component_$value[tag_id] form-control form-control-sm' name='sound_distance_m' type='number' id='sound_distance_m$value[tag_id]' style='width:75px;' maxlength='100' value='$value[sound_distance_m]' " . ($value['distance_not_estimable'] ? 'readonly' : '') . ($value['soundscape_component'] != 'biophony' ? 'hidden' : '') . ">";
                $arr[$key][] = "<input class='soundscape_component_$value[tag_id]' name='distance_not_estimable' type='checkbox' id='distance_not_estimable_$value[tag_id]' " . ($value['distance_not_estimable'] ? 'checked' : '') . ($value['soundscape_component'] != 'biophony' ? 'hidden' : '') . ">";
                $arr[$key][] = "<input class='soundscape_component_$value[tag_id] form-control form-control-sm' name='individuals' type='number' min='0' max='1000' value='$value[individuals]'" . ($value['soundscape_component'] != 'biophony' ? 'hidden' : '') . ">";
                $arr[$key][] = "<select class='soundscape_component_$value[tag_id] form-control form-control-sm' name='animal_sound_type' id='animal_sound_type$value[tag_id]' style='width:180px;' " . ($value['soundscape_component'] != 'biophony' ? 'hidden' : '') . "><option value='" . ($value['type_id'] ? $value['type_id'] : 0) . "' selected>$value[typeName]</option></select>";
                $arr[$key][] = "<input name='reference_call' type='checkbox' " . ($value['reference_call'] ? 'checked' : '') . ">";
                $arr[$key][] = "<textarea name='comments' class='form-control form-control-sm' maxlength='200' rows='1' style='resize:none;width:180px;'>$value[comments]</textarea>";
                $arr[$key][] = $value['creation_date'];
            }
        }
        return $arr;
    }

    public function getTagCount($recordings)
    {
        $this->database->prepareQuery("SELECT COUNT(tag_id) AS count FROM tag WHERE recording_id IN ($recordings)");
        if (empty($result = $this->database->executeSelect())) {
            return null;
        }
        return $result[0]['count'];
    }

    public function getViewTag(string $collectionId, string $recordingId, string $minTime, string $maxTime, string $minFrequency, string $maxFrequency): array
    {
        $sql = "SELECT t.*,sound.soundscape_component,sound.sound_type,s.binomial AS speciesName,r.`name` AS recordingName,u.`name` AS userName,st.`name` AS typeName,s.taxon_order AS TaxonOrder,s.class AS TaxonClass FROM tag t 
            INNER JOIN recording r ON r.recording_id = t.recording_id
            LEFT JOIN species s ON s.species_id = t.species_id
            LEFT JOIN collection c ON c.collection_id = r.col_id
            LEFT JOIN user u ON u.user_id = t.user_id
            LEFT JOIN sound ON sound.sound_id = t.sound_id
            LEFT JOIN sound_type st ON st.sound_type_id = t.animal_sound_type WHERE c.collection_id = :collectionId 
            AND min_time <= :maxTime 
            AND max_time >= :minTime 
            AND min_freq <= :maxFrequency 
            AND max_freq >= :minFrequency ";
        if (!(new User())->isManage($_SESSION['user_id'], $collectionId)) {
            $sql .= " AND (t.user_id = " . Auth::getUserID() . ' OR c.public_tags = 1) ';
        }
        if ($recordingId) {
            $sql .= " AND r.recording_id = :recordingId";
        }
        $sql .= ' ORDER BY t.tag_id';
        $this->database->prepareQuery($sql);
        return $this->database->executeSelect([
            ':collectionId' => $collectionId,
            ':recordingId' => $recordingId,
            ':minTime' => $minTime,
            ':maxTime' => $maxTime,
            ':minFrequency' => $minFrequency,
            ':maxFrequency' => $maxFrequency
        ]);
    }

    public function getViewFilterCount(string $collectionId, string $recordingId, string $minTime, string $maxTime, string $minFrequency, string $maxFrequency, string $search): int
    {
        $sql = "SELECT t.*,sound.soundscape_component,sound.sound_type,s.binomial AS speciesName,r.`name` AS recordingName,u.`name` AS userName,st.`name` AS typeName,s.taxon_order AS TaxonOrder,s.class AS TaxonClass FROM tag t 
            INNER JOIN recording r ON r.recording_id = t.recording_id
            LEFT JOIN species s ON s.species_id = t.species_id
            LEFT JOIN collection c ON c.collection_id = r.col_id
            LEFT JOIN user u ON u.user_id = t.user_id
            LEFT JOIN sound ON sound.sound_id = t.sound_id
            LEFT JOIN sound_type st ON st.sound_type_id = t.animal_sound_type 
            WHERE c.collection_id = :collectionId 
            AND min_time <= :maxTime 
            AND max_time >= :minTime 
            AND min_freq <= :maxFrequency 
            AND max_freq >= :minFrequency ";
        if (!(new User())->isManage($_SESSION['user_id'], $collectionId)) {
            $sql .= " AND (t.user_id = " . Auth::getUserID() . ' OR c.public_tags = 1) ';
        }
        if ($recordingId) {
            $sql .= " AND r.recording_id = :recordingId";
        }
        if ($search) {
            $sql .= " AND CONCAT(IFNULL(t.tag_id,''), IFNULL(sound.soundscape_component,''), IFNULL(sound.sound_type,''), IFNULL(r.name,''), IFNULL(u.name,''), IFNULL(t.creator_type,''), IFNULL(t.confidence,''), IFNULL(t.min_time,''), IFNULL(t.max_time,''), IFNULL(t.min_freq,''), IFNULL(t.max_freq,''), IFNULL(s.binomial,''), IFNULL(t.sound_distance_m,''), IFNULL(t.individuals,''), IFNULL(st.name,''), IFNULL(t.comments,''), IFNULL(t.creation_date,'')) LIKE '%$search%' ";
        }
        $sql .= " GROUP BY t.tag_id";
        $this->database->prepareQuery($sql);
        $count = count($this->database->executeSelect([
            ':collectionId' => $collectionId,
            ':recordingId' => $recordingId,
            ':minTime' => $minTime,
            ':maxTime' => $maxTime,
            ':minFrequency' => $minFrequency,
            ':maxFrequency' => $maxFrequency
        ]));
        return $count;
    }

    public function getViewListByPage(string $collectionId, string $recordingId, string $minTime, string $maxTime, string $minFrequency, string $maxFrequency, string $start = '0', string $length = '8', string $search = null, string $column = '0', string $dir = 'asc'): array
    {
        $arr = [];
        $sql = "SELECT t.*,sound.soundscape_component,sound.sound_type,s.binomial AS speciesName,r.`name` AS recordingName,u.`name` AS userName,st.`name` AS typeName,s.taxon_order AS TaxonOrder,s.class AS TaxonClass,u.user_id FROM tag t 
            INNER JOIN recording r ON r.recording_id = t.recording_id
            LEFT JOIN species s ON s.species_id = t.species_id
            LEFT JOIN collection c ON c.collection_id = r.col_id
            LEFT JOIN user u ON u.user_id = t.user_id
            LEFT JOIN sound ON sound.sound_id = t.sound_id
            LEFT JOIN sound_type st ON st.sound_type_id = t.animal_sound_type 
            WHERE c.collection_id = :collectionId 
            AND min_time <= :maxTime 
            AND max_time >= :minTime 
            AND min_freq <= :maxFrequency 
            AND max_freq >= :minFrequency ";
        if (!(new User())->isManage($_SESSION['user_id'], $collectionId)) {
            $sql .= " AND (t.user_id = " . Auth::getUserID() . ' OR c.public_tags = 1) ';
        }
        if ($recordingId) {
            $sql .= " AND r.recording_id = :recordingId";
        }
        if ($search) {
            $sql .= " AND CONCAT(IFNULL(t.tag_id,''), IFNULL(sound.soundscape_component,''), IFNULL(sound.sound_type,''), IFNULL(r.name,''), IFNULL(u.name,''), IFNULL(t.creator_type,''), IFNULL(t.confidence,''), IFNULL(t.min_time,''), IFNULL(t.max_time,''), IFNULL(t.min_freq,''), IFNULL(t.max_freq,''), IFNULL(s.binomial,''), IFNULL(t.sound_distance_m,''), IFNULL(t.individuals,''), IFNULL(st.name,''), IFNULL(t.comments,''), IFNULL(t.creation_date,'')) LIKE '%$search%' ";
        }
        $sql .= " GROUP BY t.tag_id";
        $a = ['', 't.tag_id', 'sound.soundscape_component', 'sound.sound_type', 'r.name', 'u.name', 't.creator_type', 't.confidence', 't.min_time', 't.max_time', 't.min_freq', 't.max_freq', 's.binomial', 't.uncertain', 't.sound_distance_m', 't.distance_not_estimable', 't.individuals', 'st.name', 'reference_call', 't.comments', 't.creation_date'];
        $sql .= " ORDER BY $a[$column] $dir LIMIT $length OFFSET $start";
        $this->database->prepareQuery($sql);
        $result = $this->database->executeSelect([
            ':collectionId' => $collectionId,
            ':recordingId' => $recordingId,
            ':minTime' => $minTime,
            ':maxTime' => $maxTime,
            ':minFrequency' => $minFrequency,
            ':maxFrequency' => $maxFrequency
        ]);
        $soundscape_components = (new SoundProvider())->get();
        $sound_types = (new SoundProvider())->getAll();
        if (count($result)) {
            foreach ($result as $key => $value) {
                if (!(new User())->isManage($_SESSION['user_id'], $collectionId) && $value['user_id'] != $_SESSION['user_id']) {
                    $arr[$key][] = "";
                    $arr[$key][] = "$value[tag_id]<input type='hidden' name='tag_id' value='$value[tag_id]'>";
                    $arr[$key][] = $value['soundscape_component'];
                    $arr[$key][] = $value['sound_type'];
                    $arr[$key][] = $value['recordingName'];
                    $arr[$key][] = $value['userName'];
                    $arr[$key][] = $value['creator_type'];
                    $arr[$key][] = $value['confidence'];
                    $arr[$key][] = $value['min_time'];
                    $arr[$key][] = $value['max_time'];
                    $arr[$key][] = $value['min_freq'];
                    $arr[$key][] = $value['max_freq'];
                    $arr[$key][] = $value['speciesName'];
                    $arr[$key][] = "<input " . ($value['soundscape_component'] != 'biophony' ? 'display:none' : '') . " type='checkbox' " . ($value['uncertain'] ? 'checked' : '') . " disabled>";
                    $arr[$key][] = $value['sound_distance_m'];
                    $arr[$key][] = "<input " . ($value['soundscape_component'] != 'biophony' ? 'display:none' : '') . " type='checkbox' " . ($value['distance_not_estimable'] ? 'checked' : '') . " disabled>";
                    $arr[$key][] = $value['individuals'];
                    $arr[$key][] = $value['typeName'];
                    $arr[$key][] = "<input name='reference_call' type='checkbox' " . ($value['reference_call'] ? 'checked' : '') . " disabled>";
                    $arr[$key][] = $value['comments'];
                    $arr[$key][] = $value['creation_date'];
                } else {
                    $str_soundscape_component = '';
                    $str_sound_type = '';
                    foreach ($soundscape_components as $soundscape_component) {
                        $str_soundscape_component .= "<option value='" . $soundscape_component->getSoundscapeComponent() . "' " . ($value['soundscape_component'] == $soundscape_component->getSoundscapeComponent() ? 'selected' : '') . ">" . $soundscape_component->getSoundscapeComponent() . "</option>";
                    }
                    foreach ($sound_types as $sound_type) {
                        if ($sound_type['soundscape_component'] == $value['soundscape_component']) {
                            $str_sound_type .= "<option value='$sound_type[sound_id]' " . ($value['sound_id'] == $sound_type['sound_id'] ? 'selected' : '') . ">$sound_type[sound_type]</option>";
                        }
                    }
                    $arr[$key][] = "<input type='checkbox' class='js-checkbox' data-id='$value[tag_id]' data-recording-id='$value[recording_id]' data-tmin='$value[min_time]' data-tmax='$value[max_time]' data-fmin='$value[min_freq]' data-fmax='$value[max_freq]' name='cb[$value[collection_id]]' id='cb[$value[collection_id]]'>";
                    $arr[$key][] = "$value[tag_id]
                        <input type='hidden' name='tag_id' value='$value[tag_id]'>
                        <input class='js-species-id$value[tag_id]' data-type='edit' name='species_id' type='hidden' value='$value[species_id]'>
                        <input id='old_id$value[tag_id]' type='hidden' value='$value[species_id]'>
                        <input id='old_name$value[tag_id]' type='hidden' value='$value[speciesName]'>
                        <input id='taxon_order$value[tag_id]' type='hidden' value='$value[TaxonOrder]'>
                        <input id='taxon_class$value[tag_id]' type='hidden' value='$value[TaxonClass]'>";
                    $arr[$key][] = "<select id='soundscape_component_$value[tag_id]' name='soundscape_component' class='form-control form-control-sm' style='width:180px;'>$str_soundscape_component</select>";
                    $arr[$key][] = "<select id='sound_id$value[tag_id]' name='sound_id' class='form-control form-control-sm' style='width:180px;'>$str_sound_type</select>";
                    $arr[$key][] = $value['recordingName'];
                    $arr[$key][] = $value['userName'];
                    $arr[$key][] = $value['creator_type'];
                    $arr[$key][] = $value['confidence'];
                    $arr[$key][] = "<input type='number' class='form-control form-control-sm' style='width:60px;' name='min_time' maxlength='100' value='$value[min_time]'>";
                    $arr[$key][] = "<input type='number' class='form-control form-control-sm' style='width:60px;' name='max_time' maxlength='100' value='$value[max_time]'>";
                    $arr[$key][] = "<input type='number' class='form-control form-control-sm' style='width:75px;' name='min_freq' maxlength='100' value='$value[min_freq]'>";
                    $arr[$key][] = "<input type='number' class='form-control form-control-sm' style='width:75px;' name='max_freq' maxlength='100' value='$value[max_freq]'>";
                    $arr[$key][] = "<input class='soundscape_component_$value[tag_id] form-control form-control-sm js-species-autocomplete' type='text' id='speciesName_$value[tag_id]' style='width:150px;' data-type='edit' size='30' value='$value[speciesName]'" . ($value['soundscape_component'] != 'biophony' ? 'hidden' : '') . "><div class='invalid-feedback'>Please select a species from the list.</div>";
                    $arr[$key][] = "<input class='soundscape_component_$value[tag_id]' name='uncertain' type='checkbox' " . ($value['uncertain'] ? 'checked' : '') . ($value['soundscape_component'] != 'biophony' ? 'hidden' : '') . ">";
                    $arr[$key][] = "<input class='soundscape_component_$value[tag_id] form-control form-control-sm' name='sound_distance_m' type='number' id='sound_distance_m$value[tag_id]' style='width:75px;' maxlength='100' value='$value[sound_distance_m]' " . ($value['distance_not_estimable'] ? 'readonly' : '') . ($value['soundscape_component'] != 'biophony' ? 'hidden' : '') . ">";
                    $arr[$key][] = "<input class='soundscape_component_$value[tag_id]' name='distance_not_estimable' type='checkbox' id='distance_not_estimable_$value[tag_id]' " . ($value['distance_not_estimable'] ? 'checked' : '') . ($value['soundscape_component'] != 'biophony' ? 'hidden' : '') . ">";
                    $arr[$key][] = "<input class='soundscape_component_$value[tag_id] form-control form-control-sm' name='individuals' type='number' min='0' max='1000' value='$value[individuals]'" . ($value['soundscape_component'] != 'biophony' ? 'hidden' : '') . ">";
                    $arr[$key][] = "<select class='soundscape_component_$value[tag_id] form-control form-control-sm' name='animal_sound_type' id='animal_sound_type$value[tag_id]' style='width:180px;' " . ($value['soundscape_component'] != 'biophony' ? 'hidden' : '') . "><option value='" . ($value['type_id'] ? $value['type_id'] : 0) . "' selected>$value[typeName]</option></select>";
                    $arr[$key][] = "<input name='reference_call' type='checkbox' " . ($value['reference_call'] ? 'checked' : '') . ">";
                    $arr[$key][] = "<textarea name='comments' class='form-control form-control-sm' maxlength='200' rows='1' style='resize:none;width:180px;'>$value[comments]</textarea>";
                    $arr[$key][] = $value['creation_date'];
                }
            }
        }
        return $arr;
    }

    public function getRecrdingViewListByPage(string $collectionId, string $recordingId, string $minTime, string $maxTime, string $minFrequency, string $maxFrequency, string $start = '0', string $length = '8', string $search = null, string $column = '0', string $dir = 'asc'): array
    {
        $arr = [];
        $sql = "SELECT t.*,sound.soundscape_component,sound.sound_type,s.binomial AS speciesName,r.`name` AS recordingName,u.`name` AS userName,st.`name` AS typeName,s.taxon_order AS TaxonOrder,s.family AS TaxonFamily,s.genus AS TaxonGenus,u.user_id FROM tag t 
            INNER JOIN recording r ON r.recording_id = t.recording_id
            LEFT JOIN species s ON s.species_id = t.species_id
            LEFT JOIN collection c ON c.collection_id = r.col_id
            LEFT JOIN user u ON u.user_id = t.user_id
            LEFT JOIN sound ON sound.sound_id = t.sound_id
            LEFT JOIN sound_type st ON st.sound_type_id = t.animal_sound_type 
            WHERE c.collection_id = :collectionId 
            AND min_time <= :maxTime 
            AND max_time >= :minTime 
            AND min_freq <= :maxFrequency 
            AND max_freq >= :minFrequency ";
        if (!(new User())->isManage($_SESSION['user_id'], $collectionId)) {
            $sql .= " AND (t.user_id = " . Auth::getUserID() . ' OR c.public_tags = 1) ';
        }
        if ($recordingId) {
            $sql .= " AND r.recording_id = :recordingId";
        }
        if ($search) {
            $sql .= " AND CONCAT(IFNULL(t.tag_id,''), IFNULL(sound.soundscape_component,''), IFNULL(sound.sound_type,''), IFNULL(r.name,''), IFNULL(u.name,''), IFNULL(t.creator_type,''), IFNULL(t.confidence,''), IFNULL(t.min_time,''), IFNULL(t.max_time,''), IFNULL(t.min_freq,''), IFNULL(t.max_freq,''), IFNULL(s.binomial,''), IFNULL(t.sound_distance_m,''), IFNULL(t.individuals,''), IFNULL(st.name,''), IFNULL(t.comments,''), IFNULL(t.creation_date,'')) LIKE '%$search%' ";
        }
        $sql .= " GROUP BY t.tag_id";
        $a = ['', 't.tag_id', 'sound.soundscape_component', 'sound.sound_type', 'r.name', 'u.name', 't.creator_type', 't.confidence', 't.min_time', 't.max_time', 't.min_freq', 't.max_freq', 's.binomial', 't.uncertain', 't.sound_distance_m', 't.distance_not_estimable', 't.individuals', 'st.name', 'reference_call', 't.comments', 't.creation_date'];
        $sql .= " ORDER BY $a[$column] $dir LIMIT $length OFFSET $start";
        $this->database->prepareQuery($sql);
        $result = $this->database->executeSelect([
            ':collectionId' => $collectionId,
            ':recordingId' => $recordingId,
            ':minTime' => $minTime,
            ':maxTime' => $maxTime,
            ':minFrequency' => $minFrequency,
            ':maxFrequency' => $maxFrequency
        ]);
        $soundscape_components = (new SoundProvider())->get();
        $sound_types = (new SoundProvider())->getAll();
        if (count($result)) {
            foreach ($result as $key => $value) {
                if (!(new User())->isManage($_SESSION['user_id'], $collectionId) && $value['user_id'] != $_SESSION['user_id']) {
                    $arr[$key][] = "";
                    $arr[$key][] = "$value[tag_id]<input type='hidden' name='tag_id' value='$value[tag_id]'>";
                    $arr[$key][] = $value['soundscape_component'];
                    $arr[$key][] = $value['sound_type'];
                    $arr[$key][] = $value['userName'];
                    $arr[$key][] = $value['creator_type'];
                    $arr[$key][] = $value['confidence'];
                    $arr[$key][] = $value['min_time'];
                    $arr[$key][] = $value['max_time'];
                    $arr[$key][] = $value['min_freq'];
                    $arr[$key][] = $value['max_freq'];
                    $arr[$key][] = $value['speciesName'];
                    $arr[$key][] = "<div id='order$value[tag_id]'>$value[TaxonOrder]</div>";
                    $arr[$key][] = "<div id='family$value[tag_id]'>$value[TaxonFamily]</div>";
                    $arr[$key][] = "<div id='genus$value[tag_id]'>$value[TaxonGenus]</div>";
                    $arr[$key][] = "<input " . ($value['soundscape_component'] != 'biophony' ? 'display:none' : '') . " type='checkbox' " . ($value['uncertain'] ? 'checked' : '') . " disabled>";
                    $arr[$key][] = $value['sound_distance_m'];
                    $arr[$key][] = "<input " . ($value['soundscape_component'] != 'biophony' ? 'display:none' : '') . " type='checkbox' " . ($value['distance_not_estimable'] ? 'checked' : '') . " disabled>";
                    $arr[$key][] = $value['individuals'];
                    $arr[$key][] = $value['typeName'];
                    $arr[$key][] = "<input name='reference_call' type='checkbox' " . ($value['reference_call'] ? 'checked' : '') . " disabled>";
                    $arr[$key][] = $value['comments'];
                    $arr[$key][] = $value['creation_date'];
                } else {
                    $str_soundscape_component = '';
                    $str_sound_type = '';
                    foreach ($soundscape_components as $soundscape_component) {
                        $str_soundscape_component .= "<option value='" . $soundscape_component->getSoundscapeComponent() . "' " . ($value['soundscape_component'] == $soundscape_component->getSoundscapeComponent() ? 'selected' : '') . ">" . $soundscape_component->getSoundscapeComponent() . "</option>";
                    }
                    foreach ($sound_types as $sound_type) {
                        if ($sound_type['soundscape_component'] == $value['soundscape_component']) {
                            $str_sound_type .= "<option value='$sound_type[sound_id]' " . ($value['sound_id'] == $sound_type['sound_id'] ? 'selected' : '') . ">$sound_type[sound_type]</option>";
                        }
                    }
                    $arr[$key][] = "<input type='checkbox' class='js-checkbox' data-id='$value[tag_id]' data-recording-id='$value[recording_id]' data-tmin='$value[min_time]' data-tmax='$value[max_time]' data-fmin='$value[min_freq]' data-fmax='$value[max_freq]' name='cb[$value[collection_id]]' id='cb[$value[collection_id]]'>";
                    $arr[$key][] = "$value[tag_id]
                        <input type='hidden' name='tag_id' value='$value[tag_id]'>
                        <input class='js-species-id$value[tag_id]' data-type='edit' name='species_id' type='hidden' value='$value[species_id]'>
                        <input id='old_id$value[tag_id]' type='hidden' value='$value[species_id]'>
                        <input id='old_name$value[tag_id]' type='hidden' value='$value[speciesName]'>
                        <input id='taxon_order$value[tag_id]' type='hidden' value='$value[TaxonOrder]'>
                        <input id='taxon_class$value[tag_id]' type='hidden' value='$value[TaxonClass]'>";
                    $arr[$key][] = "<select id='soundscape_component_$value[tag_id]' name='soundscape_component' class='form-control form-control-sm' style='width:180px;'>$str_soundscape_component</select>";
                    $arr[$key][] = "<select id='sound_id$value[tag_id]' name='sound_id' class='form-control form-control-sm' style='width:180px;'>$str_sound_type</select>";
                    $arr[$key][] = $value['userName'];
                    $arr[$key][] = $value['creator_type'];
                    $arr[$key][] = $value['confidence'];
                    $arr[$key][] = "<input type='number' class='form-control form-control-sm' style='width:60px;' name='min_time' maxlength='100' value='$value[min_time]'>";
                    $arr[$key][] = "<input type='number' class='form-control form-control-sm' style='width:60px;' name='max_time' maxlength='100' value='$value[max_time]'>";
                    $arr[$key][] = "<input type='number' class='form-control form-control-sm' style='width:75px;' name='min_freq' maxlength='100' value='$value[min_freq]'>";
                    $arr[$key][] = "<input type='number' class='form-control form-control-sm' style='width:75px;' name='max_freq' maxlength='100' value='$value[max_freq]'>";
                    $arr[$key][] = "<input class='soundscape_component_$value[tag_id] form-control form-control-sm js-species-autocomplete' type='text' id='speciesName_$value[tag_id]' style='width:150px;' data-type='edit' size='30' value='$value[speciesName]'" . ($value['soundscape_component'] != 'biophony' ? 'hidden' : '') . "><div class='invalid-feedback'>Please select a species from the list.</div>";
                    $arr[$key][] = "<div id='order$value[tag_id]'>$value[TaxonOrder]</div>";
                    $arr[$key][] = "<div id='family$value[tag_id]'>$value[TaxonFamily]</div>";
                    $arr[$key][] = "<div id='genus$value[tag_id]'>$value[TaxonGenus]</div>";
                    $arr[$key][] = "<input class='soundscape_component_$value[tag_id]' name='uncertain' type='checkbox' " . ($value['uncertain'] ? 'checked' : '') . ($value['soundscape_component'] != 'biophony' ? 'hidden' : '') . ">";
                    $arr[$key][] = "<input class='soundscape_component_$value[tag_id] form-control form-control-sm' name='sound_distance_m' type='number' id='sound_distance_m$value[tag_id]' style='width:75px;' maxlength='100' value='$value[sound_distance_m]' " . ($value['distance_not_estimable'] ? 'readonly' : '') . ($value['soundscape_component'] != 'biophony' ? 'hidden' : '') . ">";
                    $arr[$key][] = "<input class='soundscape_component_$value[tag_id]' name='distance_not_estimable' type='checkbox' id='distance_not_estimable_$value[tag_id]' " . ($value['distance_not_estimable'] ? 'checked' : '') . ($value['soundscape_component'] != 'biophony' ? 'hidden' : '') . ">";
                    $arr[$key][] = "<input class='soundscape_component_$value[tag_id] form-control form-control-sm' name='individuals' type='number' min='0' max='1000' value='$value[individuals]'" . ($value['soundscape_component'] != 'biophony' ? 'hidden' : '') . ">";
                    $arr[$key][] = "<select class='soundscape_component_$value[tag_id] form-control form-control-sm' name='animal_sound_type' id='animal_sound_type$value[tag_id]' style='width:180px;' " . ($value['soundscape_component'] != 'biophony' ? 'hidden' : '') . "><option value='" . ($value['type_id'] ? $value['type_id'] : 0) . "' selected>$value[typeName]</option></select>";
                    $arr[$key][] = "<input name='reference_call' type='checkbox' " . ($value['reference_call'] ? 'checked' : '') . ">";
                    $arr[$key][] = "<textarea name='comments' class='form-control form-control-sm' maxlength='200' rows='1' style='resize:none;width:180px;'>$value[comments]</textarea>";
                    $arr[$key][] = $value['creation_date'];
                }
            }
        }
        return $arr;
    }
}