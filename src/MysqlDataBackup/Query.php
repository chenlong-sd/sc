<?php
/**
 * datetime: 2023/6/14 0:00
 **/

namespace Sc\Util\MysqlDataBackup;

class Query
{
    /**
     * 获取所有表
     *
     * @param Connect $connect
     *
     * @return bool|array
     * @date 2023/6/14
     */
    public static function getTables(Connect $connect): bool|array
    {
        $sql = 'SELECT TABLE_COMMENT as `comment`,TABLE_NAME as `table` FROM INFORMATION_SCHEMA.TABLES  WHERE TABLE_SCHEMA = :schemas';

        $statement = $connect->getPDO()->prepare($sql);
        $statement->execute(['schemas' => $connect->database]);

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * 获取创建表语句
     *
     * @param Connect $connect
     * @param string  $table
     *
     * @return mixed
     */
    public static function getCreateTable(Connect $connect, string $table): mixed
    {
        $sql = "SHOW CREATE TABLE `$table`";

        $statement = $connect->getPDO()->prepare($sql);
        $statement->execute();

        $result = $statement->fetch(\PDO::FETCH_ASSOC);

        return $result['Create Table'];
    }

    /**
     * @param Connect $connect
     * @param string  $table
     *
     * @return array
     */
    public static function getTableInfo(Connect $connect, string $table): array
    {
        $sql = "DESCRIBE `$table`";

        $statement = $connect->getPDO()->prepare($sql);
        $statement->execute();

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param Connect $connect
     * @param string  $table
     * @param string  $primary
     * @param int     $maxId
     * @param int     $limit
     *
     * @return bool|array
     */
    public static function getData(Connect $connect, string $table, string $primary, int $maxId, int $limit): bool|array
    {
        $sql = "SELECT * FROM `$table` where {$primary} > :id limit {$limit}";
        $statement = $connect->getPDO()->prepare($sql);
        $statement->execute(['id' => $maxId]);

        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * 获取数据库版本
     *
     * @param Connect $connect
     *
     * @return mixed
     * @date 2023/6/15
     */
    public static function getVersion(Connect $connect): mixed
    {
        $sql = "SELECT VERSION() as version;";

        return $connect->getPDO()->query($sql)->fetch(\PDO::FETCH_ASSOC)['version'] ?? '';
    }

    /**
     * 获取编码
     *
     * @param Connect $connect
     *
     * @return mixed
     * @date 2023/6/15
     */
    public static function getCharset(Connect $connect): mixed
    {
        $sql = "SHOW VARIABLES LIKE 'character_set_database';";

        return $connect->getPDO()->query($sql)->fetch(\PDO::FETCH_ASSOC)['Value'] ?? 'utf8mb4';
    }
}