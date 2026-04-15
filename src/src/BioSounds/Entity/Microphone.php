<?php

namespace BioSounds\Entity;

use BioSounds\Provider\BaseProvider;

class Microphone extends BaseProvider
{
    const TABLE_NAME = 'microphone';
    const ID = 'microphone_id';
    const NAME = 'name';
    const ELEMENT = 'microphone_element';
    const SENSITIVITY = 'sensitivity';
    const SNR = 'signal_to_noise_ratio';

    public function getAll()
    {
        $query = 'SELECT * FROM ' . self::TABLE_NAME . ' ORDER BY ' . self::NAME . ' ASC';
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
            'SELECT ' . self::ID . ', ' . self::NAME . ' FROM ' . self::TABLE_NAME . ' ORDER BY ' . self::NAME
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
     * Get paginated list of microphones for DataTables server-side processing
     */
    public function getListByPage(string $start = '0', string $length = '10', string $search = null, string $column = '1', string $dir = 'asc'): array
    {
        $arr = [];
        
        $sql = "SELECT * FROM " . self::TABLE_NAME . " WHERE 1=1";
        $params = [];
        
        if ($search) {
            $sql .= " AND (CONCAT(IFNULL(name,''), ' ', IFNULL(microphone_element,''), ' ', IFNULL(sensitivity,''), ' ', IFNULL(signal_to_noise_ratio,'')) LIKE :search)";
            $params[':search'] = "%$search%";
        }
        
        // Column mapping for sorting
        $columns = ['', 'microphone_id', 'name', 'microphone_element', 'sensitivity', 'signal_to_noise_ratio'];
        $orderColumn = isset($columns[$column]) && $columns[$column] ? $columns[$column] : 'name';
        
        $sql .= " ORDER BY $orderColumn $dir LIMIT $length OFFSET $start";
        
        $this->database->prepareQuery($sql);
        $result = $this->database->executeSelect($params);
        
        if (count($result)) {
            foreach ($result as $key => $value) {
                $arr[$key][] = "<input type='checkbox' class='js-checkbox' data-id='$value[microphone_id]' name='cb[]'>";
                $arr[$key][] = "$value[microphone_id]<input type='hidden' name='itemID' value='$value[microphone_id]'>";
                $arr[$key][] = "<input type='text' class='form-control form-control-sm' name='name' value='" . htmlspecialchars($value['name']) . "' maxlength='100' required>";
                $arr[$key][] = "<input type='text' class='form-control form-control-sm' name='microphone_element' value='" . htmlspecialchars($value['microphone_element']) . "' maxlength='100'>";
                $arr[$key][] = "<input type='number' class='form-control form-control-sm' name='sensitivity' value='" . htmlspecialchars($value['sensitivity']) . "'>";
                $arr[$key][] = "<input type='number' class='form-control form-control-sm' name='signal_to_noise_ratio' value='" . htmlspecialchars($value['signal_to_noise_ratio']) . "'>";
            }
        }
        
        return $arr;
    }
}
