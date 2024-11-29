<?php
/**
 * datetime: 2023/5/27 23:39
 **/

namespace Sc\Util\HtmlStructure\Theme\ElementUI;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlElement\ElementType\DoubleLabel;
use Sc\Util\HtmlElement\ElementType\TextCharacters;
use Sc\Util\HtmlStructure\Form\FormItem;
use Sc\Util\HtmlStructure\Html\Html;
use Sc\Util\HtmlStructure\Html\Js;
use Sc\Util\HtmlStructure\Html\Js\Axios;
use Sc\Util\HtmlStructure\Html\Js\JsFunc;
use Sc\Util\HtmlStructure\Html\Js\JsService;
use Sc\Util\HtmlStructure\Html\Js\Window;
use Sc\Util\HtmlStructure\Table\Column;
use Sc\Util\HtmlStructure\Theme\Interfaces\TableColumnThemeInterface;

/**
 * Class TableColumn
 *
 * @package Sc\Util\HtmlStructure\Theme\ElementUI
 * @date    2023/5/27
 */
class TableColumnTheme implements TableColumnThemeInterface
{
    /**
     * @param Column $column
     *
     * @return AbstractHtmlElement
     * @date 2023/5/27
     */
    public function render(Column $column): AbstractHtmlElement
    {
        $columnEl = El::double('el-table-column');
        if ($column->getFixedPosition()) {
            $column->setAttr('fixed', $column->getFixedPosition());
        }
        if ($column->getSortField()) {
            $column->setAttr('sortable', "custom");
        }

        if ($show = $column->getShow()) {
            match ($show['type']) {
                'switch'   => $this->switchHandle($column, $show['config']),
                'tag'      => $this->tagHandle($column, $show['config']),
                'image'    => $this->imageHandle($column),
                'mapping'  => $this->mappingHandle($column, $show['config']),
                'openPage' => $this->openPageHandle($column, $show['config']),
            };
        }

        $this->formatConfig($columnEl, $column->getFormat());
        $this->emptyValueHandle($column->getEmptyShowTemplate(), $columnEl, $column);
        $this->attrHandle($column->getAttr(), $columnEl);
        $this->tipHandle($column->getTip(), $columnEl);

        return $columnEl;
    }

    /**
     * 显示格式化处理
     *
     * @param AbstractHtmlElement $columnEl
     * @param mixed               $format
     *
     * @return void
     */
    private function formatConfig(AbstractHtmlElement $columnEl, mixed $format): void
    {
        if (!$format) return;

        $columnEl->append(El::double('template')->setAttr('#default', 'scope')->append($format));

        $columnEl->each(function (AbstractHtmlElement $currentColumn) {
            if ($currentColumn instanceof TextCharacters) {
                $currentColumn->setText(preg_replace_callback('/{{(.+)}}/', function ($match){
                    $new = preg_replace('/(((?<!@|\w|\.|\[\])[a-zA-Z]\w*).*?)+/', "scope.row.$2", $match[1]);
                    $new = preg_replace('/@(\w)/', '$1', $new);
                    $new = preg_replace_callback('/(?<y>[\'"])(?<c>.*?)\k<y>/', function ($match){
                        return $match['y'] . strtr($match['c'], ['scope.row.' => '']) . $match['y'];
                    }, $new);
                    return sprintf("{{%s}}", $new);
                }, $currentColumn->getText()));
                return;
            }

            $updateAttrs = [];
            foreach ($currentColumn->getAttrs() as $attr => $value) {
                if ($attr === 'v-for') {
                    $updateAttrs[$attr] = preg_replace_callback('/^.*?(\w+)(.*?\w+)?.*\s+in\s+([\w\.\[\]]+)$/',  function ($match){
                        if ($match[2]) {
                            return "(" . $match[1] . $match[2] . ") in scope.row." . $match[3];
                        }
                        return $match[1] . ' in scope.row.' . $match[3];
                    }, $value);
                    $updateAttrs[$attr] = strtr($updateAttrs[$attr], ['@' => '']);
                }else if (preg_match('/^[v:]/', $attr)){
                    $updateAttrs[$attr] = preg_replace('/@(\w)/', '$1', preg_replace('/(?<!@|\w|\.|\[\])[a-zA-Z]\w*/', "scope.row.$0", $value));
                }else if(str_starts_with($attr, '@')){
                    if (!preg_match('/^\w+(\((@?\w+[\s,]*)*\))?$/', $value)){
                        $updateAttrs[$attr] = preg_replace('/@(\w)/', '$1', preg_replace('/(?<!@|\w|\.|\[\])[a-zA-Z]\w*/', "scope.row.$0", $value));
                    }else{
                        $updateAttrs[$attr] = preg_replace_callback('/^(\w+)(\((@?\w+[\s,]*)*\))?$/', function ($match) {
                            if (empty($match[2])){
                                return $match[1];
                            }
                            $param = preg_replace('/(?<![@\w\.\[\]])[\w\.]+/', 'scope.row.$0', $match[2]);
                            $param = preg_replace('/@(\w)/', '$1', $param);

                            return $match[1] . $param;
                        }, $value);
                    }
                }
            }

            $updateAttrs and $currentColumn->setAttrs($updateAttrs);
        });
    }

    /**
     * 基础属性处理
     *
     * @param array       $attrs
     * @param DoubleLabel $columnEl
     *
     * @return void
     */
    private function attrHandle(array $attrs, AbstractHtmlElement $columnEl): void
    {
        foreach ($attrs as $attr => $value) {
            if (is_bool($value)) $value = $value ? 'true' : 'false';
            elseif ($value instanceof JsFunc) {
                mt_srand();
                $methodName = mt_rand(1, 999);
                Html::js()->vue->addMethod($attr . $methodName, $value);
                $value = $methodName;
            }

            $columnEl->setAttr($attr, $value);
        }

        if (empty($attrs['mark-event']) && array_intersect(array_keys($attrs), ['width', ':width']) && !array_intersect(array_keys($attrs), ['show-overflow-tooltip', ':show-overflow-tooltip'])) {
            $columnEl->setAttr(':show-overflow-tooltip', 'true');
        }
    }

    private function switchHandle(Column $column, array $switch): void
    {
        ['url' => $requestUrl, 'openValue' => $openValue, 'options' => $options,] = $switch;

        $prop   = $column->getAttr('prop');
        $format = FormItem::switch($prop)->options($options)
            ->setOpenValue($openValue)
            ->setVAttrs('@change', "@{$prop}switchChange(@scope)")
            ->render()->find('el-switch');

        $value1 = $format->getAttr(':active-value');
        $value2 = $format->getAttr(':inactive-value');
        $failHandle = Js::code(Js::assign("scope.row.$prop", "@scope.row.$prop === var1 ? var2 : var1"));

        Html::js()->vue->addMethod("{$prop}switchChange", ['scope'],
            Js::code(
                Js::let('var1', $value1),
                Js::let('var2', $value2),
                Axios::post($requestUrl, [
                    'id'  => Js::grammar('scope.row.id'),
                    $prop => Js::grammar('scope.row.' . $prop)
                ])->then(JsFunc::arrow(['{ data }'])->code(
                    Js::if('data.code !== 200', (clone $failHandle)->then(JsService::message(Js::grammar("data.msg"), 'error')))
                ))->catch(JsFunc::arrow()->code(
                    $failHandle->then(JsService::message("操作失败", 'error'))
                ))
            )
        );

        $column->setFormat($format);
    }

    private function tagHandle(Column $column, mixed $config): void
    {
        $f = El::fictitious();
        foreach ($config['options'] as $value => $option) {
            if ($f->getChildren()) {
                $f->append(El::get($option)->setAttr('v-else-if', "@$value == {$column->getAttr('prop')}"));
            }else{
                $f->append(El::get($option)->setAttr('v-if', "@$value == {$column->getAttr('prop')}"));
            }
        }
        $f->append(El::double("el-text")->setAttr('v-else')->append('——'));
        $column->setFormat($f);
    }

    private function imageHandle(Column $column): void
    {
        $column->setFormat(
            El::double('el-image')->setAttrs([
                'style'              => 'height:60px',
                ":src"               => $column->getAttr('prop'),
                ":preview-src-list"  => "[ {$column->getAttr('prop')} ]",
                'fit'                => 'scale-down',
                ':preview-teleported' => '@true',
                'v-if'               => "{$column->getAttr('prop')}?.length > 0"
            ])
        );
    }

    private function mappingHandle(Column $column, array $config): void
    {
        if (count($config['options']) === count($config['options'], COUNT_RECURSIVE)) {
            $config['options'] = kv_to_form_options($config['options']);
        }

        $mappingName = $column->getAttr('prop') . "Mapping";
        Html::js()->vue->set($mappingName, $config['options']);
        $column->setFormat(El::fictitious()->append(
            El::double('span')
                ->setAttr('v-if', sprintf("@typeof %s != '@object'", $column->getAttr('prop')))
                ->setAttr('v-for', "(item, index) in @$mappingName")
                ->append(
                    El::double('el-text')->append(
                        El::double('span')
                            ->setAttr('v-if', '@item.value == ' . $column->getAttr('prop'))
                            ->append("{{ @item.label }}")
                    ),
                ),
            El::double('span')->setAttr('v-else')
                ->append("{{ {$column->getAttr('prop')} ? {$column->getAttr('prop')}.map(@v => @$mappingName.filter(@vf => @vf.value == @v)[0].label).join(',') : '' }}")
        ));
    }

    private function openPageHandle(Column $column, array $config): void
    {
        if (!$element = $config['element']) {
            $element = El::double('el-link')->setAttrs([
                'type' => 'primary',
            ])->append("{{ {$column->getAttr('prop')} }}");
        }

        $method = "openPage" . $column->getAttr('prop');
        $element->setAttrIfNotExist('@click', "@$method(@scope)");

        $column->setFormat($element);

        Html::js()->vue->addMethod($method, JsFunc::anonymous(['scope'])->code(
            Js::let('row', '@scope.row'),
            Window::open("查看【{{$column->getAttr('prop')}}】详情")
                ->setConfig($config['config'])
                ->setUrl($config['url'], [
                    'id' => '@id',
                    $column->getAttr('prop') => "@{$column->getAttr('prop')}",
                ])
        ));
    }

    private function emptyValueHandle(\Stringable|string $getEmptyShowTemplate, DoubleLabel $columnEl, Column $column): void
    {
        if (!$getEmptyShowTemplate) {
            return;
        }

        $getEmptyShowTemplate = El::get($getEmptyShowTemplate);

        $template = $columnEl->find('template');
        if (!$template) {
            $template = El::double('template')->setAttr('#default', 'scope')->append("{{ scope.row.{$column->getAttr('prop')} }}");
            $columnEl->append($template);
        }

        $content = $template->getChildren();

        $template->setChildren([
            El::double('template')->setAttr('v-if', "scope.row.{$column->getAttr('prop')}")->setChildren($content),
            El::double('template')->setAttr('v-else')->append(
                $getEmptyShowTemplate instanceof TextCharacters
                    ? El::double('el-text')->append($getEmptyShowTemplate)
                    : $getEmptyShowTemplate
            )
        ]);
    }

    private function tipHandle(array $tip, DoubleLabel $columnEl): void
    {
        if (empty($tip)) return;
        $template = $columnEl->getChildrenByIndex(0);
        if (!$template instanceof DoubleLabel || !$columnEl->getChildrenByIndex(0)->hasAttr('#default')){
            $template = El::template("{{ scope.row.{$columnEl->getAttr('prop')} }}")->setAttr('#default', 'scope');
        }

        $icon = El::get($tip['icon']);
        if ($icon instanceof TextCharacters) {
            $icon = El::double('el-icon')->append(El::double(preg_replace('/([a-z])([A-Z])/', '$1-$2', $tip['icon'])));
        }
        $icon->appendStyle("{cursor:pointer;}");
        if ($icon->getLabel() === 'el-icon' && $iconLabel = $icon->getChildrenByIndex(0)?->getLabel()) {
            $icon->getChildrenByIndex(0)->setLabel(preg_replace('/([a-z])([A-Z])/', '$1-$2', $iconLabel));
        }

        $icon = El::template($icon)->setAttr('#reference');
        $template->append(
            El::double('el-popover')->setAttrs([
                'trigger' => 'click',
                ...$tip['attrs']
            ])->append($icon)
            ->append($tip['tip'] ?? '无')
        );

        $columnEl->setChildren($template);
    }
}