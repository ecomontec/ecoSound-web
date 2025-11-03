<?php

namespace BioSounds\Provider;

use BioSounds\Entity\AbstractProvider;
use BioSounds\Entity\Species;

class SpeciesProvider extends AbstractProvider
{
    const TABLE_NAME = 'species';

    /**
     * Get species statistics with tag counts
     * @return array
     * @throws \Exception
     */
    public function getSpeciesStatistics(): array
    {
        $sql = "SELECT 
                    s.species_id,
                    s.binomial,
                    s.common_name,
                    s.genus,
                    s.family,
                    s.taxon_order,
                    s.class,
                    COUNT(DISTINCT t.tag_id) as tag_count,
                    COUNT(DISTINCT t.recording_id) as recording_count
                FROM species s
                LEFT JOIN tag t ON s.species_id = t.species_id
                GROUP BY s.species_id, s.binomial, s.common_name, s.genus, s.family, s.taxon_order, s.class
                HAVING tag_count > 0
                ORDER BY tag_count DESC, s.binomial ASC";
        
        $this->database->prepareQuery($sql);
        return $this->database->executeSelect();
    }

    /**
     * Get total number of species with tags
     * @return int
     * @throws \Exception
     */
    public function getSpeciesCount(): int
    {
        $sql = "SELECT COUNT(DISTINCT s.species_id) as count
                FROM species s
                INNER JOIN tag t ON s.species_id = t.species_id";
        
        $this->database->prepareQuery($sql);
        $result = $this->database->executeSelect();
        return (int) ($result[0]['count'] ?? 0);
    }

    /**
     * Get species statistics for a specific collection
     * @param int $collectionId
     * @return array
     * @throws \Exception
     */
    public function getSpeciesStatisticsByCollection(int $collectionId): array
    {
        $sql = "SELECT 
                    s.species_id,
                    s.binomial,
                    s.common_name,
                    s.genus,
                    s.family,
                    s.taxon_order,
                    s.class,
                    COUNT(DISTINCT t.tag_id) as tag_count,
                    COUNT(DISTINCT t.recording_id) as recording_count
                FROM species s
                INNER JOIN tag t ON s.species_id = t.species_id
                INNER JOIN recording r ON t.recording_id = r.recording_id
                WHERE r.col_id = :collectionId
                GROUP BY s.species_id, s.binomial, s.common_name, s.genus, s.family, s.taxon_order, s.class
                ORDER BY tag_count DESC, s.binomial ASC";
        
        $this->database->prepareQuery($sql);
        return $this->database->executeSelect([':collectionId' => $collectionId]);
    }

    /**
     * Get species statistics for a specific project
     * @param int $projectId
     * @return array
     * @throws \Exception
     */
    public function getSpeciesStatisticsByProject(int $projectId): array
    {
        $sql = "SELECT 
                    s.species_id,
                    s.binomial,
                    s.common_name,
                    s.genus,
                    s.family,
                    s.taxon_order,
                    s.class,
                    COUNT(DISTINCT t.tag_id) as tag_count,
                    COUNT(DISTINCT t.recording_id) as recording_count
                FROM species s
                INNER JOIN tag t ON s.species_id = t.species_id
                INNER JOIN recording r ON t.recording_id = r.recording_id
                INNER JOIN collection c ON r.col_id = c.collection_id
                WHERE c.project_id = :projectId
                GROUP BY s.species_id, s.binomial, s.common_name, s.genus, s.family, s.taxon_order, s.class
                ORDER BY tag_count DESC, s.binomial ASC";
        
        $this->database->prepareQuery($sql);
        return $this->database->executeSelect([':projectId' => $projectId]);
    }

    /**
     * Get total count of species
     * @return int
     * @throws \Exception
     */
    public function getTotalCount(): int
    {
        $sql = "SELECT COUNT(*) as count FROM species";
        $this->database->prepareQuery($sql);
        $result = $this->database->executeSelect();
        return (int) ($result[0]['count'] ?? 0);
    }

    /**
     * Get filtered count of species
     * @param string $search
     * @param string $classFilter
     * @param string $orderFilter
     * @param string $familyFilter
     * @return int
     * @throws \Exception
     */
    public function getFilteredCount(string $search = '', string $classFilter = '', string $orderFilter = '', string $familyFilter = ''): int
    {
        $sql = "SELECT COUNT(*) as count
                FROM species s
                LEFT JOIN tag t ON s.species_id = t.species_id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($search)) {
            $sql .= " AND (s.binomial LIKE :search OR s.common_name LIKE :search OR s.genus LIKE :search OR s.family LIKE :search)";
            $params[':search'] = "%$search%";
        }
        
        if (!empty($classFilter)) {
            $sql .= " AND s.class = :class";
            $params[':class'] = $classFilter;
        }
        
        if (!empty($orderFilter)) {
            $sql .= " AND s.taxon_order = :order";
            $params[':order'] = $orderFilter;
        }
        
        if (!empty($familyFilter)) {
            $sql .= " AND s.family = :family";
            $params[':family'] = $familyFilter;
        }
        
        $sql .= " GROUP BY s.species_id";
        
        $this->database->prepareQuery($sql);
        $result = $this->database->executeSelect($params);
        return count($result);
    }

    /**
     * Get paginated species list
     * @param string $start
     * @param string $length
     * @param string $search
     * @param string $column
     * @param string $dir
     * @param string $classFilter
     * @param string $orderFilter
     * @param string $familyFilter
     * @param bool $isEditable
     * @return array
     * @throws \Exception
     */
    public function getListByPage(string $start = '0', string $length = '10', string $search = '', string $column = '1', string $dir = 'asc', string $classFilter = '', string $orderFilter = '', string $familyFilter = '', bool $isEditable = false): array
    {
        $dir = ($dir === 'asc' || $dir === 'desc') ? $dir : 'asc';
        
        $sql = "SELECT 
                    s.species_id,
                    s.binomial,
                    s.common_name,
                    s.genus,
                    s.family,
                    s.taxon_order,
                    s.class,
                    COUNT(DISTINCT t.tag_id) as tag_count,
                    COUNT(DISTINCT t.recording_id) as recording_count
                FROM species s
                LEFT JOIN tag t ON s.species_id = t.species_id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($search)) {
            $sql .= " AND (s.binomial LIKE :search OR s.common_name LIKE :search OR s.genus LIKE :search OR s.family LIKE :search)";
            $params[':search'] = "%$search%";
        }
        
        if (!empty($classFilter)) {
            $sql .= " AND s.class = :class";
            $params[':class'] = $classFilter;
        }
        
        if (!empty($orderFilter)) {
            $sql .= " AND s.taxon_order = :order";
            $params[':order'] = $orderFilter;
        }
        
        if (!empty($familyFilter)) {
            $sql .= " AND s.family = :family";
            $params[':family'] = $familyFilter;
        }
        
        $sql .= " GROUP BY s.species_id, s.binomial, s.common_name, s.genus, s.family, s.taxon_order, s.class";
        
        // Column sorting
        $columns = ['', 's.species_id', 's.binomial', 's.common_name', 's.genus', 's.family', 's.taxon_order', 's.class', 'tag_count', 'recording_count'];
        if (isset($columns[$column])) {
            $sql .= " ORDER BY " . $columns[$column] . " $dir";
        }
        
        if ($length != '-1') {
            $sql .= " LIMIT $length OFFSET $start";
        }
        
        $this->database->prepareQuery($sql);
        $result = $this->database->executeSelect($params);
        
        $arr = [];
        if (count($result)) {
            foreach ($result as $key => $value) {
                if ($isEditable) {
                    $arr[$key][] = '<input type="checkbox" class="js-checkbox" data-id="' . $value['species_id'] . '">';
                } else {
                    $arr[$key][] = '';
                }
                $arr[$key][] = $value['species_id'];
                
                if ($isEditable) {
                    $arr[$key][] = '<input type="text" class="form-control form-control-sm" name="binomial" value="' . htmlspecialchars($value['binomial']) . '" data-id="' . $value['species_id'] . '">';
                    $arr[$key][] = '<input type="text" class="form-control form-control-sm" name="common_name" value="' . htmlspecialchars($value['common_name'] ?? '') . '" data-id="' . $value['species_id'] . '">';
                    $arr[$key][] = '<input type="text" class="form-control form-control-sm" name="genus" value="' . htmlspecialchars($value['genus'] ?? '') . '" data-id="' . $value['species_id'] . '">';
                    $arr[$key][] = '<input type="text" class="form-control form-control-sm" name="family" value="' . htmlspecialchars($value['family'] ?? '') . '" data-id="' . $value['species_id'] . '">';
                    $arr[$key][] = '<input type="text" class="form-control form-control-sm" name="taxon_order" value="' . htmlspecialchars($value['taxon_order'] ?? '') . '" data-id="' . $value['species_id'] . '">';
                    $arr[$key][] = '<input type="text" class="form-control form-control-sm" name="class" value="' . htmlspecialchars($value['class'] ?? '') . '" data-id="' . $value['species_id'] . '">';
                } else {
                    $arr[$key][] = '<em>' . htmlspecialchars($value['binomial']) . '</em>';
                    $arr[$key][] = htmlspecialchars($value['common_name'] ?? '-');
                    $arr[$key][] = htmlspecialchars($value['genus'] ?? '-');
                    $arr[$key][] = htmlspecialchars($value['family'] ?? '-');
                    $arr[$key][] = htmlspecialchars($value['taxon_order'] ?? '-');
                    $arr[$key][] = htmlspecialchars($value['class'] ?? '-');
                }
                
                $arr[$key][] = $value['tag_count'];
                $arr[$key][] = $value['recording_count'];
            }
        }
        
        return $arr;
    }

    /**
     * Get distinct classes
     * @return array
     * @throws \Exception
     */
    public function getDistinctClasses(): array
    {
        $sql = "SELECT DISTINCT class FROM species WHERE class IS NOT NULL AND class != '' ORDER BY class";
        $this->database->prepareQuery($sql);
        return $this->database->executeSelect();
    }

    /**
     * Get distinct orders
     * @return array
     * @throws \Exception
     */
    public function getDistinctOrders(): array
    {
        $sql = "SELECT DISTINCT taxon_order FROM species WHERE taxon_order IS NOT NULL AND taxon_order != '' ORDER BY taxon_order";
        $this->database->prepareQuery($sql);
        return $this->database->executeSelect();
    }

    /**
     * Get distinct families
     * @return array
     * @throws \Exception
     */
    public function getDistinctFamilies(): array
    {
        $sql = "SELECT DISTINCT family FROM species WHERE family IS NOT NULL AND family != '' ORDER BY family";
        $this->database->prepareQuery($sql);
        return $this->database->executeSelect();
    }

    /**
     * Insert new species
     * @param array $data
     * @return int|null
     * @throws \Exception
     */
    public function insert(array $data)
    {
        $sql = "INSERT INTO species (binomial, common_name, genus, family, taxon_order, class) 
                VALUES (:binomial, :common_name, :genus, :family, :taxon_order, :class)";
        
        $this->database->prepareQuery($sql);
        return $this->database->executeInsert([
            ':binomial' => $data['binomial'],
            ':common_name' => $data['common_name'],
            ':genus' => $data['genus'],
            ':family' => $data['family'],
            ':taxon_order' => $data['taxon_order'],
            ':class' => $data['class'],
        ]);
    }

    /**
     * Update species
     * @param int $speciesId
     * @param array $data
     * @return int
     * @throws \Exception
     */
    public function update(int $speciesId, array $data)
    {
        $sql = "UPDATE species 
                SET binomial = :binomial, 
                    common_name = :common_name, 
                    genus = :genus, 
                    family = :family, 
                    taxon_order = :taxon_order, 
                    class = :class
                WHERE species_id = :species_id";
        
        $this->database->prepareQuery($sql);
        return $this->database->executeUpdate([
            ':species_id' => $speciesId,
            ':binomial' => $data['binomial'],
            ':common_name' => $data['common_name'],
            ':genus' => $data['genus'],
            ':family' => $data['family'],
            ':taxon_order' => $data['taxon_order'],
            ':class' => $data['class'],
        ]);
    }

    /**
     * Delete species
     * @param int $speciesId
     * @return int
     * @throws \Exception
     */
    public function delete(int $speciesId)
    {
        $sql = "DELETE FROM species WHERE species_id = :species_id";
        $this->database->prepareQuery($sql);
        return $this->database->executeDelete([':species_id' => $speciesId]);
    }
}
