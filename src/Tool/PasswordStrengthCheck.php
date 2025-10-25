<?php

namespace Sc\Util\Tool;

/**
 * 密码强度检测工具
 *
 */
class PasswordStrengthCheck {
    // 等级常量定义
    const LEVEL_VERY_WEAK = 1;  // 极弱
    const LEVEL_WEAK = 2;       // 弱
    const LEVEL_MEDIUM = 3;     // 中
    const LEVEL_STRONG = 4;     // 强
    const LEVEL_VERY_STRONG = 5;// 极强

    // 等级映射（名称与常量对应）
    private array $levelNames = [
        self::LEVEL_VERY_WEAK => '极弱',
        self::LEVEL_WEAK => '弱',
        self::LEVEL_MEDIUM => '中',
        self::LEVEL_STRONG => '强',
        self::LEVEL_VERY_STRONG => '极强'
    ];

    // 配置属性（公开可直接设置）
    private int $minLength = 8;               // 最小长度要求
    private int $requiredTypes = 2;           // 至少包含的字符类型数
    private int $maxRepeat = 2;               // 允许的最大连续重复字符数
    private array $weakPatterns = [             // 弱密码模式库（正则表达式）
        '/^123456.*$/', '/^password.*$/', '/^qwerty.*$/', '/^abc123.*$/',
        '/^admin.*$/', '/^user.*$/', '/^pass.*$/', '/^login.*$/',
        '/^111111.*$/', '/^000000.*$/', '/^666666.*$/', '/^888888.*$/'
    ];
    private array $regularPatterns = [          // 规律性模式库（正则=>描述）
        '/(0123|1234|2345|3456|4567|5678|6789|7890)/' => '连续数字序列（如123456）',
        '/(qwerty|asdfgh|zxcvbn|abcdef)/i' => '键盘连续字符（如qwerty）'
    ];


    /**
     * 构造函数：支持初始化时设置配置
     * @param array $options 初始配置（键名与属性名对应）
     */
    public function __construct(array $options = []) {
        foreach ($options as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }


    /**
     * 检测密码强度
     * @param string $password 待检测密码
     * @return array 检测结果
     */
    public function check(string $password): array
    {
        $score = 0;
        $feedback = [];
        $length = strlen($password);

        // 1. 长度检测
        $this->checkLength($length, $score, $feedback);

        // 2. 字符类型检测
        $typeCount = $this->checkCharTypes($password, $score, $feedback);

        // 3. 规律性检测（含连续重复字符）
        $this->checkRegularPatterns($password, $score, $feedback);

        // 4. 弱密码模式检测
        $this->checkWeakPatterns($password, $score, $feedback);

        // 修正评分（确保非负）
        $score = max(0, $score);

        // 计算等级（基于评分映射到等级常量）
        $level = $this->getLevelByScore($score);

        // 生成改进建议
        $suggestions = $this->generateSuggestions($length, $typeCount);

        return [
            'score' => $score,
            'level' => $level,
            'level_name' => $this->levelNames[$level],
            'feedback' => $feedback,
            'suggestions' => $suggestions
        ];
    }


    /**
     * 添加弱密码模式
     * @param array $patterns 新增正则模式
     */
    public function addWeakPatterns(array $patterns): void
    {
        $this->weakPatterns = array_merge($this->weakPatterns, $patterns);
    }


    /**
     * 添加规律性模式
     * @param array $patterns 新增正则=>描述键值对
     */
    public function addRegularPatterns(array $patterns): void
    {
        $this->regularPatterns = array_merge($this->regularPatterns, $patterns);
    }


    /**
     * 获取所有等级常量及名称
     * @return array 等级映射
     */
    public function getLevelDefinitions(): array
    {
        return $this->levelNames;
    }


    // 私有方法：长度检测
    private function checkLength($length, &$score, &$feedback): void
    {
        if ($length < $this->minLength) {
            $feedback[] = "密码过短（至少需{$this->minLength}位）";
        } else {
            $score += 1;
            // 超长加分（每多4位加1分，上限2分）
            $extraLength = floor(($length - $this->minLength) / 4);
            $score += min(2, $extraLength);
            $feedback[] = "密码长度达标（{$length}位，越长越安全）";
        }
    }


    // 私有方法：字符类型检测
    private function checkCharTypes($password, &$score, &$feedback): int
    {
        $charTypes = [
            '小写字母' => preg_match('/[a-z]/', $password),
            '大写字母' => preg_match('/[A-Z]/', $password),
            '数字' => preg_match('/\d/', $password),
            '特殊符号' => preg_match('/[^a-zA-Z0-9]/', $password),
        ];
        $typeCount = array_sum($charTypes);
        $typeNames = array_keys(array_filter($charTypes));

        if ($typeCount > 0) {
            $score += $typeCount; // 每类字符加1分（最多4分）
            $feedback[] = "包含" . implode('、', $typeNames) . "（共{$typeCount}类字符）";
        } else {
            $feedback[] = "无有效字符（密码不能为空）";
        }

        return $typeCount;
    }


    // 私有方法：规律性检测（含动态生成的连续重复字符规则）
    private function checkRegularPatterns($password, &$score, &$feedback): void
    {
        // 合并连续重复字符规则（基于maxRepeat属性）
        $patterns = $this->regularPatterns + [
                '/(.)\1{' . $this->maxRepeat . ',}/' => "连续重复字符（如超过{$this->maxRepeat}个相同字符）"
            ];

        foreach ($patterns as $pattern => $desc) {
            if (preg_match($pattern, $password)) {
                $score -= 2;
                $feedback[] = "包含{$desc}，易被猜测";
            }
        }
    }


    // 私有方法：弱密码模式检测
    private function checkWeakPatterns($password, &$score, &$feedback): void
    {
        foreach ($this->weakPatterns as $pattern) {
            if (preg_match($pattern, $password)) {
                $score -= 3;
                $feedback[] = "包含常见弱密码片段，风险极高";
                break; // 命中一个即停止
            }
        }
    }


    // 私有方法：根据评分计算等级（映射到等级常量）
    private function getLevelByScore($score): int
    {
        if ($score >= 8) {
            return self::LEVEL_VERY_STRONG;
        } elseif ($score >= 6) {
            return self::LEVEL_STRONG;
        } elseif ($score >= 4) {
            return self::LEVEL_MEDIUM;
        } elseif ($score >= 2) {
            return self::LEVEL_WEAK;
        } else {
            return self::LEVEL_VERY_WEAK;
        }
    }


    // 私有方法：生成改进建议
    private function generateSuggestions($length, $typeCount): array
    {
        $suggestions = [];
        if ($length < $this->minLength) {
            $suggestions[] = "增加长度至{$this->minLength}位以上";
        }
        if ($typeCount < $this->requiredTypes) {
            $suggestions[] = "至少包含{$this->requiredTypes}类字符（大小写、数字、符号）";
        }
        if (empty($suggestions)) {
            $suggestions[] = "当前密码强度良好，无需修改";
        }
        return $suggestions;
    }

    public function setMaxRepeat(int $maxRepeat): PasswordStrengthCheck
    {
        $this->maxRepeat = $maxRepeat;
        return $this;
    }

    public function setRequiredTypes(int $requiredTypes): PasswordStrengthCheck
    {
        $this->requiredTypes = $requiredTypes;
        return $this;
    }

    public function setMinLength(int $minLength): PasswordStrengthCheck
    {
        $this->minLength = $minLength;
        return $this;
    }
}
