<?php

namespace Sc\Util\MysqlStructureSync;

/**
 * 对比两个建表SQL的结构差异
 */
class SqlTableComparator {
    private $ignoreCharset; // 是否忽略字符集对比

    public function __construct($ignoreCharset = false) {
        $this->ignoreCharset = $ignoreCharset;
    }

    /**
     * 解析建表SQL，提取表结构信息
     * @param string $sql 建表SQL语句
     * @return array 解析后的表结构信息
     */
    public function parseCreateTableSql($sql) {
        $tableInfo = [
            'table_name' => '',
            'columns' => [], // 字段信息，键为字段名
            'primary_key' => [], // 主键字段
            'unique_keys' => [], // 唯一键
            'foreign_keys' => [], // 外键
            'charset' => '', // 表字符集
            'collation' => '' // 表校对规则
        ];

        // 提取表名
        if (preg_match('/CREATE\s+TABLE\s+`?([^`\s]+)`?/i', $sql, $matches)) {
            $tableInfo['table_name'] = $matches[1];
        }

        // 提取表字符集和校对规则
        if (!$this->ignoreCharset) {
            if (preg_match('/CHARSET\s*=\s*([^\s,]+)/i', $sql, $matches)) {
                $tableInfo['charset'] = $matches[1];
            }
            if (preg_match('/COLLATE\s*=\s*([^\s,]+)/i', $sql, $matches)) {
                $tableInfo['collation'] = $matches[1];
            }
        }

        // 提取字段定义部分（去掉首尾括号）
        if (preg_match('/\((.*)\)/s', $sql, $matches)) {
            $content = $matches[1];
            // 分割字段定义（处理逗号分隔，排除括号内的逗号）
            $tokens = preg_split('/,(?![^()]*\))/', $content);

            foreach ($tokens as $token) {
                $token = trim($token);
                if (empty($token)) continue;

                // 处理字段定义
                if (preg_match('/^`?([^`\s]+)`?\s+(.+?)(?=\s+(PRIMARY KEY|UNIQUE|NOT NULL|DEFAULT|COMMENT|REFERENCES|CHARACTER SET|COLLATE|,|$))/i', $token, $colMatches)) {
                    $colName = $colMatches[1];
                    $dataType = trim($colMatches[2]);

                    // 处理字段字符集（如果不忽略）
                    if (!$this->ignoreCharset) {
                        if (preg_match('/CHARACTER SET\s+([^\s,]+)/i', $token, $csMatches)) {
                            $dataType .= ' CHARACTER SET ' . $csMatches[1];
                        }
                        if (preg_match('/COLLATE\s+([^\s,]+)/i', $token, $clMatches)) {
                            $dataType .= ' COLLATE ' . $clMatches[1];
                        }
                    }

                    $column = [
                        'name' => $colName,
                        'type' => $dataType,
                        'nullable' => true,
                        'default' => null,
                        'comment' => ''
                    ];

                    // 检查是否为NOT NULL
                    if (stripos($token, 'NOT NULL') !== false) {
                        $column['nullable'] = false;
                    }

                    // 提取默认值
                    if (preg_match('/DEFAULT\s+(.+?)(?=\s+(NOT NULL|COMMENT|CHARACTER SET|COLLATE|,|$))/i', $token, $defMatches)) {
                        $column['default'] = trim($defMatches[1]);
                    }

                    // 提取注释
                    if (preg_match('/COMMENT\s+[\'\"](.*?)[\'\"]/i', $token, $comMatches)) {
                        $column['comment'] = $comMatches[1];
                    }

                    $tableInfo['columns'][$colName] = $column;
                }

                // 处理主键
                if (stripos($token, 'PRIMARY KEY') !== false) {
                    if (preg_match('/PRIMARY KEY\s*\(`?([^`\)]+)`?\)/i', $token, $pkMatches)) {
                        $tableInfo['primary_key'] = explode('`,`', trim($pkMatches[1], '`'));
                    }
                }

                // 处理唯一键
                if (stripos($token, 'UNIQUE') !== false) {
                    if (preg_match('/UNIQUE\s+(KEY|INDEX)\s+`?([^`\s]+)`?\s*\(`?([^`\)]+)`?\)/i', $token, $ukMatches)) {
                        $keyName = $ukMatches[2];
                        $columns = explode('`,`', trim($ukMatches[3], '`'));
                        $tableInfo['unique_keys'][$keyName] = $columns;
                    }
                }

                // 处理外键
                if (stripos($token, 'FOREIGN KEY') !== false) {
                    if (preg_match('/FOREIGN KEY\s*\(`?([^`\)]+)`?\)\s+REFERENCES\s+`?([^`\s]+)`?\s*\(`?([^`\)]+)`?\)/i', $token, $fkMatches)) {
                        $tableInfo['foreign_keys'][] = [
                            'local_column' => $fkMatches[1],
                            'ref_table' => $fkMatches[2],
                            'ref_column' => $fkMatches[3]
                        ];
                    }
                }
            }
        }

        return $tableInfo;
    }

    /**
     * 对比两个表结构信息
     * @param array $table1 第一个表结构信息
     * @param array $table2 第二个表结构信息
     * @return array 差异信息
     */
    public function compareTables($table1, $table2) {
        $differences = [
            'table_name' => [],
            'columns' => [
                'added' => [],
                'removed' => [],
                'modified' => []
            ],
            'primary_key' => [],
            'unique_keys' => [
                'added' => [],
                'removed' => [],
                'modified' => []
            ],
            'foreign_keys' => [
                'added' => [],
                'removed' => []
            ],
            'charset' => [],
            'collation' => []
        ];

        // 对比表名
        if ($table1['table_name'] !== $table2['table_name']) {
            $differences['table_name'] = [
                'old' => $table1['table_name'],
                'new' => $table2['table_name']
            ];
        }

        // 对比字符集（如果不忽略）
        if (!$this->ignoreCharset) {
            if ($table1['charset'] !== $table2['charset']) {
                $differences['charset'] = [
                    'old' => $table1['charset'],
                    'new' => $table2['charset']
                ];
            }
            if ($table1['collation'] !== $table2['collation']) {
                $differences['collation'] = [
                    'old' => $table1['collation'],
                    'new' => $table2['collation']
                ];
            }
        }

        // 对比字段
        $cols1 = $table1['columns'];
        $cols2 = $table2['columns'];

        // 新增字段
        foreach ($cols2 as $colName => $col) {
            if (!isset($cols1[$colName])) {
                $differences['columns']['added'][] = $col;
            }
        }

        // 移除字段
        foreach ($cols1 as $colName => $col) {
            if (!isset($cols2[$colName])) {
                $differences['columns']['removed'][] = $col;
            }
        }

        // 修改字段
        foreach ($cols1 as $colName => $col1) {
            if (isset($cols2[$colName])) {
                $col2 = $cols2[$colName];
                $changes = [];

                if ($col1['type'] !== $col2['type']) {
                    $changes['type'] = ['old' => $col1['type'], 'new' => $col2['type']];
                }
                if ($col1['nullable'] !== $col2['nullable']) {
                    $changes['nullable'] = ['old' => $col1['nullable'], 'new' => $col2['nullable']];
                }
                if ($col1['default'] !== $col2['default']) {
                    $changes['default'] = ['old' => $col1['default'], 'new' => $col2['default']];
                }
                if ($col1['comment'] !== $col2['comment']) {
                    $changes['comment'] = ['old' => $col1['comment'], 'new' => $col2['comment']];
                }

                if (!empty($changes)) {
                    $differences['columns']['modified'][] = [
                        'name' => $colName,
                        'changes' => $changes,
                        'old' => $col1,
                        'new' => $col2
                    ];
                }
            }
        }

        // 对比主键
        if (implode(',', $table1['primary_key']) !== implode(',', $table2['primary_key'])) {
            $differences['primary_key'] = [
                'old' => $table1['primary_key'],
                'new' => $table2['primary_key']
            ];
        }

        // 对比唯一键
        $uks1 = $table1['unique_keys'];
        $uks2 = $table2['unique_keys'];

        // 新增唯一键
        foreach ($uks2 as $keyName => $cols) {
            if (!isset($uks1[$keyName])) {
                $differences['unique_keys']['added'][$keyName] = $cols;
            }
        }

        // 移除唯一键
        foreach ($uks1 as $keyName => $cols) {
            if (!isset($uks2[$keyName])) {
                $differences['unique_keys']['removed'][$keyName] = $cols;
            }
        }

        // 修改唯一键
        foreach ($uks1 as $keyName => $cols1) {
            if (isset($uks2[$keyName])) {
                $cols2 = $uks2[$keyName];
                if (implode(',', $cols1) !== implode(',', $cols2)) {
                    $differences['unique_keys']['modified'][$keyName] = [
                        'old' => $cols1,
                        'new' => $cols2
                    ];
                }
            }
        }

        // 对比外键
        $fks1Str = $this->foreignKeysToStr($table1['foreign_keys']);
        $fks2Str = $this->foreignKeysToStr($table2['foreign_keys']);

        $differences['foreign_keys']['removed'] = array_filter($table1['foreign_keys'], function($fk) use ($fks2Str) {
            return !strpos($fks2Str, $this->foreignKeyToStr($fk));
        });

        $differences['foreign_keys']['added'] = array_filter($table2['foreign_keys'], function($fk) use ($fks1Str) {
            return !strpos($fks1Str, $this->foreignKeyToStr($fk));
        });

        return $differences;
    }

    /**
     * 生成修改SQL语句
     * @param array $differences 差异信息
     * @param string $baseTableName 基准表名（使用哪个表名作为修改目标）
     * @return string 修改SQL
     */
    public function generateAlterSql($differences, $baseTableName = null) {
        $sql = [];
        $tableName = $baseTableName ?: $differences['table_name']['old'] ?? $differences['table_name']['new'] ?? '';

        if (!$tableName) {
            return "";
        }

        // 处理表名修改（需要单独的RENAME语句）
        $renameSql = '';
        if (!empty($differences['table_name'])) {
            $renameSql = "ALTER TABLE `{$differences['table_name']['old']}` RENAME TO `{$differences['table_name']['new']}`;";
            $tableName = $differences['table_name']['new']; // 后续操作使用新表名
        }

        // 处理字段操作
        foreach ($differences['columns']['added'] as $col) {
            $parts = ["ADD COLUMN `{$col['name']}` {$col['type']}"];
            if (!$col['nullable']) {
                $parts[] = 'NOT NULL';
            }
            if ($col['default'] !== null) {
                $parts[] = "DEFAULT {$col['default']}";
            }
            if ($col['comment']) {
                $parts[] = "COMMENT '{$col['comment']}'";
            }
            $sql[] = implode(' ', $parts);
        }

        foreach ($differences['columns']['removed'] as $col) {
            $sql[] = "DROP COLUMN `{$col['name']}`";
        }

        foreach ($differences['columns']['modified'] as $mod) {
            $col = $mod['new'];
            $parts = ["MODIFY COLUMN `{$col['name']}` {$col['type']}"];
            if (!$col['nullable']) {
                $parts[] = 'NOT NULL';
            }
            if ($col['default'] !== null) {
                $parts[] = "DEFAULT {$col['default']}";
            }
            if ($col['comment']) {
                $parts[] = "COMMENT '{$col['comment']}'";
            }
            $sql[] = implode(' ', $parts);
        }

        // 处理主键操作
        if (!empty($differences['primary_key'])) {
            if (!empty($differences['primary_key']['old'])) {
                $sql[] = "DROP PRIMARY KEY";
            }
            if (!empty($differences['primary_key']['new'])) {
                $pkCols = implode('`,`', $differences['primary_key']['new']);
                $sql[] = "ADD PRIMARY KEY (`{$pkCols}`)";
            }
        }

        // 处理唯一键操作
        foreach ($differences['unique_keys']['removed'] as $keyName => $cols) {
            $sql[] = "DROP INDEX `{$keyName}`";
        }

        foreach ($differences['unique_keys']['added'] as $keyName => $cols) {
            $ukCols = implode('`,`', $cols);
            $sql[] = "ADD UNIQUE KEY `{$keyName}` (`{$ukCols}`)";
        }

        foreach ($differences['unique_keys']['modified'] as $keyName => $change) {
            $sql[] = "DROP INDEX `{$keyName}`";
            $ukCols = implode('`,`', $change['new']);
            $sql[] = "ADD UNIQUE KEY `{$keyName}` (`{$ukCols}`)";
        }

        // 处理外键操作
        foreach ($differences['foreign_keys']['removed'] as $fk) {
            $fkName = "fk_{$tableName}_{$fk['local_column']}"; // 简单生成外键名
            $sql[] = "DROP FOREIGN KEY `{$fkName}`";
        }

        foreach ($differences['foreign_keys']['added'] as $fk) {
            $fkName = "fk_{$tableName}_{$fk['local_column']}";
            $sql[] = "ADD CONSTRAINT `{$fkName}` FOREIGN KEY (`{$fk['local_column']}`) REFERENCES `{$fk['ref_table']}` (`{$fk['ref_column']}`)";
        }

        // 处理字符集和校对规则
        if (!$this->ignoreCharset) {
            if (!empty($differences['charset']['new'])) {
                $sql[] = "DEFAULT CHARACTER SET {$differences['charset']['new']}";
            }
            if (!empty($differences['collation']['new'])) {
                $sql[] = "COLLATE {$differences['collation']['new']}";
            }
        }

        // 组合ALTER语句
        $alterSql = '';
        if (!empty($sql)) {
            $alterSql = "ALTER TABLE `{$tableName}`\n  " . implode(",\n  ", $sql) . ";";
        }

        // 组合所有SQL
        $result = [];
        if ($renameSql) $result[] = $renameSql;
        if ($alterSql) $result[] = $alterSql;

        return implode("\n\n", $result) ?: "";
    }

    /**
     * 外键数组转字符串，用于对比
     */
    private function foreignKeysToStr($fks) {
        $str = '';
        foreach ($fks as $fk) {
            $str .= $this->foreignKeyToStr($fk) . '|';
        }
        return $str;
    }

    /**
     * 单个外键转字符串
     */
    private function foreignKeyToStr($fk) {
        return $fk['local_column'] . '@' . $fk['ref_table'] . '@' . $fk['ref_column'];
    }

    /**
     * 格式化输出差异信息
     */
    public function formatDifferences($differences) {
        $output = "表结构差异对比结果：\n\n";

        // 表名差异
        if (!empty($differences['table_name'])) {
            $output .= "表名差异：\n";
            $output .= "  旧表名：{$differences['table_name']['old']}\n";
            $output .= "  新表名：{$differences['table_name']['new']}\n\n";
        }

        // 字符集差异
        if (!$this->ignoreCharset) {
            if (!empty($differences['charset'])) {
                $output .= "字符集差异：\n";
                $output .= "  旧字符集：{$differences['charset']['old']}\n";
                $output .= "  新字符集：{$differences['charset']['new']}\n\n";
            }
            if (!empty($differences['collation'])) {
                $output .= "校对规则差异：\n";
                $output .= "  旧规则：{$differences['collation']['old']}\n";
                $output .= "  新规则：{$differences['collation']['new']}\n\n";
            }
        }

        // 字段差异
        if (!empty($differences['columns']['added'])) {
            $output .= "新增字段：\n";
            foreach ($differences['columns']['added'] as $col) {
                $output .= "  - {$col['name']}（类型：{$col['type']}，" . ($col['nullable'] ? '允许为NULL' : '不允许为NULL') . "）\n";
            }
            $output .= "\n";
        }

        if (!empty($differences['columns']['removed'])) {
            $output .= "移除字段：\n";
            foreach ($differences['columns']['removed'] as $col) {
                $output .= "  - {$col['name']}（类型：{$col['type']}）\n";
            }
            $output .= "\n";
        }

        if (!empty($differences['columns']['modified'])) {
            $output .= "修改字段：\n";
            foreach ($differences['columns']['modified'] as $mod) {
                $output .= "  - {$mod['name']}：\n";
                foreach ($mod['changes'] as $key => $change) {
                    $output .= "    * {$key}：从「{$change['old']}」改为「{$change['new']}」\n";
                }
            }
            $output .= "\n";
        }

        // 主键差异
        if (!empty($differences['primary_key'])) {
            $output .= "主键差异：\n";
            $output .= "  旧主键：" . implode(',', $differences['primary_key']['old']) . "\n";
            $output .= "  新主键：" . implode(',', $differences['primary_key']['new']) . "\n\n";
        }

        // 唯一键差异
        if (!empty($differences['unique_keys']['added'])) {
            $output .= "新增唯一键：\n";
            foreach ($differences['unique_keys']['added'] as $key => $cols) {
                $output .= "  - {$key}：" . implode(',', $cols) . "\n";
            }
            $output .= "\n";
        }

        if (!empty($differences['unique_keys']['removed'])) {
            $output .= "移除唯一键：\n";
            foreach ($differences['unique_keys']['removed'] as $key => $cols) {
                $output .= "  - {$key}：" . implode(',', $cols) . "\n";
            }
            $output .= "\n";
        }

        if (!empty($differences['unique_keys']['modified'])) {
            $output .= "修改唯一键：\n";
            foreach ($differences['unique_keys']['modified'] as $key => $change) {
                $output .= "  - {$key}：从「" . implode(',', $change['old']) . "」改为「" . implode(',', $change['new']) . "」\n";
            }
            $output .= "\n";
        }

        // 外键差异
        if (!empty($differences['foreign_keys']['added'])) {
            $output .= "新增外键：\n";
            foreach ($differences['foreign_keys']['added'] as $fk) {
                $output .= "  - {$fk['local_column']} 引用 {$fk['ref_table']}.{$fk['ref_column']}\n";
            }
            $output .= "\n";
        }

        if (!empty($differences['foreign_keys']['removed'])) {
            $output .= "移除外键：\n";
            foreach ($differences['foreign_keys']['removed'] as $fk) {
                $output .= "  - {$fk['local_column']} 引用 {$fk['ref_table']}.{$fk['ref_column']}\n";
            }
            $output .= "\n";
        }

        if (empty(array_filter($differences, function($item) {
            return !empty($item);
        }))) {
            $output .= "两个表结构完全一致！\n";
        }

        return $output;
    }
}

