<?php

namespace BioSounds\Entity;

use BioSounds\Provider\BaseProvider;

class Species extends BaseProvider
{
    const TABLE_NAME = 'species';
    const ID = 'species_id';
    const BINOMIAL = 'binomial';
    const GENUS = 'genus';
    const FAMILY = 'family';
    const NAME = 'common_name';
    const ORDER = 'taxon_order';
    const SPECIES_CLASS = 'class';
    const LEVEL = 'level';
    const REGION = 'region';

    public function get()
    {
        $query = 'SELECT * FROM ' . self::TABLE_NAME;
        $this->database->prepareQuery($query);
        $result = $this->database->executeSelect();
        return $result;
    }

    public function getAll()
    {
        $query = 'SELECT * FROM ' . self::TABLE_NAME . ' ORDER BY ' . self::BINOMIAL . ' ASC';
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

    public function getByName(string $name)
    {
        $query = 'SELECT * FROM species WHERE binomial= :name ';
        $this->database->prepareQuery($query);
        $result = $this->database->executeSelect([':name' => $name]);
        return $result;
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
        // Debug log
        file_put_contents('/tmp/species_insert_debug.log', "DATA:\n" . print_r($data, true) . "\nQUERY:\n" . $query . "\nVALUES:\n" . print_r($values, true) . "\n\n", FILE_APPEND);
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

    /**
     * @param array $names
     * @return array|int
     * @throws \Exception
     */
    public function getList(array $names)
    {
        $query = 'SELECT * FROM ' . self::TABLE_NAME;

        $fields = [];
        if (isset($names)) {
            if (count($names) == 1) {
                $query .= ' WHERE ' . self::BINOMIAL . ' LIKE :binomial OR ' . self::NAME . ' LIKE :name ';
                $fields = [
                    ':binomial' => "%$names[0]%",
                    ':name' => "%$names[0]%"
                ];
            } else {
                $query .= ' WHERE (' . self::BINOMIAL . ' LIKE :binomial1 AND ';
                $query .= self::BINOMIAL . ' LIKE :binomial2) ';
                $query .= 'OR (' . self::NAME . ' LIKE :name1 AND ' . self::NAME . ' LIKE :name2) ';
                $fields = [
                    ':binomial1' => "%$names[0]%",
                    ':binomial2' => "%$names[1]%",
                    ':name1' => "%$names[0]%",
                    ':name2' => "%$names[1]%"
                ];
            }
        }
        $query .= 'ORDER BY ' . self::BINOMIAL . ' ASC LIMIT 0,15';

        $this->database->prepareQuery($query);
        $result = $this->database->executeSelect($fields);

        return $result;
    }

    /**
     * Get paginated list of species for DataTables server-side processing
     */
    public function getListByPage(string $start = '0', string $length = '10', string $search = null, string $column = '1', string $dir = 'asc'): array
    {
        $arr = [];
        
        $sql = "SELECT * FROM " . self::TABLE_NAME . " WHERE 1=1";
        $params = [];
        
        if ($search) {
            $sql .= " AND (CONCAT(IFNULL(binomial,''), ' ', IFNULL(common_name,''), ' ', IFNULL(genus,''), ' ', IFNULL(family,''), ' ', IFNULL(taxon_order,''), ' ', IFNULL(class,'')) LIKE :search)";
            $params[':search'] = "%$search%";
        }
        
        // Column mapping for sorting
        $columns = ['', 'species_id', 'binomial', 'common_name', 'genus', 'family', 'taxon_order', 'class', 'region'];
        $orderColumn = isset($columns[$column]) && $columns[$column] ? $columns[$column] : 'binomial';
        
        $sql .= " ORDER BY $orderColumn $dir LIMIT $length OFFSET $start";
        
        $this->database->prepareQuery($sql);
        $result = $this->database->executeSelect($params);
        
        if (count($result)) {
            foreach ($result as $key => $value) {
                $arr[$key][] = "<input type='checkbox' class='js-checkbox' data-id='$value[species_id]' name='cb[]'>";
                $arr[$key][] = "$value[species_id]<input type='hidden' name='itemID' value='$value[species_id]'>";
                $arr[$key][] = "<input type='text' class='form-control form-control-sm' name='binomial' value='" . htmlspecialchars($value['binomial']) . "'>";
                $arr[$key][] = "<input type='text' class='form-control form-control-sm' name='common_name' value='" . htmlspecialchars($value['common_name']) . "'>";
                $arr[$key][] = "<input type='text' class='form-control form-control-sm' name='genus' value='" . htmlspecialchars($value['genus']) . "'>";
                $arr[$key][] = "<input type='text' class='form-control form-control-sm' name='family' value='" . htmlspecialchars($value['family']) . "'>";
                $arr[$key][] = "<input type='text' class='form-control form-control-sm' name='taxon_order' value='" . htmlspecialchars($value['taxon_order']) . "'>";
                $arr[$key][] = "<input type='text' class='form-control form-control-sm' name='class' value='" . htmlspecialchars($value['class']) . "'>";
                $arr[$key][] = "<input type='text' class='form-control form-control-sm' name='region' value='" . htmlspecialchars($value['region']) . "'>";
            }
        }
        
        return $arr;
    }
}
