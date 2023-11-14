<?php

namespace BioSounds\Provider;

use BioSounds\Entity\AbstractProvider;
use BioSounds\Entity\Collection;
use BioSounds\Exception\Database\NotFoundException;
use BioSounds\Utils\Auth;
use Cassandra\Varint;

class CollectionProvider extends AbstractProvider
{
    const TABLE_NAME = "collection";

    public function getCollectionPagesByPermission(int $projectId): array
    {
        $sql = "SELECT c.* FROM collection c ";
        if (!Auth::isUserLogged()) {
            $sql .= 'WHERE c.public_access = 1 AND c.project_id = :projectId ';
        } elseif (!Auth::isUserAdmin()) {
            $sql .= 'WHERE ( c.public_access = 1 OR c.collection_id IN (SELECT up.collection_id FROM user_permission up, permission p WHERE up.permission_id = p.permission_id AND (p.name = "Access" OR p.name = "View" OR p.name = "Review" OR p.name = "Manage") AND up.user_id = ' . Auth::getUserID() . ')) AND c.project_id = :projectId ';
        } else {
            $sql .= 'WHERE c.project_id = :projectId ';
        }
        $sql = $sql . 'ORDER BY c.name ';
        $this->database->prepareQuery($sql);
        $result = $this->database->executeSelect([':projectId' => $projectId]);

        $data = [];
        foreach ($result as $item) {
            $data[] = (new Collection())
                ->setId($item['collection_id'])
                ->setName($item['name'])
                ->setUserId($item['user_id'])
                ->setDoi($item['doi'])
                ->setNote($item['note'])
                ->setSphere($item['sphere'] == null ? '' : $item['sphere'])
                ->setProject($item['project_id'])
                ->setCreationDate($item['creation_date'])
                ->setPublicAccess($item['public_access'])
                ->setPublicTags($item['public_tags'])
                ->setView($item['view']);
        }
        return $data;
    }

    /**
     * @param string $order
     * @return Collection[]
     * @throws \Exception
     */
    public function getList(string $order = 'name'): array
    {
        $data = [];
        $this->database->prepareQuery("SELECT * FROM collection ORDER BY $order");
        $result = $this->database->executeSelect();

        foreach ($result as $item) {
            $data[] = (new Collection())
                ->setId($item['collection_id'])
                ->setName($item['name'])
                ->setUserId($item['user_id'])
                ->setDoi($item['doi'])
                ->setNote($item['note'])
                ->setSphere($item['sphere'] == null ? '' : $item['sphere'])
                ->setProject($item['project_id'])
                ->setCreationDate($item['creation_date'])
                ->setPublicAccess($item['public_access'])
                ->setPublicTags($item['public_tags'])
                ->setView($item['view']);
        }

        return $data;
    }

    /**
     * @param int $id
     * @return Collection|null
     * @throws \Exception
     */
    public function get(int $id): ?Collection
    {
        $this->database->prepareQuery('SELECT * FROM collection WHERE collection_id = :id');

        if (empty($result = $this->database->executeSelect([':id' => $id]))) {
            throw new NotFoundException($id);
        }

        $result = $result[0];

        return (new Collection())
            ->setId($result['collection_id'])
            ->setName($result['name'])
            ->setUserId($result['user_id'])
            ->setDoi($result['doi'])
            ->setNote($result['note'])
            ->setSphere($result['sphere'] == null ? '' : $result['sphere'])
            ->setProject($result['project_id'])
            ->setCreationDate($result['creation_date'])
            ->setPublicAccess($result['public_access'])
            ->setPublicTags($result['public_tags'])
            ->setView($result['view'])
            ->setProject($result['project_id']);
    }

    /**
     * @param int $id
     * @return Collection|null
     * @throws \Exception
     */
    public function getByProject(int $project_id, ?int $user_id): ?array
    {
        if ($user_id == null) {
            $this->database->prepareQuery("SELECT c.* FROM collection c WHERE c.project_id = $project_id");
        } else {
            $this->database->prepareQuery("SELECT c.*,u.permission_id FROM collection c LEFT JOIN user_permission u ON u.collection_id = c.collection_id AND u.user_id = $user_id WHERE c.project_id = $project_id");
        }
        $results = $this->database->executeSelect();
        $data = [];
        foreach ($results as $item) {
            $data[] = (new Collection())
                ->setId($item['collection_id'])
                ->setName($item['name'])
                ->setUserId($item['user_id'])
                ->setDoi($item['doi'])
                ->setNote($item['note'])
                ->setSphere($item['sphere'] == null ? '' : $item['sphere'])
                ->setProject($item['project_id'])
                ->setSphere($item['sphere'] == null ? '' : $item['sphere'])
                ->setCreationDate($item['creation_date'])
                ->setPublicAccess($item['public_access'])
                ->setPublicTags($item['public_tags'])
                ->setView($item['view'])
                ->setPermission($item['permission_id'] == null ? 0 : $item['permission_id']);
        }
        return $data;
    }

    public function getWithSite(int $project_id, string $site_id): ?array
    {
        $this->database->prepareQuery('SELECT c.*,MAX(IF(site_id = :site_id, 1, 0)) AS site_id FROM collection c LEFT JOIN site_collection sc ON sc.collection_id = c.collection_id WHERE c.project_id = :project_id GROUP BY c.collection_id');
        $results = $this->database->executeSelect([':project_id' => $project_id, ':site_id' => $site_id]);
        $data = [];
        foreach ($results as $item) {
            $data[] = (new Collection())
                ->setId($item['collection_id'])
                ->setName($item['name'])
                ->setUserId($item['user_id'])
                ->setDoi($item['doi'])
                ->setNote($item['note'])
                ->setSphere($item['sphere'] == null ? '' : $item['sphere'])
                ->setProject($item['project_id'])
                ->setCreationDate($item['creation_date'])
                ->setPublicAccess($item['public_access'])
                ->setPublicTags($item['public_tags'])
                ->setView($item['view'])
                ->setPermission(count(explode(',', $site_id)) == 1 ? $item['site_id'] : 0);
        }
        return $data;
    }

    /**
     * @param string $order
     * @return Collection[]
     * @throws \Exception
     */
    public function getAccessedList(int $userId): array
    {
        $data = [];
        $this->database->prepareQuery('SELECT * FROM collection WHERE collection_id IN ( SELECT up.collection_id FROM user_permission up, permission p WHERE up.permission_id = p.permission_id AND (p.name = "Access" OR p.name = "View" OR p.name = "Review" OR p.name= "Manage") AND up.user_id = :userId) ORDER BY name');

        $result = $this->database->executeSelect([':userId' => $userId]);

        foreach ($result as $item) {
            $data[] = (new Collection())
                ->setId($item['collection_id'])
                ->setName($item['name'])
                ->setUserId($item['user_id'])
                ->setDoi($item['doi'])
                ->setNote($item['note'])
                ->setSphere($item['sphere'] == null ? '' : $item['sphere'])
                ->setProject($item['project_id'])
                ->setCreationDate($item['creation_date'])
                ->setPublicAccess($item['public_access'])
                ->setPublicTags($item['public_tags'])
                ->setView($item['view']);
        }

        return $data;
    }

    public function getManageList(int $userId): array
    {
        $data = [];
        $this->database->prepareQuery('SELECT * FROM collection WHERE collection_id IN ( SELECT up.collection_id FROM user_permission up, permission p WHERE up.permission_id = p.permission_id AND p.name= "Manage" AND up.user_id = :userId) ORDER BY name');

        $result = $this->database->executeSelect([':userId' => $userId]);

        foreach ($result as $item) {
            $data[] = (new Collection())
                ->setId($item['collection_id'])
                ->setName($item['name'])
                ->setUserId($item['user_id'])
                ->setDoi($item['doi'])
                ->setNote($item['note'])
                ->setSphere($item['sphere'] == null ? '' : $item['sphere'])
                ->setProject($item['project_id'])
                ->setCreationDate($item['creation_date'])
                ->setPublicAccess($item['public_access'])
                ->setPublicTags($item['public_tags'])
                ->setView($item['view']);
        }

        return $data;
    }

    public function getPublicList(int $userId): array
    {
        $data = [];
        $this->database->prepareQuery('SELECT * FROM collection WHERE collection_id IN ( SELECT up.collection_id FROM user_permission up, permission p WHERE up.permission_id = p.permission_id AND ( p.name= "Manage" OR p.name= "View" OR p.name= "Review") AND up.user_id = :userId) OR public_access = 1 ORDER BY name');

        $result = $this->database->executeSelect([':userId' => $userId]);

        foreach ($result as $item) {
            $data[] = (new Collection())
                ->setId($item['collection_id'])
                ->setName($item['name'])
                ->setUserId($item['user_id'])
                ->setDoi($item['doi'])
                ->setNote($item['note'])
                ->setSphere($item['sphere'] == null ? '' : $item['sphere'])
                ->setProject($item['project_id'])
                ->setCreationDate($item['creation_date'])
                ->setPublicAccess($item['public_access'])
                ->setPublicTags($item['public_tags'])
                ->setView($item['view']);
        }

        return $data;
    }

    /**
     * @param int $id
     * @throws \Exception
     */
    public function delete(int $id): void
    {
        $this->database->prepareQuery('DELETE FROM ' . Collection::TABLE_NAME . ' WHERE collection_id = :id');
        $this->database->executeDelete([':id' => $id]);
        $this->database->prepareQuery('DELETE FROM site_collection WHERE collection_id = :id');
        $this->database->executeDelete([':id' => $id]);
    }

    public function getCollection(string $projectId): array
    {
        $sql = "SELECT c.*,u.name as username FROM collection c LEFT JOIN user_permission up ON up.collection_id = c.collection_id AND up.user_id = :user_id LEFT JOIN user u ON u.user_id = c.user_id  WHERE c.project_id = :project_id ";
        $this->database->prepareQuery($sql);
        return $this->database->executeSelect([':project_id' => $projectId, ':user_id' => Auth::getUserID()]);
    }

    public function getFilterCount(string $projectId, string $search): int
    {
        $sql = "SELECT c.*,up.permission_id,u.name as username FROM collection c LEFT JOIN user_permission up ON up.collection_id = c.collection_id AND up.user_id = :user_id LEFT JOIN user u ON u.user_id = c.user_id  WHERE c.project_id = :project_id ";
        if ($search) {
            $sql .= " AND CONCAT(IFNULL(c.collection_id,''), IFNULL(c.name,''), IFNULL(u.name,''), IFNULL(c.doi,''), IFNULL(c.sphere,''), IFNULL(c.note,''), IFNULL(c.creation_date,''), IFNULL(c.view,'')) LIKE '%$search%' ";
        }
        $this->database->prepareQuery($sql);
        $count = count($this->database->executeSelect([':project_id' => $projectId, ':user_id' => Auth::getUserID()]));
        return $count;
    }

    public function getListByPage(string $projectId, string $start = '0', string $length = '8', string $search = null, string $column = '0', string $dir = 'asc'): array
    {
        $arr = [];
        $sql = "SELECT c.*,up.permission_id,u.name as username FROM collection c LEFT JOIN user_permission up ON up.collection_id = c.collection_id AND up.user_id = :user_id LEFT JOIN user u ON u.user_id = c.user_id  WHERE c.project_id = :project_id ";
        if ($search) {
            $sql .= " AND CONCAT(IFNULL(c.collection_id,''), IFNULL(c.name,''), IFNULL(u.name,''), IFNULL(c.doi,''), IFNULL(c.sphere,''), IFNULL(c.note,''), IFNULL(c.creation_date,''), IFNULL(c.view,'')) LIKE '%$search%' ";
        }
        $a = ['', 'c.collection_id', 'c.name', 'u.name', 'c.doi', 'c.sphere', 'c.note', 'c.creation_date', 'c.view', 'c.public_access', 'c.public_tags'];
        $sql .= " ORDER BY $a[$column] $dir LIMIT $length OFFSET $start";
        $this->database->prepareQuery($sql);
        $result = $this->database->executeSelect([':project_id' => $projectId, ':user_id' => Auth::getUserID()]);
        if (count($result)) {
            foreach ($result as $key => $value) {
                $arr[$key][] = "<input type='checkbox' class='js-checkbox'data-id='$value[collection_id]' data-name='$value[name]' name='cb[$value[collection_id]]' id='cb[$value[collection_id]]'>";
                $arr[$key][] = "$value[collection_id]<input id='col$value[collection_id]' type='hidden' name='collId' value='$value[collection_id]'><input id='project$value[collection_id]' type='hidden' name='project_id' value='$value[project_id]'>";
                $arr[$key][] = "<input type='text' class='form-control form-control-sm' id='$value[collection_id]' name='name' value='$value[name]'><small id='collectionValid$value[collection_id]' class='text-danger'></small>";
                $arr[$key][] = $value['username'];
                $arr[$key][] = "<input type='text' class='form-control form-control-sm' name='doi' value='$value[doi]'>";
                $arr[$key][] = "<select id='sphere' name='sphere' class='form-control form-control-sm'>
                            <option></option>
                            <option value='hydrosphere' " . ($value['sphere'] == 'hydrosphere' ? 'selected' : '') . ">hydrosphere</option>
                            <option value='cryosphere' " . ($value['sphere'] == 'cryosphere' ? 'selected' : '') . ">cryosphere</option>
                            <option value='lithosphere' " . ($value['sphere'] == 'lithosphere' ? 'selected' : '') . ">lithosphere</option>
                            <option value='pedosphere' " . ($value['sphere'] == 'pedosphere' ? 'selected' : '') . ">pedosphere</option>
                            <option value='atmosphere' " . ($value['sphere'] == 'atmosphere' ? 'selected' : '') . ">atmosphere</option>
                            <option value='biosphere' " . ($value['sphere'] == 'biosphere' ? 'selected' : '') . ">biosphere</option>
                            <option value='anthroposphere' " . ($value['sphere'] == 'anthroposphere' ? 'selected' : '') . ">anthroposphere</option>
                        </select>";
                $arr[$key][] = "<input type='text' class='form-control form-control-sm' name='note' value='$value[note]'>";
                $arr[$key][] = $value['creation_date'];
                $arr[$key][] = "<select id='view' name='view' class='form-control form-control-sm' required>;
                            <option value='gallery' " . ($value['view'] == 'gallery' ? 'selected' : '') . ">gallery</option>
                            <option value='list' " . ($value['view'] == 'list' ? 'selected' : '') . ">list</option>
                            <option value='timeline' " . ($value['view'] == 'timeline' ? 'selected' : '') . ">timeline</option>
                        </select>";
                $arr[$key][] = "<input name='public_access' type='checkbox' " . ($value['public_access'] ? 'checked' : '') . ">";
                $arr[$key][] = "<input name='public_tags' type='checkbox' " . ($value['public_tags'] ? 'checked' : '') . ">";
            }
        }
        return $arr;
    }
}
