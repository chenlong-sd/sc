<?php
/**
 * datetime: 2023/6/13 23:42
 **/

namespace Sc\Util\MysqlDataBackup;

class Backup
{
    /**
     * BackUp constructor.
     *
     * @param Connect $connect  数据库连接信息
     * @param string  $saveDir  备份文件保存位置
     * @param string  $verifyCode  重要操作验证码，验证码为10位字母数字组合，为验证码的md5值
     *                             例：md5('AbcdefGH10'), 此为示例，生产应为md5结果值
     *
     * @author chenlong<vip_chenlong@163.com>
     * @date   2023/6/17
     */
    public function __construct(private readonly Connect $connect, private readonly string $saveDir, private readonly string $verifyCode) { }


    public function render(array $postParam): array|string
    {
        // 默认返回html页面
        if (!$postParam) {
            return $this->page();
        }

        // 备份
        if (!empty($postParam['start'])) {
            try {
                $this->backUp($postParam['tables']);
            } catch (\Exception $exception) {
                return ['code' => 202, 'msg' => $exception->getMessage()];
            }
            return ['code' => 200];
        }

        // 取消备份
        if (!empty($postParam['cancel'])) {
            ExecutionProgress::end();
            return ['code' => 200];
        }

        // 删除备份文件
        if (!empty($postParam['remove'])) {
            return $this->remove($postParam);
        }

        // 获取备份文件
        if (!empty($postParam['getRecover'])) {
            return (new Recover($this->connect))->getRecoverFile($this->saveDir);
        }

        // 执行恢复
        if (!empty($postParam['recover'])) {
            return $this->recover($postParam);
        }

        // 获取备份日志
        return ExecutionProgress::getProgress($postParam['seek'] ?? 0);
    }


    private function page(): string
    {
        return (new Page($this->connect, $this->saveDir))->render();
    }

    /**
     * @param array $tables
     *
     * @return void
     * @throws \Exception
     */
    private function backUp(array $tables): void
    {
        $startTime = microtime(true);
        try {
            ExecutionProgress::writeProgress('back_up', $fd);

            $allTables = Query::getTables($this->connect);
            $allTable  = array_column($allTables, 'comment', 'table');
            $sqlWrite  = new SqlWrite($this->saveDir);

            ExecutionProgress::writeProgress('开始备份...', $fd);

            $backUpTableTip = count($tables) === count($allTable)
                ? [" * 全部备份"]
                : array_map(fn($v) => " * {$v['table']} {$v['comment']}", array_filter($allTables, fn($v) => in_array($v['table'], $tables)));

            $sqlWrite->write(implode(PHP_EOL, [
                '/**',
                ' * 备份时间：' . date('Y-m-d H:i:s'),
                ' * 操作用户：' . 'admin',
                ' * 数据库：' . $this->connect->database,
                ' * 地址：' . $this->connect->host . ':' . $this->connect->port,
                ' * Mysql版本：' . Query::getVersion($this->connect),
                ' * 备份表：',
                ...$backUpTableTip,
                ' */'
            ]));

            $sqlWrite->write(sprintf("SET NAMES %s;", Query::getCharset($this->connect)));
            $sqlWrite->write("-- 备份表结构");
            ExecutionProgress::writeProgress("-- 备份表结构", $fd);

            foreach ($tables as $table) {
                $createTable = Query::getCreateTable($this->connect, $table);
                $sqlWrite->write("-- $table $allTable[$table]");
                $sqlWrite->write("DROP TABLE IF EXISTS `$table`;");
                $sqlWrite->write($createTable . ';');
                ExecutionProgress::writeProgress("$table $allTable[$table] 表结构备份完成。", $fd);
            }

            $sqlWrite->write("-- 表结构备份完成");
            $sqlWrite->write("--");
            $sqlWrite->write("--");

            $sqlWrite->write("-- 备份表数据");
            ExecutionProgress::writeProgress("-- 备份表数据", $fd);

            $baseSql = "INSERT INTO `%s` VALUES ";

            foreach ($tables as $table) {
                $maxId     = 0;  // 以获取的数据最大ID值
                $rows      = 0;  // 以获取数据行数
                $limit     = 5000; // 每次获取数据行数
                // 获取表字段信息
                $tableInfo = Query::getTableInfo($this->connect, $table,);
                $primary   = current(array_filter($tableInfo, fn($v) => $v['Key'] === 'PRI'))['Field'] ?? '';
                if (!$primary) {
                    ExecutionProgress::writeProgress(sprintf('<span style="color: red;font-weight: bold">Warning: %s 无主键信息，跳过数据备份</span>', $table), $fd);
                    continue;
                }
                $fields    = array_column($tableInfo, 'Field');
                $sql       = sprintf($baseSql, $table);

                ExecutionProgress::writeProgress("$table $allTable[$table] 表数据开始备份", $fd);

                while (true) {
                    if (!$data = Query::getData($this->connect, $table, $primary, $maxId, $limit)){
                        ExecutionProgress::writeProgress("$table $allTable[$table] 表数据备份完成", $fd);
                        break;
                    }

                    $sqlParams = [];
                    $index     = 0;

                    // 组建数据
                    foreach ($data as $index => $datum) {
                        $values = array_map(function ($field) use ($datum) {
                            if ($datum[$field] === null) {
                                return 'null';
                            }
                            return is_string($datum[$field]) ? '"' . addslashes($datum[$field]) . '"' : $datum[$field];
                        }, $fields);
                        $sqlParams[] = '(' . implode(',', $values) . ')';
                    }

                    // 记录行数与最大ID值
                    $rows += $index + 1;
                    $maxId = $data[$index][$primary];

                    $sqlWrite->write($sql . implode(',', $sqlParams) . ';');

                    ExecutionProgress::writeProgress(sprintf("%s 已备份数据行数: %d, [已耗时：%.4f]",  $table, $rows, microtime(true) - $startTime), $fd);

                    // 如果获取行数小于要获取的行数，则判定数据已经取完，终止循环
                    if ($index + 1 < $limit) {
                        ExecutionProgress::writeProgress("$table $allTable[$table] 表数据备份完成", $fd);
                        break;
                    }
                }
            }
        }catch (\Throwable $exception){
            // 系统异常，把异常信息写入日志
            if ($exception->getCode() === 0) {
                ExecutionProgress::writeProgress("ERROR: " . $exception->getMessage(), $fd);
            }
            var_dump($exception->getMessage());
            var_dump($exception->getTraceAsString());
            empty($sqlWrite) or $sqlWrite->cancel();
            throw $exception;
        } finally {
            // 结束执行， 写入结束标志日志 END
            ExecutionProgress::writeProgress('备份结束', $fd);
            ExecutionProgress::writeProgress('总耗时：' . (microtime(true) - $startTime), $fd);
            ExecutionProgress::writeProgress('END', $fd);
        }
    }

    /**
     * 删除备份文件
     *
     * @param array $postParam
     *
     * @return array
     */
    private function remove(array $postParam): array
    {
        if (empty($postParam['code']) || !preg_match('/^\w{10}$/', $postParam['code']) || md5($postParam['code']) !== $this->verifyCode) {
            return ['code' => 202, 'msg' => '验证码错误'];
        }

        @unlink($this->saveDir . DIRECTORY_SEPARATOR . $postParam['filename']);

        return ['code' => 200];
    }

    /**
     * 恢复数据
     *
     * @param array $postParam
     *
     * @return array
     */
    private function recover(array $postParam): array
    {
        if (empty($postParam['code']) || !preg_match('/^\w{10}$/', $postParam['code']) || md5($postParam['code']) !== $this->verifyCode) {
            return ['code' => 202, 'msg' => '验证码错误'];
        }

        return (new Recover($this->connect, $this->saveDir . DIRECTORY_SEPARATOR . $postParam['filename']))->recover();
    }
}