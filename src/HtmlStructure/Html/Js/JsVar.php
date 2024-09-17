<?php
/**
 * datetime: 2023/5/16 23:43
 **/

namespace Sc\Util\HtmlStructure\Html\Js;

use JetBrains\PhpStorm\ExpectedValues;
use Sc\Util\HtmlStructure\Html\Common;

/**
 * js变量
 *
 * Class Vars
 *
 * @package Sc\Util\HtmlStructure\Html\Js
 * @date    2023/5/16
 */
class JsVar
{
    // 定义
    private const SCENE_DEF = "def";

    // 赋值
    private const SCENE_ASSIGN = "assign";

    private function __construct(
        public readonly ?string $name = null,
        public mixed  $value = null,
        public readonly string $scene = self::SCENE_DEF,
        public readonly string $scope = 'let'
    )
    {
    }

    /**
     * 定义变量
     *
     * @param string     $name
     * @param mixed|null $value
     * @param string     $scope
     *
     * @return JsVar
     * @date 2023/5/16
     */
    public static function def(string $name, mixed $value = null, #[ExpectedValues(['let', 'var'])] string $scope = 'let'): JsVar
    {
        return new self($name, $value, self::SCENE_DEF, $scope);
    }

    /**
     * 给变量赋值
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return JsVar
     * @date 2023/5/31
     */
    public static function assign(string $name, mixed $value): JsVar
    {
        return new self($name, $value, self::SCENE_ASSIGN);
    }

    /**
     * @param string $name
     * @param mixed  $value
     *
     * @return JsVar
     */
    public static function set(string $name, mixed $value): JsVar
    {
        return self::assign($name, $value);
    }

    public function __toString(): string
    {
        return $this->toCode();
    }

    public function toCode(): string
    {
        $var = $this->scene === self::SCENE_DEF ? "$this->scope $this->name" : $this->name;

        if ($this->value !== null) {
            if (is_string($this->value) && preg_match('/^(\\\)?@(.*)/s', $this->value, $match)) {
                $outputValue = $match[1] ? sprintf("\"@%s\"", $match[2]) : $match[2];
            }else{
                if (is_array($this->value)) {
                    array_walk_recursive($this->value, function (&$value) {
                        if (is_string($value)){
                            if (str_contains($value, "\n")) {
                                $value = Grammar::mark($value, 'line');
                            }
                            if (str_starts_with($value, '@')) {
                                $value = Grammar::mark(substr($value, 1));
                            }
                            if (str_contains($value, '"')) {
                                $defJsFnBase64Decode = Common::defJsFnBase64Decode();
                                $value = base64_encode(Grammar::extract($value));
                                $value = Grammar::mark("$defJsFnBase64Decode(`$value`)");
                            }
                        }
                        if ($value instanceof JsFunc) {
                            $value = $value->toCode();
                        }
                    });
                }

                $outputValue = match (true) {
                    is_numeric($this->value)             => $this->value,
                    is_array($this->value)
                    || $this->value instanceof \stdClass => json_encode($this->value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
                    is_bool($this->value)                => $this->value ? 'true' : 'false',
                    default                              => "\"$this->value\""
                };
            }

            $var .= " = $outputValue";
        }

        return "$var;";
    }
}