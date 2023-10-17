<?php
/**
 * datetime: 2023/6/13 23:55
 **/

namespace Sc\Util\MysqlDataBackup;

class Connect
{
    private ?\PDO $PDO = null;

    public function __construct(
        public readonly string $database,
        public readonly string $host = '127.0.0.1',
        public readonly string $username = 'root',
        public readonly string $password = 'root',
        public readonly int    $port = 3306
    )
    {

    }

    /**
     * @return \PDO
     */
    public function getPDO(): \PDO
    {
        if ($this->PDO === null) {
            $dsn = 'mysql:host=%s;dbname=%s;port=%d;charset=utf8mb4';
            $dsn = sprintf($dsn, $this->host, $this->database, $this->port);
            $this->PDO = new \PDO($dsn, $this->username, $this->password);
        }

        return $this->PDO;
    }
}