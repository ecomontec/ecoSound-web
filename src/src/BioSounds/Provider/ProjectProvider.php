<?php

namespace BioSounds\Provider;

use BioSounds\Entity\AbstractProvider;
use BioSounds\Entity\Project;
use BioSounds\Exception\Database\NotFoundException;
use BioSounds\Utils\Auth;

class ProjectProvider extends AbstractProvider
{
    const TABLE_NAME = "project";

    public function getList(): array
    {
        $sql = "SELECT * FROM project WHERE active = 1";

        $this->database->prepareQuery($sql);
        $result = $this->database->executeSelect();

        $data = [];
        foreach ($result as $item) {
            $data[] = (new Project())
                ->setId($item['project_id'])
                ->setName($item['name'])
                ->setDescription($item['description'])
                ->setDescriptionShort($item['description_short'])
                ->setCreatorId($item['creator_id'])
                ->setCreationDate($item['creation_date'])
                ->setUrl($item['url'])
                ->setPictureId($item['picture_id'] ? $item['picture_id'] : '')
                ->setPublic($item['public']);
        }

        return $data;
    }

    public function get(int $id): ?Project
    {
        $this->database->prepareQuery('SELECT * FROM project WHERE project_id = :id');

        if (empty($result = $this->database->executeSelect([':id' => $id]))) {
            throw new NotFoundException($id);
        }

        $result = $result[0];

        return (new Project())
            ->setId($result['project_id'])
            ->setName($result['name'])
            ->setDescription($result['description'])
            ->setDescriptionShort($result['description_short'])
            ->setCreatorId($result['creator_id'])
            ->setCreationDate($result['creation_date'])
            ->setUrl($result['url'])
            ->setPictureId($result['picture_id'] ? $result['picture_id'] : '')
            ->setPublic($result['public']);
    }

    public function getWithPermission($userId = null, int $disalbe = 1): array
    {
        if (Auth::isUserAdmin()) {
            if ($userId == null) {
                $sql = "SELECT p.* FROM project p LEFT JOIN collection c ON p.project_id = c.project_id LEFT JOIN user_permission u ON u.collection_id = c.collection_id GROUP BY p.project_id ORDER BY p.project_id";
            } else {
                $sql = "SELECT p.*,MAX(c.collection_id) AS collection_id,MAX( u.permission_id ) AS permission_id FROM project p LEFT JOIN collection c ON p.project_id = c.project_id LEFT JOIN user_permission u ON u.collection_id = c.collection_id AND u.user_id = $userId GROUP BY p.project_id ORDER BY p.project_id";
            }
        } else {
            $str = $disalbe ? ' WHERE u1.permission_id = 4 ' : ' WHERE u1.permission_id IS NOT NULL';
            if ($userId == null) {
                $sql = "SELECT p.* FROM project p LEFT JOIN collection c ON p.project_id = c.project_id LEFT JOIN user_permission u1 ON u1.collection_id = c.collection_id AND u1.user_id = " . Auth::getUserID() . $str . " GROUP BY p.project_id ORDER BY p.project_id";
            } else {
                $sql = "SELECT p.*,MAX(c.collection_id) AS collection_id,MAX( u2.permission_id ) AS permission_id FROM project p LEFT JOIN collection c ON p.project_id = c.project_id LEFT JOIN user_permission u1 ON u1.collection_id = c.collection_id AND u1.user_id = " . Auth::getUserID() . " LEFT JOIN user_permission u2 ON u2.collection_id = c.collection_id AND u2.user_id = $userId " . $str . " GROUP BY p.project_id ORDER BY p.project_id";
            }
        }
        $this->database->prepareQuery($sql);
        $result = $this->database->executeSelect();

        $data = [];
        foreach ($result as $item) {
            $data[] = (new Project())
                ->setId($item['project_id'])
                ->setName($item['name'])
                ->setDescription($item['description'])
                ->setDescriptionShort($item['description_short'])
                ->setCreatorId($item['creator_id'])
                ->setCreationDate($item['creation_date'])
                ->setUrl($item['url'])
                ->setPictureId($item['picture_id'] ? $item['picture_id'] : '')
                ->setPublic($item['public'])
                ->setCollections((new CollectionProvider())->getByProject($item['project_id'], $userId))
                ->setPermission($item['permission_id'] == null ? 0 : $item['permission_id'])
                ->setActive($item['active'])
                ->setCollection($item['collection_id']);
        }
        return $data;
    }

    public function getSiteProjectWithPermission($userId, $siteId): array
    {
        if (Auth::isUserAdmin()) {
            $sql = "SELECT p.* FROM project p LEFT JOIN collection c ON p.project_id = c.project_id LEFT JOIN site_collection sc ON sc.collection_id = c.collection_id GROUP BY p.project_id ORDER BY p.project_id";
        } else {
            $sql = "SELECT p.* FROM project p LEFT JOIN collection c ON p.project_id = c.project_id LEFT JOIN site_collection sc ON sc.collection_id = c.collection_id LEFT JOIN user_permission u ON u.collection_id = c.collection_id WHERE u.permission_id = 4 AND u.user_id = $userId GROUP BY p.project_id ORDER BY p.project_id";
        }
        $this->database->prepareQuery($sql);
        $result = $this->database->executeSelect();

        $data = [];
        foreach ($result as $item) {
            $data[] = (new Project())
                ->setId($item['project_id'])
                ->setName($item['name'])
                ->setDescription($item['description'])
                ->setDescriptionShort($item['description_short'])
                ->setCreatorId($item['creator_id'])
                ->setCreationDate($item['creation_date'])
                ->setUrl($item['url'])
                ->setPictureId($item['picture_id'] ? $item['picture_id'] : '')
                ->setPublic($item['public'])
                ->setCollections((new CollectionProvider())->getWithSite($item['project_id'], $siteId))
                ->setPermission($item['permission_id'] == null ? 0 : $item['permission_id'])
                ->setActive($item['active']);
        }
        return $data;
    }

    /**
     * @param array $Data
     * @return int
     * @throws \Exception
     */
    public function insert(array $Data): int
    {
        if (empty($Data)) {
            return false;
        }

        $fields = "( ";
        $valuesNames = "( ";
        $values = array();

        foreach ($Data as $key => $value) {
            $fields .= $key;
            $valuesNames .= ":" . $key;
            $values[":" . $key] = $value;
            $fields .= ",";
            $valuesNames .= ",";
        }
        $fields = substr($fields, 0, strlen($fields) - 1) . ' )';
        $valuesNames = substr($valuesNames, 0, strlen($valuesNames) - 1) . ' )';
        $this->database->prepareQuery("INSERT INTO project $fields VALUES $valuesNames");
        return $this->database->executeInsert($values);
    }


    /**
     * @param array $Data
     * @return bool
     * @throws \Exception
     */
    public function update(array $Data): bool
    {
        if (empty($Data)) {
            return false;
        }

        $projectId = $Data["projectId"];
        unset($Data["projectId"]);
        $fields = '';
        $values = [];

        foreach ($Data as $key => $value) {
            $fields .= $key . ' = :' . $key;
            $values[':' . $key] = $value;
            $fields .= ",";
        }
        $fields = substr($fields, 0, strlen($fields) - 1);

        $values[':projectId'] = $projectId;

        $this->database->prepareQuery("UPDATE project SET $fields WHERE project_id = :projectId");
        return $this->database->executeUpdate($values);
    }

    public function isValid($str, $project_id)
    {
        $sql = "SELECT * FROM project WHERE `name` = '$str'";
        if (isset($project_id)) {
            $sql = $sql . " and project_id != $project_id";
        }
        $this->database->prepareQuery($sql);
        $result = $this->database->executeSelect();
        if (count($result) > 0) {
            return true;
        }
        return false;
    }

    public function getProject(): array
    {
        if (Auth::isUserAdmin()) {
            $sql = "SELECT p.*,u.name AS username,MAX(c.collection_id) AS collection_id,MAX( u1.permission_id ) AS permission_id FROM project p LEFT JOIN user u ON p.creator_id = u.user_id LEFT JOIN collection c ON p.project_id = c.project_id LEFT JOIN user_permission u1 ON u1.collection_id = c.collection_id AND u1.user_id = :userId ";
        } else {
            $sql = "SELECT p.*,u.name AS username,MAX(c.collection_id) AS collection_id,MAX( u2.permission_id ) AS permission_id FROM project p LEFT JOIN user u ON p.creator_id = u.user_id LEFT JOIN collection c ON p.project_id = c.project_id LEFT JOIN user_permission u1 ON u1.collection_id = c.collection_id AND u1.user_id = " . Auth::getUserID() . " LEFT JOIN user_permission u2 ON u2.collection_id = c.collection_id AND u2.user_id = :userId  WHERE u1.permission_id = 4 ";
        }
        $sql .= " GROUP BY p.project_id ";
        $this->database->prepareQuery($sql);
        return $this->database->executeSelect([':userId' => Auth::getUserID()]);
    }

    public function getFilterCount(string $search): int
    {
        if (Auth::isUserAdmin()) {
            $sql = "SELECT p.*,u.name AS username,MAX(c.collection_id) AS collection_id,MAX( u1.permission_id ) AS permission_id FROM project p LEFT JOIN user u ON p.creator_id = u.user_id LEFT JOIN collection c ON p.project_id = c.project_id LEFT JOIN user_permission u1 ON u1.collection_id = c.collection_id AND u1.user_id = :userId ";
        } else {
            $sql = "SELECT p.*,u.name AS username,MAX(c.collection_id) AS collection_id,MAX( u2.permission_id ) AS permission_id FROM project p LEFT JOIN user u ON p.creator_id = u.user_id LEFT JOIN collection c ON p.project_id = c.project_id LEFT JOIN user_permission u1 ON u1.collection_id = c.collection_id AND u1.user_id = " . Auth::getUserID() . " LEFT JOIN user_permission u2 ON u2.collection_id = c.collection_id AND u2.user_id = :userId  WHERE u1.permission_id = 4 ";
        }
        if ($search) {
            $sql .= (Auth::isUserAdmin() ? ' WHERE ' : ' AND ');
            $sql .= " CONCAT(IFNULL(p.project_id,''), IFNULL(p.name,''), IFNULL(u.name,''), IFNULL(p.url,''), IFNULL(p.creation_date,'')) LIKE '%$search%' ";
        }
        $sql .= " GROUP BY p.project_id ";
        $this->database->prepareQuery($sql);
        $count = count($this->database->executeSelect([':userId' => Auth::getUserID()]));
        return $count;
    }

    public function getListByPage(string $start = '0', string $length = '8', string $search = null, string $column = '0', string $dir = 'asc'): array
    {
        $arr = [];
        if (Auth::isUserAdmin()) {
            $sql = "SELECT p.*,u.name AS username,MAX(c.collection_id) AS collection_id,MAX( u1.permission_id ) AS permission_id FROM project p LEFT JOIN user u ON p.creator_id = u.user_id LEFT JOIN collection c ON p.project_id = c.project_id LEFT JOIN user_permission u1 ON u1.collection_id = c.collection_id AND u1.user_id = :userId ";
        } else {
            $sql = "SELECT p.*,u.name AS username,MAX(c.collection_id) AS collection_id,MAX( u2.permission_id ) AS permission_id FROM project p LEFT JOIN user u ON p.creator_id = u.user_id LEFT JOIN collection c ON p.project_id = c.project_id LEFT JOIN user_permission u1 ON u1.collection_id = c.collection_id AND u1.user_id = " . Auth::getUserID() . " LEFT JOIN user_permission u2 ON u2.collection_id = c.collection_id AND u2.user_id = :userId  WHERE u1.permission_id = 4 ";
        }
        if ($search) {
            $sql .= Auth::isUserAdmin() ? ' WHERE ' : ' AND ';
            $sql .= " CONCAT(IFNULL(p.project_id,''), IFNULL(p.name,''), IFNULL(u.name,''), IFNULL(p.url,''), IFNULL(p.creation_date,'')) LIKE '%$search%' ";
        }
        $sql .= " GROUP BY p.project_id ";
        $a = ['', 'p.project_id', 'p.name', 'u.name', 'p.url', '', 'p.creation_date', 'p.active'];
        $sql .= " ORDER BY $a[$column] $dir LIMIT $length OFFSET $start";
        $this->database->prepareQuery($sql);
        $result = $this->database->executeSelect([':userId' => Auth::getUserID()]);
        if (count($result)) {
            foreach ($result as $key => $value) {
                $arr[$key][] = "<input type='checkbox' class='js-checkbox'data-id='$value[project_id]' name='cb[$value[project_id]]' id='cb[$value[project_id]]'>";
                $arr[$key][] = "$value[project_id]<input id='project$value[project_id]' type='hidden' name='projectId' value='$value[project_id]'>";
                $arr[$key][] = "<input type='text' class='form-control form-control-sm' id='$value[project_id]' name='name' style='width:200px;' value='$value[name]'><small id='projectValid$value[project_id]' class='text-danger'></small>";
                $arr[$key][] = $value['username'];
                $arr[$key][] = "<input type='text' class='form-control form-control-sm' name='url' style='width:400px;' value='$value[url]'>";
                $arr[$key][] = "<input type='file' name='picture_id_file' id='picture_id_file$value[project_id]' class='picture_id_file file_upload' accept='image/*' data-project-id='$value[project_id]' hidden><a href='#' class='project-picture' data-project-id='$value[project_id]'><img id='pic$value[project_id]' src='" . APP_URL . "/sounds/projects/$value[picture_id]' alt='Upload Picture' style='height:30px;'></a>";
                $arr[$key][] = $value['creation_date'];
                $arr[$key][] = "<input name='active' type='checkbox' " . ($value['active'] ? 'checked' : '') . ">";
            }
        }
        return $arr;
    }
}
