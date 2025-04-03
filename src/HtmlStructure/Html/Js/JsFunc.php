<?php
/**
 * datetime: 2023/5/16 0:07
 **/

namespace Sc\Util\HtmlStructure\Html\Js;
use JetBrains\PhpStorm\Language;

/**
 * Js 函数
 *
 * Class Func
 *
 * @package Sc\Util\HtmlStructure\Html\Js
 * @date    2023/5/16
 */
class JsFunc
{
    /**
     * 代码模板
     */
    private const TEMPLATE = <<<'TPL'
    :function :name(:params) :arrow {
        :code
    }
    TPL;


    private function __construct(
        public readonly ?string $name,
        public readonly array  $params,
        public string $code)
    {
    }

    /**
     * 定义函数
     *
     * @param string $name
     * @param array  $params
     * @param string $code
     *
     * @return JsFunc
     * @date 2023/5/18
     */
    public static function def(string $name, array $params, #[Language('JavaScript')] string $code = ''): JsFunc
    {
        return new self($name, $params, $code);
    }

    /**
     * 匿名函数
     *
     * @param array  $params
     * @param string $code
     *
     * @return JsFunc
     * @date 2023/5/18
     */
    public static function anonymous(array $params = [], #[Language('JavaScript')] string $code = ''): JsFunc
    {
        return new self(null, $params, $code);
    }

    /**
     * 箭头函数
     *
     * @param array  $params
     * @param string $code
     *
     * @return self
     * @date 2023/5/19
     */
    public static function arrow(array $params = [], #[Language('JavaScript')] string $code = ''): JsFunc
    {
        return new self('=>', $params, $code);
    }

    /**
     * 调用函数
     *
     * @param string $name
     * @param mixed  $params
     *
     * @return Obj
     * @date 2023/6/1
     */
    public static function call(#[Language('JavaScript')] string $name, ...$params): Obj
    {
        return Obj::use(new self($name, $params, 'undefined'));
    }

    /**
     * @return string
     * @date 2023/5/19
     */
    public function toCode(): string
    {
        return Grammar::mark($this->code === 'undefined' ? $this->callCode() : $this->defCode());
    }

    /**
     * 设置函数代码
     *
     * @param string ...$code
     *
     * @return $this
     */
    public function code(#[Language('JavaScript')] string ...$code): static
    {
        $this->code = JsCode::make(...$code);

        return $this;
    }

    /**
     * @param string $code
     *
     * @return $this
     */
    public function appendCode(#[Language('JavaScript')] string $code): static
    {
        $this->code = JsCode::create($this->code)->then($code);

        return $this;
    }

    /**
     * 调用代码
     *
     * @return string
     * @date 2023/6/1
     */
    private function callCode(): string
    {
        $params = $this->params;

        array_walk_recursive($params, function (&$value) {
            if (is_numeric($value) || ($value instanceof \Stringable && str_starts_with(get_class($value), 'Sc\\Util\\HtmlStructure\\Html\\Js'))){
                $value = Grammar::mark($value);
            }
        });

        foreach ($params as &$param) {
            $param = $this->valueParse($param);
        }

        $paramCode = implode('", "', $params);
        $paramCode = $paramCode ? "\"$paramCode\"" : '';

        return $this->name . '(' . $paramCode . ')';
    }

    /**
     * 定义代码
     *
     * @return string
     * @date 2023/6/1
     */
    private function defCode(): string
    {
        $templateReplace = [
            ':function' => $this->name === '=>' ? '' : (str_contains($this->code, 'await') ? 'async function' : 'function'),
            ':name'     => ($this->name && $this->name !== '=>') ? $this->name : '',
            ':params'   => implode(', ', $this->params),
            ':code'     => strtr($this->code, ["\r\n" => "\r\n    ", '\\"' => '"']),
            ':arrow'    => $this->name === '=>' ? '=>' : '',
        ];

        return trim(strtr(self::TEMPLATE, $templateReplace));
    }

    public function __toString(): string
    {
        return $this->toCode();
    }

    /**
     * @param mixed $value
     *
     * @return mixed|string
     */
    private function valueParse(mixed $value): mixed
    {
        return match (true) {
            is_array($value) ||
            $value instanceof \stdClass   => stripcslashes(Grammar::mark(json_encode($value, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT))),
            str_starts_with($value, '@')  => Grammar::mark(substr($value, 1)),
            is_bool($value)               => Grammar::mark($value ? 'true' : 'false'),
            default                       => $value
        };
    }
}