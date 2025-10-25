<?php

namespace Sc\Util\MysqlStructureSync;

/**
 * 对比两个建表SQL的结构差异（保留字段AUTO_INCREMENT属性，忽略表级自增值）
 */
class SqlTableComparator {
    private $ignoreCharset;    // 忽略字符集对比
    private $ignoreCollation;  // 忽略校对规则对比

    /**
     * 构造方法：控制字符集和校对规则的忽略逻辑
     * @param bool $ignoreCharset 忽略字符集对比
     * @param bool $ignoreCollation 忽略略校对规则对比（默认true）
     */
    public function __construct($ignoreCharset = false, $ignoreCollation = true) {
        $this->ignoreCharset = $ignoreCharset;
        $this->ignoreCollation = $ignoreCollation;
    }

    /**
     * 解析建表SQL（保留字段AUTO_INCREMENT属性，忽略表级自增值）
     */
    public function parseCreateTableSql($sql) {
        $tableInfo = [
            'table_name' => '',
            'columns' => [],
            'column_order' => [],
            'primary_key' => [],
            'unique_keys' => [],
            'indexes' => [],
            'foreign_keys' => [],
            'charset' => '',
            'collation' => ''
            // 移除表级auto_increment字段（不再解析表级自增值）
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
            if (!$this->ignoreCollation && preg_match('/COLLATE\s*=\s*([^\s,]+)/i', $sql, $matches)) {
                $tableInfo['collation'] = $matches[1];
            }
        }

        // 提取字段和索引定义
        if (preg_match('/\((.*)\)/s', $sql, $matches)) {
            $content = $matches[1];
            $tokens = preg_split('/,(?![^()]*\))/', $content);

            foreach ($tokens as $token) {
                $token = trim($token);
                if (empty($token)) continue;

                // 处理字段定义（保留AUTO_INCREMENT属性）
                if (preg_match('/^`?([^`\s]+)`?\s+(.+?)(?=\s+(PRIMARY KEY|UNIQUE|KEY|INDEX|NOT NULL|DEFAULT|COMMENT|AUTO_INCREMENT|REFERENCES|,|$))/i', $token, $colMatches)) {
                    $colName = $colMatches[1];
                    $dataType = trim($colMatches[2]);

                    // 处理字符集（忽略时移除相关信息）
                    if ($this->ignoreCharset) {
                        $dataType = $this->stripCharsetAndCollation($dataType);
                    } else {
                        if (preg_match('/CHARACTER SET\s+([^\s,]+)/i', $token, $csMatches)) {
                            $dataType .= ' CHARACTER SET ' . $csMatches[1];
                        }
                        if (!$this->ignoreCollation && preg_match('/COLLATE\s+([^\s,]+)/i', $token, $clMatches)) {
                            $dataType .= ' COLLATE ' . $clMatches[1];
                        }
                    }

                    $column = [
                        'name' => $colName,
                        'type' => $dataType,
                        'nullable' => true,
                        'default' => null,
                        'comment' => '',
                        'auto_increment' => stripos($token, 'AUTO_INCREMENT') !== false // 保留字段自增属性
                    ];

                    // 解析NOT NULL
                    if (stripos($token, 'NOT NULL') !== false) {
                        $column['nullable'] = false;
                    }

                    // 解析默认值
                    if (preg_match('/DEFAULT\s+(.+?)(?=\s+(NOT NULL|COMMENT|AUTO_INCREMENT|,|$))/i', $token, $defMatches)) {
                        $column['default'] = trim($defMatches[1]);
                    }

                    // 解析注释
                    if (preg_match('/COMMENT\s+[\'\"](.*?)[\'\"]/i', $token, $comMatches)) {
                        $column['comment'] = $comMatches[1];
                    }

                    $tableInfo['columns'][$colName] = $column;
                    $tableInfo['column_order'][] = $colName;
                    continue;
                }

                // 处理主键
                if (stripos($token, 'PRIMARY KEY') !== false) {
                    if (preg_match('/PRIMARY KEY\s*(?:USING \w+)?\s*\(`?([^`\)]+)`?\)/i', $token, $pkMatches)) {
                        $tableInfo['primary_key'] = explode('`,`', trim($pkMatches[1], '`'));
                    }
                    continue;
                }

                // 处理唯一键
                if (stripos($token, 'UNIQUE') !== false) {
                    if (preg_match('/UNIQUE\s+(KEY|INDEX)\s+`?([^`\s]*?)`?\s*(?:USING \w+)?\s*\(`?([^`\)]+)`?\)/i', $token, $ukMatches)) {
                        $keyName = $ukMatches[2] ?: $this->generateIndexName($ukMatches[3]);
                        $columns = explode('`,`', trim($ukMatches[3], '`'));
                        $tableInfo['unique_keys'][$keyName] = $columns;
                    }
                    continue;
                }

                // 处理普通索引
                if (stripos($token, 'KEY') !== false || stripos($token, 'INDEX') !== false) {
                    if (stripos($token, 'PRIMARY') !== false || stripos($token, 'UNIQUE') !== false) {
                        continue;
                    }
                    if (preg_match('/(KEY|INDEX)\s+`?([^`\s]*?)`?\s*(?:USING \w+)?\s*\(`?([^`\)]+)`?\)/i', $token, $idxMatches)) {
                        $keyName = $idxMatches[2] ?: $this->generateIndexName($idxMatches[3]);
                        $columns = explode('`,`', trim($idxMatches[3], '`'));
                        $tableInfo['indexes'][$keyName] = $columns;
                    }
                    continue;
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
                    continue;
                }
            }
        }

        return $tableInfo;
    }

    /**
     * 生成默认索引名
     */
    private function generateIndexName($columnsStr) {
        $columns = explode('`,`', trim($columnsStr, '`'));
        return 'idx_' . implode('_', $columns);
    }

    /**
     * 对比表结构（保留字段AUTO_INCREMENT属性对比，忽略表级自增值）
     */
    public function compareTables($table1, $table2) {
        $differences = [
            'table_name' => [],
            'columns' => [
                'added' => [],
                'removed' => [],
                'modified' => [],
                'order_changed' => []
            ],
            'primary_key' => [],
            'unique_keys' => [
                'added' => [],
                'removed' => [],
                'modified' => []
            ],
            'indexes' => [
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
            // 移除表级auto_increment差异项
        ];

        // 对比表名
        if ($table1['table_name'] !== $table2['table_name']) {
            $differences['table_name'] = [
                'old' => $table1['table_name'],
                'new' => $table2['table_name']
            ];
        }

        // 对比字符集
        if (!$this->ignoreCharset && $table1['charset'] !== $table2['charset']) {
            $differences['charset'] = [
                'old' => $table1['charset'],
                'new' => $table2['charset']
            ];
        }

        // 对比校对规则
        if (!$this->ignoreCharset && !$this->ignoreCollation &&
            $table1['collation'] !== $table2['collation']) {
            $differences['collation'] = [
                'old' => $table1['collation'],
                'new' => $table2['collation']
            ];
        }

        // 对比字段（保留AUTO_INCREMENT属性对比）
        $cols1 = $table1['columns'];
        $cols2 = $table2['columns'];
        $colOrder1 = $table1['column_order'];
        $colOrder2 = $table2['column_order'];

        // 新增字段
        foreach ($cols2 as $colName => $col) {
            if (!isset($cols1[$colName])) {
                $pos = array_search($colName, $colOrder2);
                $prevCol = ($pos > 0) ? $colOrder2[$pos - 1] : null;
                $differences['columns']['added'][] = array_merge($col, [
                    'position' => [
                        'prev_column' => $prevCol,
                        'is_first' => ($pos === 0)
                    ]
                ]);
            }
        }

        // 移除字段
        foreach ($cols1 as $colName => $col) {
            if (!isset($cols2[$colName])) {
                $differences['columns']['removed'][] = $col;
            }
        }

        // 修改字段（保留AUTO_INCREMENT属性对比）
        foreach ($cols1 as $colName => $col1) {
            if (isset($cols2[$colName])) {
                $col2 = $cols2[$colName];
                $changes = [];

                // 对比AUTO_INCREMENT属性（保留）
                if ($col1['auto_increment'] !== $col2['auto_increment']) {
                    $changes['auto_increment'] = [
                        'old' => $col1['auto_increment'] ? '是' : '否',
                        'new' => $col2['auto_increment'] ? '是' : '否'
                    ];
                }

                // 对比其他字段属性
                if ($col1['type'] !== $col2['type']) {
                    $changes['type'] = ['old' => $col1['type'], 'new' => $col2['type']];
                }
                if ($col1['nullable'] !== $col2['nullable']) {
                    $changes['nullable'] = [
                        'old' => $col1['nullable'] ? '是' : '否',
                        'new' => $col2['nullable'] ? '是' : '否'
                    ];
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

        // 字段顺序变更
        $commonCols = array_intersect($colOrder1, $colOrder2);
        $sorted1 = array_values(array_filter($colOrder1, function($col) use ($commonCols) {
            return in_array($col, $commonCols);
        }));
        $sorted2 = array_values(array_filter($colOrder2, function($col) use ($commonCols) {
            return in_array($col, $commonCols);
        }));
        if ($sorted1 !== $sorted2) {
            $differences['columns']['order_changed'] = [
                'old' => $sorted1,
                'new' => $sorted2
            ];
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
        foreach ($uks2 as $keyName => $cols) {
            if (!isset($uks1[$keyName])) {
                $differences['unique_keys']['added'][$keyName] = $cols;
            }
        }
        foreach ($uks1 as $keyName => $cols) {
            if (!isset($uks2[$keyName])) {
                $differences['unique_keys']['removed'][$keyName] = $cols;
            }
        }
        foreach ($uks1 as $keyName => $cols1) {
            if (isset($uks2[$keyName]) &&
                implode(',', $cols1) !== implode(',', $uks2[$keyName])) {
                $differences['unique_keys']['modified'][$keyName] = [
                    'old' => $cols1,
                    'new' => $uks2[$keyName]
                ];
            }
        }

        // 对比普通索引
        $idx1 = $table1['indexes'];
        $idx2 = $table2['indexes'];
        foreach ($idx2 as $keyName => $cols) {
            if (!isset($idx1[$keyName])) {
                $differences['indexes']['added'][$keyName] = $cols;
            }
        }
        foreach ($idx1 as $keyName => $cols) {
            if (!isset($idx2[$keyName])) {
                $differences['indexes']['removed'][$keyName] = $cols;
            }
        }
        foreach ($idx1 as $keyName => $cols1) {
            if (isset($idx2[$keyName]) &&
                implode(',', $cols1) !== implode(',', $idx2[$keyName])) {
                $differences['indexes']['modified'][$keyName] = [
                    'old' => $cols1,
                    'new' => $idx2[$keyName]
                ];
            }
        }

        // 对比外键
        $fks1Str = $this->foreignKeysToStr($table1['foreign_keys']);
        $fks2Str = $this->foreignKeysToStr($table2['foreign_keys']);
        $differences['foreign_keys']['removed'] = array_filter(
            $table1['foreign_keys'],
            function($fk) use ($fks2Str) {
                return !strpos($fks2Str, $this->foreignKeyToStr($fk));
            }
        );
        $differences['foreign_keys']['added'] = array_filter(
            $table2['foreign_keys'],
            function($fk) use ($fks1Str) {
                return !strpos($fks1Str, $this->foreignKeyToStr($fk));
            }
        );

        return $differences;
    }

    /**
     * 生成修改SQL（保留字段AUTO_INCREMENT属性，忽略表级自增值）
     */
    public function generateAlterSql($differences, $baseTableName = null, $targetTable = []) {
        $sql = [];
        $tableName = $baseTableName ?: $differences['table_name']['old'] ??
            $differences['table_name']['new'] ?? '';
        if (!$tableName) return "";

        // 表名修改
        $renameSql = '';
        if (!empty($differences['table_name'])) {
            $renameSql = "ALTER TABLE `{$differences['table_name']['old']}` RENAME TO `{$differences['table_name']['new']}`;";
            $tableName = $differences['table_name']['new'];
        }

        // 字段操作（保留AUTO_INCREMENT属性）
        foreach ($differences['columns']['added'] as $col) {
            $parts = ["ADD COLUMN `{$col['name']}` {$col['type']}"];
            if (!$col['nullable']) $parts[] = 'NOT NULL';
            if ($col['auto_increment']) $parts[] = 'AUTO_INCREMENT'; // 保留字段自增属性
            if ($col['default'] !== null) $parts[] = "DEFAULT {$col['default']}";
            if ($col['comment']) $parts[] = "COMMENT '{$col['comment']}'";
            if ($col['position']['is_first']) $parts[] = 'FIRST';
            elseif ($col['position']['prev_column']) {
                $parts[] = "AFTER `{$col['position']['prev_column']}`";
            }
            $sql[] = implode(' ', $parts);
        }

        foreach ($differences['columns']['removed'] as $col) {
            $sql[] = "DROP COLUMN `{$col['name']}`";
        }

        foreach ($differences['columns']['modified'] as $mod) {
            $col = $mod['new'];
            $parts = ["MODIFY COLUMN `{$col['name']}` {$col['type']}"];
            if (!$col['nullable']) $parts[] = 'NOT NULL';
            if ($col['auto_increment']) $parts[] = 'AUTO_INCREMENT'; // 保留字段自增属性
            if ($col['default'] !== null) $parts[] = "DEFAULT {$col['default']}";
            if ($col['comment']) $parts[] = "COMMENT '{$col['comment']}'";
            $sql[] = implode(' ', $parts);
        }

        // 字段顺序调整（保留AUTO_INCREMENT属性）
        if (!empty($differences['columns']['order_changed']) && !empty($targetTable['columns'])) {
            $newOrder = $differences['columns']['order_changed']['new'];
            for ($i = 1; $i < count($newOrder); $i++) {
                $colName = $newOrder[$i];
                $prevCol = $newOrder[$i - 1];
                $colInfo = $targetTable['columns'][$colName] ?? [];
                if (empty($colInfo)) continue;
                $parts = ["MODIFY COLUMN `{$colName}` {$colInfo['type']}"];
                if (!$colInfo['nullable']) $parts[] = 'NOT NULL';
                if ($colInfo['auto_increment']) $parts[] = 'AUTO_INCREMENT'; // 保留字段自增属性
                if ($colInfo['default'] !== null) $parts[] = "DEFAULT {$colInfo['default']}";
                if ($colInfo['comment']) $parts[] = "COMMENT '{$colInfo['comment']}'";
                $parts[] = "AFTER `{$prevCol}`";
                $sql[] = implode(' ', $parts);
            }
        }

        // 主键操作
        if (!empty($differences['primary_key'])) {
            if (!empty($differences['primary_key']['old'])) {
                $sql[] = "DROP PRIMARY KEY";
            }
            if (!empty($differences['primary_key']['new'])) {
                $pkCols = implode('`,`', $differences['primary_key']['new']);
                $sql[] = "ADD PRIMARY KEY (`{$pkCols}`)";
            }
        }

        // 唯一键操作
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

        // 普通索引操作
        foreach ($differences['indexes']['removed'] as $keyName => $cols) {
            $sql[] = "DROP INDEX `{$keyName}`";
        }
        foreach ($differences['indexes']['added'] as $keyName => $cols) {
            $idxCols = implode('`,`', $cols);
            $sql[] = "ADD INDEX `{$keyName}` (`{$idxCols}`)";
        }
        foreach ($differences['indexes']['modified'] as $keyName => $change) {
            $sql[] = "DROP INDEX `{$keyName}`";
            $idxCols = implode('`,`', $change['new']);
            $sql[] = "ADD INDEX `{$keyName}` (`{$idxCols}`)";
        }

        // 外键操作
        foreach ($differences['foreign_keys']['removed'] as $fk) {
            $fkName = "fk_{$tableName}_{$fk['local_column']}";
            $sql[] = "DROP FOREIGN KEY `{$fkName}`";
        }
        foreach ($differences['foreign_keys']['added'] as $fk) {
            $fkName = "fk_{$tableName}_{$fk['local_column']}";
            $sql[] = "ADD CONSTRAINT `{$fkName}` FOREIGN KEY (`{$fk['local_column']}`) REFERENCES `{$fk['ref_table']}` (`{$fk['ref_column']}`)";
        }

        // 字符集操作
        if (!$this->ignoreCharset) {
            if (!empty($differences['charset']['new'])) {
                $sql[] = "DEFAULT CHARACTER SET {$differences['charset']['new']}";
            }
            if (!empty($differences['collation']['new']) && !$this->ignoreCollation) {
                $sql[] = "COLLATE {$differences['collation']['new']}";
            }
        }

        // 组合SQL（不包含表级AUTO_INCREMENT修改）
        $alterSql = '';
        if (!empty($sql)) {
            $alterSql = "ALTER TABLE `{$tableName}`\n  " . implode(",\n  ", $sql) . ";";
        }

        $result = [];
        if ($renameSql) $result[] = $renameSql;
        if ($alterSql) $result[] = $alterSql;
        return implode("\n\n", $result) ?: "";
    }

    /**
     * 辅助方法：移除类型字符串中的CHARACTER SET和COLLATE
     */
    private function stripCharsetAndCollation($typeStr) {
        $typeStr = preg_replace('/\s+CHARACTER SET\s+[^ \']+/i', '', $typeStr);
        $typeStr = preg_replace('/\s+COLLATE\s+[^ \']+/i', '', $typeStr);
        return $typeStr;
    }

    /**
     * 外键转字符串辅助方法
     */
    private function foreignKeysToStr($fks) {
        $str = '';
        foreach ($fks as $fk) $str .= $this->foreignKeyToStr($fk) . '|';
        return $str;
    }

    private function foreignKeyToStr($fk) {
        return $fk['local_column'] . '@' . $fk['ref_table'] . '@' . $fk['ref_column'];
    }

    /**
     * 格式化差异信息（保留字段AUTO_INCREMENT属性，忽略表级自增值）
     */
    public function formatDifferences($differences) {
        $output = "表结构差异对比结果：\n\n";

        if (!empty($differences['table_name'])) {
            $output .= "表名差异：\n  旧表名：{$differences['table_name']['old']}\n  新表名：{$differences['table_name']['new']}\n\n";
        }

        // 字符集差异
        if (!$this->ignoreCharset) {
            if (!empty($differences['charset'])) {
                $output .= "字符集差异：\n  旧字符集：{$differences['charset']['old']}\n  新字符集：{$differences['charset']['new']}\n\n";
            }
            if (!empty($differences['collation']) && !$this->ignoreCollation) {
                $output .= "校对规则差异：\n  旧规则：{$differences['collation']['old']}\n  新规则：{$differences['collation']['new']}\n\n";
            }
        }

        // 字段差异（保留AUTO_INCREMENT属性）
        if (!empty($differences['columns']['added'])) {
            $output .= "新增字段：\n";
            foreach ($differences['columns']['added'] as $col) {
                $autoInc = $col['auto_increment'] ? '（自增）' : '';
                $posDesc = $col['position']['is_first'] ? '（表的第一个字段）' : "（在`{$col['position']['prev_column']}`之后）";
                $output .= "  - {$col['name']}（类型：{$col['type']}{$autoInc}，" .
                    ($col['nullable'] ? '允许为NULL' : '不允许为NULL') . "）{$posDesc}\n";
            }
            $output .= "\n";
        }

        if (!empty($differences['columns']['removed'])) {
            $output .= "移除字段：\n";
            foreach ($differences['columns']['removed'] as $col) {
                $autoInc = $col['auto_increment'] ? '（自增）' : '';
                $output .= "  - {$col['name']}（类型：{$col['type']}{$autoInc}）\n";
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

        if (!empty($differences['columns']['order_changed'])) {
            $output .= "字段顺序变更：\n  旧顺序：" . implode(' → ', $differences['columns']['order_changed']['old']) .
                "\n  新顺序：" . implode(' → ', $differences['columns']['order_changed']['new']) . "\n\n";
        }

        // 主键差异
        if (!empty($differences['primary_key'])) {
            $output .= "主键差异：\n  旧主键：" . implode(',', $differences['primary_key']['old']) .
                "\n  新主键：" . implode(',', $differences['primary_key']['new']) . "\n\n";
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

        // 普通索引差异
        if (!empty($differences['indexes']['added'])) {
            $output .= "新增普通索引：\n";
            foreach ($differences['indexes']['added'] as $key => $cols) {
                $output .= "  - {$key}：" . implode(',', $cols) . "\n";
            }
            $output .= "\n";
        }

        if (!empty($differences['indexes']['removed'])) {
            $output .= "移除普通索引：\n";
            foreach ($differences['indexes']['removed'] as $key => $cols) {
                $output .= "  - {$key}：" . implode(',', $cols) . "\n";
            }
            $output .= "\n";
        }

        if (!empty($differences['indexes']['modified'])) {
            $output .= "修改普通索引：\n";
            foreach ($differences['indexes']['modified'] as $key => $change) {
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