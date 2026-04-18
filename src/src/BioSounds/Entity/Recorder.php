<?php

namespace BioSounds\Entity;

use BioSounds\Provider\BaseProvider;

class Recorder extends BaseProvider
{
    const TABLE_NAME = 'recorder';
    const ID = 'recorder_id';
    const MODEL = 'model';
    const VERSION = 'version';
    const BRAND = 'brand';
    const MICROPHONE = 'microphone';

    public function getAll()
    {
        $query = 'SELECT * FROM ' . self::TABLE_NAME . ' ORDER BY ' . self::MODEL . ' ASC';
        $this->database->prepareQuery($query);
        $result = $this->database->executeSelect();
        return $result;
    }

    public function getById(int $id)
    {
        $query = 'SELECT * FROM ' . self::TABLE_NAME . ' WHERE ' . self::ID . ' = :id';
        $this->database->prepareQuery($query);
        $result = $this->database->executeSelect([':id' => $id]);
        return !empty($result) ? $result[0] : null;
    }

    public function insert(array $data)
    {
        $fields = [];
        $values = [];
        $placeholders = [];

        foreach ($data as $key => $value) {
            $fields[] = $key;
            $placeholders[] = ':' . $key;
            $values[':' . $key] = $value;
        }

        $query = 'INSERT INTO ' . self::TABLE_NAME . ' (' . implode(', ', $fields) . ') VALUES (' . implode(', ', $placeholders) . ')';
        $this->database->prepareQuery($query);
        return $this->database->executeInsert($values);
    }

    public function update(array $data, int $id)
    {
        $setParts = [];
        $values = [];

        foreach ($data as $key => $value) {
            $setParts[] = $key . ' = :' . $key;
            $values[':' . $key] = $value;
        }

        $values[':id'] = $id;
        $query = 'UPDATE ' . self::TABLE_NAME . ' SET ' . implode(', ', $setParts) . ' WHERE ' . self::ID . ' = :id';
        $this->database->prepareQuery($query);
        return $this->database->executeUpdate($values);
    }

    public function delete(int $id)
    {
        $query = 'DELETE FROM ' . self::TABLE_NAME . ' WHERE ' . self::ID . ' = :id';
        $this->database->prepareQuery($query);
        return $this->database->executeUpdate([':id' => $id]);
    }

    public function getBasicList()
    {
        $this->database->prepareQuery(
            'SELECT ' . self::ID . ', ' . self::MODEL . ', ' . self::VERSION . ', ' . self::BRAND . ', ' . self::MICROPHONE . ' FROM ' . self::TABLE_NAME . ' ORDER BY ' . self::MODEL
        );
        return $this->database->executeSelect();
    }

    public function getColumns()
    {
        $sql = "SELECT COLUMN_NAME FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = '" . self::TABLE_NAME . "' ORDER BY ordinal_position";
        $this->database->prepareQuery($sql);
        return $this->database->executeSelect();
    }

    /**
     * Get paginated list of recorders for DataTables server-side processing
     */
    public function getListByPage(string $start = '0', string $length = '10', string $search = null, string $column = '1', string $dir = 'asc'): array
    {
        $arr = [];
        
        $sql = "SELECT * FROM " . self::TABLE_NAME . " WHERE 1=1";
        $params = [];
        
        if ($search) {
            $sql .= " AND (CONCAT(IFNULL(model,''), ' ', IFNULL(version,''), ' ', IFNULL(brand,''), ' ', IFNULL(microphone,'')) LIKE :search)";
            $params[':search'] = "%$search%";
        }
        
        // Column mapping for sorting
        $columns = ['', 'recorder_id', 'model', 'version', 'brand', 'microphone'];
        $orderColumn = isset($columns[$column]) && $columns[$column] ? $columns[$column] : 'model';
        
        $sql .= " ORDER BY $orderColumn $dir LIMIT $length OFFSET $start";
        
        $this->database->prepareQuery($sql);
        $result = $this->database->executeSelect($params);
        
        if (count($result)) {
            foreach ($result as $key => $value) {
                $arr[$key][] = "<input type='checkbox' class='js-checkbox' data-id='$value[recorder_id]' name='cb[]'>";
                $arr[$key][] = "$value[recorder_id]<input type='hidden' name='itemID' value='$value[recorder_id]'>";
                $arr[$key][] = "<input type='text' class='form-control form-control-sm' name='model' value='" . htmlspecialchars($value['model']) . "' maxlength='100' required>";
                $arr[$key][] = "<input type='text' class='form-control form-control-sm' name='version' value='" . htmlspecialchars($value['version']) . "' maxlength='100'>";
                $arr[$key][] = "<input type='text' class='form-control form-control-sm' name='brand' value='" . htmlspecialchars($value['brand']) . "' maxlength='100'>";
                $arr[$key][] = "<input type='text' class='form-control form-control-sm' name='microphone' value='" . htmlspecialchars($value['microphone']) . "' maxlength='300' placeholder='e.g. 1,2,3'>";
            }
        }
        
        return $arr;
    }
}
