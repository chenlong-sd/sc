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
     * @param bool $isCreateDatabase
     *
     * @return \PDO
     * @throws \Exception
     */
    public function getPDO(bool $isCreateDatabase = false): \PDO
    {
        if ($this->PDO === null) {
            $dsn = 'mysql:host=%s;dbname=%s;port=%d;charset=utf8mb4';
            $dsn = sprintf($dsn, $this->host, $this->database, $this->port);
            if ($isCreateDatabase){
                $this->createDatabase();
            }
            $this->PDO = new \PDO($dsn, $this->username, $this->password);
        }

        return $this->PDO;
    }

    /**
     * 创建失败
     *
     * @return void
     * @throws \Exception
     */
    private function createDatabase(): void
    {
        $dsn = 'mysql:host=%s;port=%d;charset=utf8mb4';
        $dsn = sprintf($dsn, $this->host, $this->port);

        $PDO = new \PDO($dsn, $this->username, $this->password);

        $PDOStatement = $PDO->prepare("CREATE DATABASE IF NOT EXISTS `$this->database`");

        if (!$PDOStatement->execute()) {
            throw new \Exception("数据库创建失败");
        }

        $PDO->prepare("ALTER DATABASE $this->database DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;")->execute();
    }
}