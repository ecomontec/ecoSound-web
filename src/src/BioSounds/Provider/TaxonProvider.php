<?php

namespace BioSounds\Provider;

use BioSounds\Entity\AbstractProvider;

class TaxonProvider extends AbstractProvider
{
    const TABLE_NAME = 'taxon';

    public function getTaxonStatistics(): array
    {
        $sql = "SELECT 
                    t.taxon_id,
                    t.binomial,
                    t.common_name,
                    t.genus,
                    t.familia,
                    t.ordo,
                    t.classis,
                    t.phylum,
                    t.source,
                    COUNT(DISTINCT tag.tag_id) as tag_count,
                    COUNT(DISTINCT tag.recording_id) as recording_count
                FROM taxon t
                LEFT JOIN tag ON t.taxon_id = tag.taxon_id
                GROUP BY t.taxon_id, t.binomial, t.common_name, t.genus, t.familia, t.ordo, t.classis, t.phylum, t.source
                HAVING tag_count > 0
                ORDER BY tag_count DESC, t.binomial ASC";
        
        $this->database->prepareQuery($sql);
        return $this->database->executeSelect();
    }

    public function getTaxonCount(): int
    {
        $sql = "SELECT COUNT(DISTINCT t.taxon_id) as count
                FROM taxon t
                INNER JOIN tag ON t.taxon_id = tag.taxon_id";
        
        $this->database->prepareQuery($sql);
        $result = $this->database->executeSelect();
        return (int) ($result[0]['count'] ?? 0);
    }

    public function getTotalCount(): int
    {
        $sql = "SELECT COUNT(*) as count FROM taxon";
        $this->database->prepareQuery($sql);
        $result = $this->database->executeSelect();
        return (int) ($result[0]['count'] ?? 0);
    }

    public function getFilteredCount(string $search = '', string $classisFilter = '', string $ordoFilter = '', string $familiaFilter = ''): int
    {
        $sql = "SELECT COUNT(*) as count
                FROM taxon t
                LEFT JOIN tag ON t.taxon_id = tag.taxon_id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($search)) {
            $sql .= " AND (t.binomial LIKE :search OR t.common_name LIKE :search OR t.genus LIKE :search OR t.familia LIKE :search)";
            $params[':search'] = "%$search%";
        }
        
        if (!empty($classisFilter)) {
            $sql .= " AND t.classis = :classis";
            $params[':classis'] = $classisFilter;
        }
        
        if (!empty($ordoFilter)) {
            $sql .= " AND t.ordo = :ordo";
            $params[':ordo'] = $ordoFilter;
        }
        
        if (!empty($familiaFilter)) {
            $sql .= " AND t.familia = :familia";
            $params[':familia'] = $familiaFilter;
        }
        
        $sql .= " GROUP BY t.taxon_id";
        
        $this->database->prepareQuery($sql);
        $result = $this->database->executeSelect($params);
        return count($result);
    }

    public function getListByPage(string $start = '0', string $length = '10', string $search = '', string $column = '1', string $dir = 'asc', string $classisFilter = '', string $ordoFilter = '', string $familiaFilter = '', bool $isEditable = false): array
    {
        $dir = ($dir === 'asc' || $dir === 'desc') ? $dir : 'asc';
        
        $sql = "SELECT 
                    t.taxon_id,
                    t.binomial,
                    t.common_name,
                    t.genus,
                    t.familia,
                    t.ordo,
                    t.classis,
                    t.phylum,
                    t.source,
                    COUNT(DISTINCT tag.tag_id) as tag_count,
                    COUNT(DISTINCT tag.recording_id) as recording_count
                FROM taxon t
                LEFT JOIN tag ON t.taxon_id = tag.taxon_id
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($search)) {
            $sql .= " AND (t.binomial LIKE :search OR t.common_name LIKE :search OR t.genus LIKE :search OR t.familia LIKE :search)";
            $params[':search'] = "%$search%";
        }
        
        if (!empty($classisFilter)) {
            $sql .= " AND t.classis = :classis";
            $params[':classis'] = $classisFilter;
        }
        
        if (!empty($ordoFilter)) {
            $sql .= " AND t.ordo = :ordo";
            $params[':ordo'] = $ordoFilter;
        }
        
        if (!empty($familiaFilter)) {
            $sql .= " AND t.familia = :familia";
            $params[':familia'] = $familiaFilter;
        }
        
        $sql .= " GROUP BY t.taxon_id, t.binomial, t.common_name, t.genus, t.familia, t.ordo, t.classis, t.phylum, t.source";
        
        $columns = ['', 't.taxon_id', 't.binomial', 't.common_name', 't.genus', 't.familia', 't.ordo', 't.classis', 't.phylum', 't.source', 'tag_count', 'recording_count'];
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
                    $arr[$key][] = '<input type="checkbox" class="js-checkbox" data-id="' . $value['taxon_id'] . '">';
                } else {
                    $arr[$key][] = '';
                }
                $arr[$key][] = $value['taxon_id'];
                
                if ($isEditable) {
                    $arr[$key][] = '<input type="text" class="form-control form-control-sm" name="binomial" value="' . htmlspecialchars($value['binomial']) . '" data-id="' . $value['taxon_id'] . '">';
                    $arr[$key][] = '<input type="text" class="form-control form-control-sm" name="common_name" value="' . htmlspecialchars($value['common_name'] ?? '') . '" data-id="' . $value['taxon_id'] . '">';
                    $arr[$key][] = '<input type="text" class="form-control form-control-sm" name="genus" value="' . htmlspecialchars($value['genus'] ?? '') . '" data-id="' . $value['taxon_id'] . '">';
                    $arr[$key][] = '<input type="text" class="form-control form-control-sm" name="familia" value="' . htmlspecialchars($value['familia'] ?? '') . '" data-id="' . $value['taxon_id'] . '">';
                    $arr[$key][] = '<input type="text" class="form-control form-control-sm" name="ordo" value="' . htmlspecialchars($value['ordo'] ?? '') . '" data-id="' . $value['taxon_id'] . '">';
                    $arr[$key][] = '<input type="text" class="form-control form-control-sm" name="classis" value="' . htmlspecialchars($value['classis'] ?? '') . '" data-id="' . $value['taxon_id'] . '" required>';
                    $arr[$key][] = '<input type="text" class="form-control form-control-sm" name="phylum" value="' . htmlspecialchars($value['phylum'] ?? '') . '" data-id="' . $value['taxon_id'] . '">';
                    $arr[$key][] = '<input type="text" class="form-control form-control-sm" name="source" value="' . htmlspecialchars($value['source'] ?? '') . '" data-id="' . $value['taxon_id'] . '">';
                } else {
                    $arr[$key][] = '<em>' . htmlspecialchars($value['binomial']) . '</em>';
                    $arr[$key][] = htmlspecialchars($value['common_name'] ?? '-');
                    $arr[$key][] = htmlspecialchars($value['genus'] ?? '-');
                    $arr[$key][] = htmlspecialchars($value['familia'] ?? '-');
                    $arr[$key][] = htmlspecialchars($value['ordo'] ?? '-');
                    $arr[$key][] = htmlspecialchars($value['classis'] ?? '-');
                    $arr[$key][] = htmlspecialchars($value['phylum'] ?? '-');
                    $arr[$key][] = htmlspecialchars($value['source'] ?? '-');
                }
                
                $arr[$key][] = $value['tag_count'];
                $arr[$key][] = $value['recording_count'];
            }
        }
        
        return $arr;
    }

    public function getDistinctClassis(): array
    {
        $sql = "SELECT DISTINCT classis FROM taxon WHERE classis IS NOT NULL AND classis != '' ORDER BY classis";
        $this->database->prepareQuery($sql);
        return $this->database->executeSelect();
    }

    public function getDistinctOrdo(): array
    {
        $sql = "SELECT DISTINCT ordo FROM taxon WHERE ordo IS NOT NULL AND ordo != '' ORDER BY ordo";
        $this->database->prepareQuery($sql);
        return $this->database->executeSelect();
    }

    public function getDistinctFamilia(): array
    {
        $sql = "SELECT DISTINCT familia FROM taxon WHERE familia IS NOT NULL AND familia != '' ORDER BY familia";
        $this->database->prepareQuery($sql);
        return $this->database->executeSelect();
    }

    public function getDistinctSources(): array
    {
        $sql = "SELECT DISTINCT source FROM taxon WHERE source IS NOT NULL AND source != '' ORDER BY source";
        $this->database->prepareQuery($sql);
        return $this->database->executeSelect();
    }

    public function getHigherRanks(string $fieldName, string $fieldValue): ?array
    {
        if (empty($fieldValue)) {
            return null;
        }
        
        $allowedFields = ['binomial', 'genus', 'familia', 'ordo', 'classis'];
        if (!in_array($fieldName, $allowedFields)) {
            return null;
        }
        
        $sql = "SELECT genus, familia, ordo, classis, phylum 
                FROM taxon 
                WHERE $fieldName = :value 
                LIMIT 1";
        
        $this->database->prepareQuery($sql);
        $result = $this->database->executeSelect([':value' => $fieldValue]);
        
        return $result[0] ?? null;
    }

    public function getAutocompleteSuggestions(string $fieldName, string $searchTerm): array
    {
        $allowedFields = ['binomial', 'genus', 'familia', 'ordo', 'classis', 'phylum', 'source'];
        if (!in_array($fieldName, $allowedFields)) {
            return [];
        }
        
        $sql = "SELECT DISTINCT $fieldName as value 
                FROM taxon 
                WHERE $fieldName LIKE :search 
                ORDER BY $fieldName 
                LIMIT 20";
        
        $this->database->prepareQuery($sql);
        return $this->database->executeSelect([':search' => "$searchTerm%"]);
    }

    public function insert(array $data)
    {
        $sql = "INSERT INTO taxon (binomial, common_name, genus, familia, ordo, classis, phylum, source) 
                VALUES (:binomial, :common_name, :genus, :familia, :ordo, :classis, :phylum, :source)";
        
        $this->database->prepareQuery($sql);
        return $this->database->executeInsert([
            ':binomial' => $data['binomial'],
            ':common_name' => $data['common_name'] ?? '',
            ':genus' => $data['genus'] ?? '',
            ':familia' => $data['familia'] ?? '',
            ':ordo' => $data['ordo'] ?? '',
            ':classis' => $data['classis'],
            ':phylum' => $data['phylum'] ?? '',
            ':source' => $data['source'] ?? '',
        ]);
    }

    public function update(int $taxonId, array $data)
    {
        $sql = "UPDATE taxon 
                SET binomial = :binomial, 
                    common_name = :common_name, 
                    genus = :genus, 
                    familia = :familia, 
                    ordo = :ordo, 
                    classis = :classis,
                    phylum = :phylum,
                    source = :source
                WHERE taxon_id = :taxon_id";
        
        $this->database->prepareQuery($sql);
        return $this->database->executeUpdate([
            ':taxon_id' => $taxonId,
            ':binomial' => $data['binomial'],
            ':common_name' => $data['common_name'] ?? '',
            ':genus' => $data['genus'] ?? '',
            ':familia' => $data['familia'] ?? '',
            ':ordo' => $data['ordo'] ?? '',
            ':classis' => $data['classis'],
            ':phylum' => $data['phylum'] ?? '',
            ':source' => $data['source'] ?? '',
        ]);
    }

    public function delete(int $taxonId)
    {
        $sql = "DELETE FROM taxon WHERE taxon_id = :taxon_id";
        $this->database->prepareQuery($sql);
        return $this->database->executeDelete([':taxon_id' => $taxonId]);
    }
}
