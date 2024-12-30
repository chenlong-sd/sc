<?php

namespace Sc\Util\HtmlStructure\Form\ItemAttrs;

use JetBrains\PhpStorm\ExpectedValues;
use JetBrains\PhpStorm\Language;
use Sc\Util\HtmlStructure\Form\FormItemAttrGetter;
use Sc\Util\HtmlStructure\Form\FormItemText;
use Sc\Util\HtmlStructure\Html\Html;
use Sc\Util\HtmlStructure\Html\Js\Grammar;
use Sc\Util\HtmlStructure\Html\Js\JsCode;
use Sc\Util\HtmlStructure\Html\Js\JsFunc;
use Sc\Util\HtmlStructure\Html\Js\JsIf;
use Sc\Util\HtmlStructure\Html\Js\JsLog;

/**
 * Class Validate
 */
trait Validate
{
    protected array $rules = [];

    /**
     * @param string|null $message
     *
     * @return $this
     */
    public function requiredVerify(string $message = null, string|array $trigger = ['change', 'blur']): static
    {
        if ($message === null){
            $message = $this->getLabel() . "不能为空";
        }

        return $this->addRule(['required' => true], $message, $trigger);
    }

    /**
     * @param string       $when  变量需使用全局变量 VueApp ，例：VueApp.user.id === 1
     * @param string|null  $message
     * @param string|array $trigger
     *
     * @return $this
     */
    public function requiredVerifyWhen(#[Language('JavaScript')]string $when, string $message = null, string|array $trigger = ['change', 'blur']): static
    {
        return $this->customizeVerify(JsFunc::anonymous(['rule', 'value', 'callback'])->code(
            JsIf::when("!value && $when")->then(
                JsFunc::call("callback", $message ?: $this->getLabel() . "不能为空")
            )->else(
                JsFunc::call("callback")
            )
        ), $message, $trigger);
    }

    /**
     * @param int          $min
     * @param int          $max
     * @param string|null  $message
     * @param string|array $trigger
     *
     * @return $this
     */
    public function lengthRangeVerify(int $min, int $max, string $message = null, string|array $trigger = ['change', 'blur']): static
    {
        if ($message === null){
            $message = $this->getLabel() . sprintf("的长度必须再 %d - %d 之间", $min, $max);
        }

        return $this->addRule(['min' => $min, "max" => $max], $message, $trigger);
    }

    /**
     * @param string       $type
     * @param string|null  $message
     * @param string|array $trigger
     *
     * @return $this
     */
    public function typeVerify(#[ExpectedValues([
        'string', 'number', 'boolean', 'method', 'regexp', 'integer', 'float', 'array', 'object', 'enum', 'date', 'url', 'hex', 'email', 'any',])
                               ] string $type, string $message = null, string|array $trigger = ['change', 'blur']): static
    {
        if (($type == 'integer') && $this instanceof FormItemText) {
            return $this->patternVerify('/^\-?\d+$/');
        }

        if (($type == 'number') && $this instanceof FormItemText) {
            return $this->patternVerify('/^\-?\d+(\.\d+)?$/');
        }

        if ($message === null) {
            $message = "请输入合法的" . $this->getLabel();
        }

        return $this->addRule(['type' => $type,], $message, $trigger);
    }

    /**
     * @param string|null  $message
     * @param string|array $trigger
     *
     * @return $this
     */
    public function phoneVerify(string $message = null, string|array $trigger = ['change', 'blur']): static
    {
        return $this->patternVerify('/^1[3456789]\d{9}$/', $message, $trigger);
    }

    /**
     * @param string       $pattern
     * @param string|null  $message
     * @param string|array $trigger
     *
     * @return $this
     */
    public function patternVerify(string $pattern, string $message = null, string|array $trigger = ['change', 'blur']): static
    {
        if ($message === null) {
            $message = $this->getLabel() . "不合法";
        }

        return $this->addRule(['pattern' => Grammar::mark($pattern)], $message, $trigger);
    }

    /**
     * 数字范围验证
     *
     * @param float|int    $min
     * @param float|int    $max
     * @param string|null  $message
     * @param string|array $trigger
     *
     * @return $this
     */
    public function numberRangeVerify(float|int $min, float|int $max, string $message = null, string|array $trigger = ['change', 'blur']): static
    {
        if ($message === null) {
            $message = $this->getLabel() . "的大小必须在 $min - $max 之间";
        }

        $this->typeVerify('number', null, $trigger);

        $this->customizeVerify(JsFunc::anonymous(['rule', 'value', 'callback'])->code(
            JsIf::when("value >= $min && value <= $max")
                ->then("return callback();")
                ->else("return callback(new Error('$message'));"),
        ), $message, $trigger);

        return $this;
    }

    /**
     * @param array        $enums
     * @param string|null  $message
     * @param string|array $trigger
     *
     * @return $this
     */
    public function enumVerify(array $enums, string $message = null, string|array $trigger = ['change', 'blur']): static
    {
        if ($message === null) {
            $message = $this->getLabel() . sprintf("须在 %s 之中", implode(',', $enums));
        }

        return $this->addRule(['type' => 'enum', "enums" => $enums], $message, $trigger);
    }

    /**
     * @param int          $length
     * @param string|null  $message
     * @param string|array $trigger
     *
     * @return $this
     */
    public function lengthVerify(int $length, string $message = null, string|array $trigger = ['change', 'blur']): static
    {
        if ($message === null) {
            $message = $this->getLabel() . sprintf("长度限制为 %d", $length);
        }

        return $this->addRule(['max' => $length], $message, $trigger);
    }

    /**
     * @param string|null $message
     *
     * @return $this
     */
    public function whitespaceVerify(string $message = null, string|array $trigger = ['change', 'blur']): static
    {
        if ($message === null) {
            $message = $this->getLabel() . '不能为空';
        }

        return $this->addRule(['whitespace' => true], $message, $trigger);
    }

    /**
     * @param JsFunc|string $func 参数 rule: any, value: any, callback: any
     *
     * @return $this
     */
    public function customizeVerify(JsFunc|string $func, string $message = null, string|array $trigger = ['change', 'blur']): static
    {
        if ($func instanceof JsFunc) {
            Html::js()->defFunc($this->name . "validator", $func->params, $func->code);
            $func = Grammar::mark($this->name . "validator");
        }

        return $this->addRule(['validator' => $func], $message, $trigger);
    }

    /**
     * @param array        $rules
     * @param string|null  $message
     * @param string|array $trigger
     *
     * @return $this
     */
    private function addRule(array $rules, ?string $message = null, string|array $trigger = ['change', 'blur']): static
    {
        if ($message !== null) {
            $rules['message'] = $message;
        }
        $rules['trigger'] = $trigger;

        $this->rules[] = $rules;

        return $this;
    }
}