<?php

namespace BioSounds\Entity;

use BioSounds\Provider\BaseProvider;

class User extends BaseProvider
{
    const TABLE_NAME = "user";
    const ID = "user_id";
    const NAME = "username";
    const FULL_NAME = "name";
    const PASSWORD = "password";
    const TAG_COLOR = "color";

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
        $this->database->prepareQuery('SELECT * FROM user ORDER BY `name`');
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
     * @return bool
     * @throws \Exception
     */
    public function insertUser(array $userData): bool
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

    /**
     * @param array $names
     * @return array|int
     * @throws \Exception
     */
    public function getInputList(array $names)
    {
        $query = 'SELECT * FROM ' . self::TABLE_NAME;

        $fields = [];
        if (isset($names)) {
            if (count($names) == 1) {
                $query .= ' WHERE name LIKE :name ';
                $fields = [':name' => "%$names[0]%"];
            } else {
                $query .= 'WHERE (name LIKE :name1 AND name LIKE :name2) ';
                $fields = [
                    ':name1' => "%$names[0]%",
                    ':name2' => "%$names[1]%"
                ];
            }
        }
        $query .= 'ORDER BY name ASC LIMIT 0,15';

        $this->database->prepareQuery($query);
        $result = $this->database->executeSelect($fields);

        return $result;
    }
}
