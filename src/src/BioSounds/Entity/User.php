<?php

namespace BioSounds\Entity;

use BioSounds\Utils\Auth;

class User extends AbstractProvider
{
    const TABLE_NAME = "user";
    const ID = "user_id";
    const NAME = "username";
    const FULL_NAME = "name";
    const PASSWORD = "password";
    const TAG_COLOR = "color";
    const DEFAULT_TAG_COLOR = '#FFFFFF';

    /**
     * @return int
     */
    public function getFft(): int
    {
        return $this->fft;
    }

    /**
     * @param int $fft
     * @return User
     */
    public function setFft(int $fft): User
    {
        $this->fft = $fft;
        return $this;
    }

    /**
     * @param string $user_id
     * @return string|null
     * @throws \Exception
     */
    public function getFftValue(string $user_id = null): ?string
    {
        if (empty($user_id)) {
            return null;
        }

        $this->database->prepareQuery('SELECT fft FROM user WHERE user_id = :user_id');
        if (empty($result = $this->database->executeSelect([":user_id" => $user_id]))) {
            return null;
        }

        return $result[0]["fft"];
    }

    /**
     * @param string $username
     * @return string|null
     * @throws \Exception
     */
    public function getPassword(string $username): ?string
    {
        if (empty($username)) {
            return null;
        }

        $this->database->prepareQuery('SELECT password FROM user WHERE username = :username');
        if (empty($result = $this->database->executeSelect([":username" => $username]))) {
            return null;
        }

        return $result[0]["password"];
    }

    /**
     * @param int $userId
     * @return string|null
     * @throws \Exception
     */
    public function getPasswordByUserId(int $userId): ?string
    {
        if (empty($userId)) {
            return null;
        }

        $this->database->prepareQuery('SELECT password FROM user WHERE user_id = :userId');
        if (empty($result = $this->database->executeSelect([':userId' => $userId]))) {
            return null;
        }

        return $result[0][self::PASSWORD];
    }

    /**
     * @param string $username
     * @return int|null
     * @throws \Exception
     */
    public function getUserId(string $username): ?int
    {
        $this->database->prepareQuery('SELECT user_id FROM user WHERE username = :username');
        if (empty($result = $this->database->executeSelect([":username" => $username]))) {
            return null;
        }

        return $result[0]['user_id'];
    }

    /**
     * @param int $userId
     * @return string|null
     * @throws \Exception
     */
    public function getUserName(int $userId): ?string
    {
        $this->database->prepareQuery('SELECT username FROM user WHERE user_id = :userId');
        if (empty($result = $this->database->executeSelect([":userId" => $userId]))) {
            return null;
        }

        return $result[0][self::NAME];
    }

    /**
     * @param int $userId
     * @return string|null
     * @throws \Exception
     */
    public function getOrcid(int $userId): ?string
    {
        $this->database->prepareQuery('SELECT orcid FROM user WHERE user_id = :userId');
        if (empty($result = $this->database->executeSelect([":userId" => $userId]))) {
            return null;
        }
        return $result[0]['orcid'];
    }

    public function getEmail(int $userId): ?string
    {
        $this->database->prepareQuery('SELECT email FROM user WHERE user_id = :userId');
        if (empty($result = $this->database->executeSelect([":userId" => $userId]))) {
            return null;
        }
        return $result[0]['email'];
    }

    /**
     * @param int $userId
     * @return string|null
     * @throws \Exception
     */
    public function getFullName(int $userId): ?string
    {
        $this->database->prepareQuery('SELECT name FROM user WHERE user_id = :userId');
        if (empty($result = $this->database->executeSelect([":userId" => $userId]))) {
            return null;
        }

        return $result[0][self::FULL_NAME];
    }

    /**
     * @param int $userId
     * @return string|null
     * @throws \Exception
     */
    public function getTagColor(int $userId): ?string
    {
        $this->database->prepareQuery('SELECT color FROM user WHERE user_id = :userId');
        if (empty($result = $this->database->executeSelect([":userId" => $userId]))) {
            return null;
        }

        return $result[0][self::TAG_COLOR];
    }

    /**
     * @param int $userId
     * @return bool
     * @throws \Exception
     */
    public function isUserActive(int $userId): bool
    {
        $this->database->prepareQuery('SELECT active FROM user WHERE user_id = :userId');
        if (empty($result = $this->database->executeSelect([":userId" => $userId]))) {
            return false;
        }

        return $result[0]["active"] == 1 ? true : false;
    }

    public function getUserCount($collection_id)
    {
        $collection_id = $collection_id ? $collection_id : 0;
        $this->database->prepareQuery("SELECT COUNT(user_id) AS count FROM (SELECT user_id FROM user_permission WHERE collection_id IN ( $collection_id ) UNION All SELECT user_id FROM `user` WHERE role_id = 1)c GROUP BY user_id");
        if (empty($result = $this->database->executeSelect())) {
            return null;
        }
        return count($result);
    }

    /**
     * @param int $userId
     * @return bool
     * @throws \Exception
     */
    public function isUserAdmin(int $userId): bool
    {
        $this->database->prepareQuery(
            'SELECT STRCMP(role.name, :roleName) AS result FROM user ' .
            'LEFT JOIN role ON user.role_id = role.role_id WHERE user_id = :userId'
        );

        if (empty($result = $this->database->executeSelect([":userId" => $userId, ":roleName" => Role::ADMIN_ROLE]))) {
            throw new \Exception("User $userId doesn't exist.");
        }
        return ($result[0]["result"] == 0 ? true : false);
    }

    /**
     * @param int $userId
     * @return bool
     * @throws \Exception
     */
    public function isManage(int $userId): bool
    {
        $this->database->prepareQuery(
            'SELECT count(*) AS result FROM user u LEFT JOIN user_permission p ON u.user_id = p.user_id WHERE p.user_id = :userId AND permission_id = 4'
        );

        if (empty($result = $this->database->executeSelect([":userId" => $userId]))) {
            throw new \Exception("User $userId doesn't exist.");
        }
        return ($result[0]["result"] ? true : false);
    }

    /**
     * @param int $userId
     * @return int
     * @throws \Exception
     */
    public function getPermission(int $userId): int
    {
        $this->database->prepareQuery(
            'SELECT MAX(c.) AS result FROM user ' .
            'LEFT JOIN role ON user.role_id = role.role_id WHERE user_id = :userId'
        );

        if (empty($result = $this->database->executeSelect([":userId" => $userId, ":roleName" => Role::ADMIN_ROLE]))) {
            throw new \Exception("User $userId doesn't exist.");
        }
        return ($result[0]["result"] == 0 ? true : false);
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getActiveUsers(): array
    {
        $this->database->prepareQuery('SELECT * FROM user WHERE active = \'1\' ORDER BY active, username');
        return $this->database->executeSelect();
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getList(): array
    {
        if (Auth::isUserAdmin()) {
            $sql = 'SELECT * FROM user ORDER BY `name`';
        } else {
            $user_id = Auth::getUserID();
            $sql = "SELECT u.* FROM user u LEFT JOIN user_permission p ON u.user_id = p.user_id WHERE p.collection_id IN (SELECT collection_id FROM user_permission WHERE user_id = $user_id AND permission_id = 4) GROUP BY u.user_id ORDER BY u.name";
        }
        $this->database->prepareQuery($sql);
        return $this->database->executeSelect();
    }

    public function getManageList(): array
    {
        $user_id = Auth::getUserID();
        $sql = "SELECT collection_id FROM user_permission WHERE user_id = $user_id AND permission_id = 4";
        $this->database->prepareQuery($sql);
        return $this->database->executeSelect();
    }

    public function getName(): array
    {
        $this->database->prepareQuery('SELECT user_id,
       `name` FROM user ORDER BY `name`');
        return $this->database->executeSelect();
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getUser(): array
    {
        $this->database->prepareQuery('SELECT * FROM user WHERE role_id = 2');
        return $this->database->executeSelect();
    }

    public function getAll(): array
    {
        $sql = 'SELECT * FROM user where active = 1';
        $this->database->prepareQuery($sql);
        return $this->database->executeSelect();
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getMyProfile(int $userId): array
    {
        $this->database->prepareQuery('SELECT * FROM user WHERE user_id = :userId');
        $result = $this->database->executeSelect([":userId" => $userId]);
        return $result[0];
    }

    /**
     * @param int $userId
     * @return int
     * @throws \Exception
     */
    public function countOtherAdminUsers(int $userId): int
    {
        $this->database->prepareQuery('SELECT COUNT(*) AS result FROM user WHERE role_id = :adminRoleId AND user_id <> :userId');
        if (empty($result = $this->database->executeSelect([":userId" => $userId, ":adminRoleId" => Role::ADMIN_ID]))) {
            return 0;
        }

        return $result[0]["result"];
    }

    /**
     * @param array $userData
     * @return int
     * @throws \Exception
     */
    public function insertUser(array $userData): int
    {
        if (empty($userData)) {
            return false;
        }

        $fields = "( ";
        $valuesNames = "( ";
        $values = array();

        foreach ($userData as $key => $value) {
            $fields .= $key;
            $valuesNames .= ":" . $key;
            $values[":" . $key] = $value;
            $fields .= ",";
            $valuesNames .= ",";
        }
        $fields = substr($fields, 0, strlen($fields) - 1) . ' )';
        $valuesNames = substr($valuesNames, 0, strlen($valuesNames) - 1) . ' )';

        $this->database->prepareQuery("INSERT INTO user $fields VALUES $valuesNames");
        return $this->database->executeInsert($values);
    }

    /**
     * @param array $userData
     * @return bool
     * @throws \Exception
     */
    public function updateUser(array $userData): bool
    {
        if (empty($userData)) {
            return false;
        }

        $userId = $userData["itemID"];
        unset($userData["itemID"]);
        $fields = '';
        $values = [];

        foreach ($userData as $key => $value) {
            $fields .= $key . ' = :' . $key;
            $values[':' . $key] = $value;
            $fields .= ",";
        }
        $fields = substr($fields, 0, strlen($fields) - 1);
        $values[':userId'] = $userId;
        $this->database->prepareQuery("UPDATE user SET $fields WHERE user_id = :userId");
        return $this->database->executeUpdate($values);
    }


    /**
     * @param array $userData
     * @return bool
     * @throws \Exception
     */
    public function resetPasswd(int $userId, string $newPasswd): bool
    {
        $values[':userId'] = $userId;
        $values[':nePasswd'] = $newPasswd;

        $this->database->prepareQuery("UPDATE user SET password = :nePasswd WHERE user_id = :userId");
        return $this->database->executeUpdate($values);
    }

    public function isValid($str)
    {
        $sql = "SELECT * FROM user WHERE `username` = '$str'";
        $this->database->prepareQuery($sql);
        $result = $this->database->executeSelect();
        if (count($result) > 0) {
            return true;
        }
        return false;
    }

    public function getListByPage(string $start = '0', string $length = '8', string $search = null, string $column = '0', string $dir = 'asc'): array
    {
        $arr = [];
        $sql = "SELECT * FROM user ";
        $is_admin = Auth::isUserAdmin();
        if ($search) {
            $sql .= " WHERE CONCAT(IFNULL(user_id,''), IFNULL(name,''), IFNULL(username,''), IFNULL(orcid,''), IFNULL(email,'')) LIKE '%$search%' ";
        }
        if ($is_admin) {
            $a = ['', 'user_id', 'name', 'username', 'orcid', 'email', 'role_id', 'active', 'color'];
        } else {
            $a = ['', 'user_id', 'name', 'username', 'orcid', 'email', 'color'];
        }
        $sql .= " ORDER BY $a[$column] $dir LIMIT $length OFFSET $start";
        $this->database->prepareQuery($sql);
        $result = $this->database->executeSelect();

        $roles = (new Role())->getRoles();
        if (count($result)) {
            foreach ($result as $key => $value) {
                if ($is_admin || $value['role_id'] == 2) {
                    $arr[$key][] = "<input type='checkbox' class='js-checkbox'data-id='$value[user_id]' name='cb[$value[user_id]]' id='cb[$value[user_id]]'>";
                } else {
                    $arr[$key][] = "";
                }
                $arr[$key][] = "$value[user_id]<input type='hidden' class='form-control form-control-sm' name='itemID' value='$value[user_id]'>";
                $arr[$key][] = "<input type='text' class='form-control form-control-sm' name='name' value='$value[name]' " . ((!$is_admin && $value['role_id'] == 1) ? 'disabled' : '') . ">";
                $arr[$key][] = $value['username'];
                $arr[$key][] = "<input type='text' class='form-control form-control-sm' name='orcid' value='$value[orcid]' maxlength='19' pattern='^(\d{4}-\d{4}-\d{4}-\d{4})$' " . ((!$is_admin && $value['role_id'] == 1) ? 'disabled' : '') . "><div class='invalid-feedback'>Please provide a valid ORCID.</div>";
                $arr[$key][] = "<input type='text' class='form-control form-control-sm' name='email' value='$value[email]' " . ((!$is_admin && $value['role_id'] == 1) ? 'disabled' : '') . ">";
                if ($is_admin) {
                    $str = '';
                    foreach ($roles as $role) {
                        $str = $str . "<option value='$role[role_id]' " . ($role['role_id'] == $value['role_id'] ? "selected" : '') . ">$role[name]</option>";
                    }
                    $arr[$key][] = "<select name='role_id' class='form-control form-control-sm'>$str</select>";
                    $arr[$key][] = "<input name='active' type='checkbox' " . ($value['active'] ? 'checked' : '') . ">";
                }
                $arr[$key][] = "<input type='color' name='color' alt='Tag color' value='" . (isset($value['color']) ? $value['color'] : self::DEFAULT_TAG_COLOR) . "' " . ((!$is_admin && $value['role_id'] == 1) ? 'disabled' : '') . ">";
            }
        }
        return $arr;
    }

    public function getFilterCount(string $search): int
    {
        $sql = "SELECT COUNT(*) FROM user ";
        if ($search) {
            $sql .= " WHERE CONCAT(IFNULL(user_id,''), IFNULL(name,''), IFNULL(username,''), IFNULL(orcid,''), IFNULL(email,'')) LIKE '%$search%' ";
        }
        $this->database->prepareQuery($sql);
        $count = $this->database->executeSelect();
        return $count[0]['COUNT(*)'];
    }
}
