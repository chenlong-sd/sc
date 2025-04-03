<?php
/**
 * datetime: 2023/6/3 2:41
 **/

namespace Sc\Util\HtmlStructure\Form;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;

/**
 * 表单可用项目
 *
 * Class FormItem
 *
 * @method static FormItemHidden hidden(string $name = null)
 * @method static FormItemText text(string $name = null, ?string $label = null)
 * @method static FormItemNumber number(string $name = null, ?string $label = null)
 * @method static FormItemPassword password(string $name = null, ?string $label = null)
 * @method static FormItemSelect select(string $name = null, ?string $label = null)
 * @method static FormItemCheckbox checkbox(string $name = null, ?string $label = null)
 * @method static FormItemRadio radio(string $name = null, ?string $label = null)
 * @method static FormItemSubmit submit(string $submit = '提交', ?string $reset = '重置')
 * @method static FormItemEditor editor(string $name = null, ?string $label = null)
 * @method static FormItemTextarea textarea(string $name = null, ?string $label = null)
 * @method static FormItemSwitch switch(string $name = null, ?string $label = null)
 * @method static FormItemIcon icon(string $name = null, ?string $label = null)
 * @method static FormItemCascader cascader(string $name = null, ?string $label = null)
 * @method static FormItemInLine inLine(FormItemInterface ...$children)
 * @method static FormItemGroup group(FormItemInterface|string ...$children)
 * @method static FormItemTable table(string $name = null, ?string $label = null)
 * @method static FormItemDatetime datetime(string $name = null, ?string $label = null)
 * @method static FormItemUpload upload(string $name = null, ?string $label = null)
 * @method static FormItemCustomize customize(AbstractHtmlElement|string $element)
 *
 * @package Sc\Util\HtmlStructure\Form
 * @date    2023/6/3
 */
abstract class FormItem
{
    /**
     * @param string $name
     * @param array  $arguments
     *
     * @return mixed
     * @throws \Exception
     * @date 2023/6/7
     */
    public static function __callStatic(string $name, array $arguments)
    {
        $classname = self::class . ucfirst($name);

        if (!class_exists($classname)) {
            throw new \Exception(sprintf('表单类型 %s 不存在', $name));
        }

        return new $classname(...$arguments);
    }
}