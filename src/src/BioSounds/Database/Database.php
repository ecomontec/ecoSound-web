<?php

namespace BioSounds\Database;

use Exception;
use PDO;
use PDOStatement;

class Database
{
    const CONNECTION_STRING = '%s:host=%s;dbname=%s;port=3306';
    /**
     * @var PDO
     */
    private $connection;

    /**
     * @var PDOStatement
     */
    private $stmt;

    /**
     * Database constructor.
     * @param string $driver
     * @param string $host
     * @param string $database
     * @param string $user
     * @param string $password
     */
    public function __construct(string $driver, string $host, string $database, string $user, string $password)
    {
        $this->connection = new PDO(
            sprintf(self::CONNECTION_STRING, $driver, $host, $database),
            $user,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
    }

    /**
     * @param string $query
     * @throws Exception
     */
    public function prepareQuery(string $query)
    {
        $this->stmt = $this->connection->prepare($query);
    }

    /**
     * @param array|null $values
     * @return array|int|string
     */
    public function executeSelect(array $values = null)
    {
        return $this->executeQuery($values, 1);
    }

    /**
     * @param array|null $values
     * @return array|int
     * @throws Exception
     */
    public function executeInsert(array $values = null)
    {
        return $this->executeQuery($values, 2);
    }

    /**
     * @param array|null $values
     * @return array|int
     * @throws Exception
     */
    public function executeUpdate(array $values = null)
    {
        return $this->executeQuery($values, 3);
    }

    /**
     * @param array|null $values
     * @return array|int
     * @throws Exception
     */
    public function executeDelete(array $values = null)
    {
        return $this->executeQuery($values, 4);
    }

    /**
     * @param array $values
     * @param int $queryType
     * @return array|int|string
     */
    private function executeQuery(array $values = null, int $queryType)
    {
        $this->stmt->execute($values);

        switch ($queryType) {
            case 1:
                return $this->stmt->fetchAll();
            case 2:
                return $this->connection->lastInsertId();
            case 3:
                return $this->stmt->rowCount();
            case 4:
                return $this->stmt->rowCount();
        }
    }
}
