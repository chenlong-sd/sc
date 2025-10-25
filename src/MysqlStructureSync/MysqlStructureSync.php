<?php

namespace Sc\Util\MysqlStructureSync;

class MysqlStructureSync
{
    private array $args;

    /**
     * @param string $function page or sync
     * @param array $args
     * function => detect: [code_paths = [...], current_structures = [table_name => sql, ....], table_prefix => '']
     * @return mixed
     */
    public static function main(string $function = 'page', array $args = []): mixed
    {
        return call_user_func([new self($args), $function]);
    }

    public function __construct(array $args)
    {
        $this->args = $args;
    }

    public function page()
    {
        return include __DIR__ . '/page.php';
    }

    public function detect(): array
    {
        $codeStructures = $this->args['code_structures'];
        $dbStructures = $this->args['current_structures'];
        $res = [];

        $sqlTableComparator = new SqlTableComparator(true);
        foreach ($codeStructures as $tableName => $structure) {
            $dbStructure = $dbStructures[$tableName] ?? null;
            if ($dbStructure) {
                // 对比两个建表sql结构，找出差异
                $code = $sqlTableComparator->parseCreateTableSql($structure);
                $db = $sqlTableComparator->parseCreateTableSql($dbStructure);
                $diff = $sqlTableComparator->compareTables($db, $code);
                $res[] = [
                    'des' => $sqlTableComparator->formatDifferences($diff),
                    'table_name' => $tableName,
                    'sql' => $sqlTableComparator->generateAlterSql($diff, $tableName, ),
                    'code' => $structure,
                    'db' => $dbStructure,
                ];
            }else{
                $res[] = [
                    'des' => '新增表',
                    'table_name' => $tableName,
                    'sql' => $structure,
                    'code' => $structure,
                    'db' => null,
                ];
            }
        }

        return $res;
    }
}