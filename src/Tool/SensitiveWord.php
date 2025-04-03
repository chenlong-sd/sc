<?php

namespace Sc\Util\Tool;

use JetBrains\PhpStorm\ExpectedValues;

/**
 * Class SensitiveWord
 */
class SensitiveWord
{

    /**
     * @var string[]
     */
    private array $waitVerifyText;

    final const DEFAULT_WORD = [
        '其他词库.txt',
        '反动词库.txt',
        '暴恐词库.txt',
        '民生词库.txt',
        '色情词库.txt',
        '贪腐词库.txt',
    ];

    /**
     * 所有的词库文件
     * 
     * @var array|string[] 
     */
    private array $wordSrc = [];
    private static array $word = [];

    /**
     * @param string ...$text 待验证的词组
     */
    public function __construct(string ...$text)
    {
        $this->waitVerifyText = $text;
        $this->wordSrc = self::DEFAULT_WORD;
    }

    /**
     * 设置验证词库
     *
     * @param array|string $word
     *
     * @return $this
     */
    public function setVerifyWord(#[ExpectedValues(self::DEFAULT_WORD)]array|string $word): static
    {
        $this->wordSrc = (array)$word;

        return $this;
    }

    /**
     * 自定义加载更多词库
     *
     * @param string $wordPath
     *
     * @return SensitiveWord
     */
    public function wordMacro(string $wordPath): static
    {
        $this->wordSrc[] = $wordPath;

        return $this;
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function verify()
    {
        self::$word or $this->loadWord();

        foreach ($this->waitVerifyText as $text) {
            foreach (self::$word as $value){
                array_map(fn($word) => str_contains($text, $word) and throw new \Exception("包含敏感词：$word"), $value);
            }
        }
    }

    /**
     * @return void
     */
    public function loadWord()
    {
        self::$word = [];
        foreach ($this->wordSrc as $value) {
            if (in_array($value, self::DEFAULT_WORD)) {
                self::$word[$value] = array_values(array_map('trim', array_filter(explode("\n", file_get_contents(__DIR__ . '/SensitiveWord/' . $value)))));
            }else{
                self::$word[md5($value)] =  array_values(array_map('trim',array_filter(explode("\n", file_get_contents($value)))));
            }
        }
    }
}
