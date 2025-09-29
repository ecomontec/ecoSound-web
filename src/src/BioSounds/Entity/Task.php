<?php

namespace BioSounds\Entity;

class Task extends AbstractProvider
{
    /**
     * @param array $data
     * @return int
     * @throws \Exception
     */
    public function insert(array $data): int
    {
        if (empty($data)) {
            return 0;
        }

        $batchData = isset($data[0]) ? $data : [$data];
        $fields = array_keys($batchData[0]);

        $sql = "INSERT INTO task (" . implode(', ', $fields) . ") VALUES ";
        $values = [];
        $valueParts = [];

        foreach ($batchData as $i => $row) {
            $placeholders = [];
            foreach ($fields as $field) {
                $param = "{$field}_{$i}";
                $placeholders[] = ":{$param}";
                $values[$param] = $row[$field];
            }
            $valueParts[] = "(" . implode(', ', $placeholders) . ")";
        }

        $sql .= implode(', ', $valueParts);
        $this->database->prepareQuery($sql);
        return $this->database->executeInsert($values);
    }


    /**
     * @param array $data
     * @return bool
     * @throws \Exception
     */
    public function update(array $data): bool
    {
        if (empty($data)) {
            return false;
        }

        $taskId = $data["task_id"];
        unset($data["task_id"]);
        $fields = '';
        $values = [];

        foreach ($data as $key => $value) {
            $fields .= $key . ' = :' . $key;
            $values[':' . $key] = $value;
            $fields .= ",";
        }
        $fields = substr($fields, 0, strlen($fields) - 1);

        $values[':task_id'] = $taskId;
        $this->database->prepareQuery("UPDATE task SET $fields WHERE task_id = :task_id");
        return $this->database->executeUpdate($values);
    }
}
