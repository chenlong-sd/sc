<?php
/**
 * datetime: 2023/5/27 23:59
 **/

namespace Sc\Util\HtmlStructure\Theme\Layui;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlElement\ElementType\TextCharacters;
use Sc\Util\HtmlStructure\Html\Html;
use Sc\Util\HtmlStructure\Html\Js\Axios;
use Sc\Util\HtmlStructure\Html\Js\JsCode;
use Sc\Util\HtmlStructure\Html\Js\JsFunc;
use Sc\Util\HtmlStructure\Html\Js\Grammar;
use Sc\Util\HtmlStructure\Html\Js\JsVar;
use Sc\Util\HtmlStructure\Table\Column;
use Sc\Util\HtmlStructure\Theme\Interfaces\TableThemeInterface;

/**
 * Class Table
 *
 * @package Sc\Util\HtmlStructure\Theme\Layui
 * @date    2023/5/28
 */
class TableTheme implements TableThemeInterface
{
    /**
     * @param \Sc\Util\HtmlStructure\Table $table
     *
     * @return AbstractHtmlElement
     * @date 2023/5/28
     */
    public function render(\Sc\Util\HtmlStructure\Table $table): AbstractHtmlElement
    {
        $id = $table->getId();
        if (!$id) {
            mt_srand();
            $id = "table" . mt_rand(1, 999);
            $table->setId($id);
        }

        $this->rowEventHandle($table);

        $this->tableRenderCode($table);

        return El::double('table')->addClass('layui-table')->setId($id);
    }

    /**
     * @param \Sc\Util\HtmlStructure\Table $table
     *
     * @date 2023/5/28
     */
    private function tableRenderCode(\Sc\Util\HtmlStructure\Table $table): void
    {
        $attrs = $table->getAttrs();

        // 如果是字符串则识别为请求后台
        if (is_string($table->getData())) {
            // 设置请求地址
            $attrs['url'] = ($table->getData() ?: Grammar::mark('location.href'));

            // 设置数据解析参数
            $attrs['parseData'] = JsFunc::arrow(['res'], <<<JS
                return {
                    code: res.code === 200 ? 0 : res.code,
                    msg : res.msg,
                    data: res.data.data,
                    count: res.data.total,
                };
            JS)->toCode();
        }else{
            $attrs['data'] = $table->getData();
        }

        $attrs['elem'] = "#{$table->getId()}";
        isset($attrs['url']) and $attrs['method'] = 'post';

        $columnConfig = $this->columnConfig($table->getColumns());
        $attrs['cols'] = [$columnConfig];

        Html::js()->defCodeBlock(sprintf("layui.table.render(%s)", json_encode($attrs, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)));
    }

    /**
     * @param array|Column[] $Columns
     *
     * @date 2023/5/28
     */
    private function columnConfig(array $Columns): array
    {
        $cols = [];
        foreach ($Columns as $column) {
            if ($column->getAttr('type')){
                $type = match ($column->getAttr('type')) {
                    'index'     => 'numbers',
                    'selection' => 'checkbox',
                    default     => 'normal'
                };

                $column->setAttr('type', $type);
            }

            if ($column->getFormat()){
                $column->setAttr('templet', "#" .$this->formatConfig($column->getFormat()));
            }

            $column->setAttr('field', $column->getAttr('prop'));
            $column->setAttr('title', $column->getAttr('label'));
            $cols[] = $column->getAttr();
        }

        return $cols;
    }

    /**
     * 模板设置
     *
     * @param \Stringable|string $format
     *
     * @return string
     * @date 2023/5/29
     */
    private function formatConfig(\Stringable|string $format): string
    {
        if (!$format instanceof AbstractHtmlElement){
            $format = El::fromCode($format);
        }

        $format->each(function (AbstractHtmlElement $element) {
            if ($element instanceof TextCharacters) {
                $element->setText(preg_replace_callback('/{{(.+)}}/', function ($match){
                    $new = preg_replace('/(((?<!@|\w|\.|\[\])[a-zA-Z]\w*).*?)+/', "d.$2", $match[1]);
                    $new = preg_replace('/@(\w)/', '$1', $new);

                    return sprintf("{{=%s}}", $new);
                }, $element->getText()));

                return;
            }

            $updateAttrs = $deleteAttrs = [];
            foreach ($element->getAttrs() as $attr => $value) {
                if ($attr === 'v-if'){
                    $element->before(sprintf("{{# if (%s) { }}", preg_replace('/@(\w)/', '$1', preg_replace('/(?<!@|\w|\.|\[\])[a-zA-Z]\w*/', "d.$0", $value))));
                    $element->after('{{# } }}');
                    $deleteAttrs[] = $attr;
                }else if ($attr === 'v-for') {
                    preg_match('/^\s*\(?\s*(?<item>\w+)\s*(,\s*(?<index>\w+)\s*)?(\)?\s*)?in\s+(?<list>[\w\.\[\]]+)$/',  $value, $match);
                    $element->before(sprintf("{{#  layui.each(d.%s, function(%s, %s){ { }}", $match['list'], $match['index'], $match['item']));
                    $element->after('{{# }) }}');
                    $deleteAttrs[] = $attr;
                }else if (str_starts_with($attr, ':')){
                    $updateAttrs[substr($attr, 1)] = sprintf("{{= %s }}", preg_replace('/@(\w)/', '$1', preg_replace('/(?<!@|\w|\.|\[\])[a-zA-Z]\w*/', "d.$0", $value)));
                    $deleteAttrs[] = $attr;
                }else if(str_starts_with($attr, '@')){
                    $deleteAttrs[] = $attr;
                    if (!preg_match('/^\w+(\(.*\))?$/', $value)){
                        $updateAttrs["on" . substr($attr, 1)] = preg_replace('/@(\w)/', '$1', preg_replace('/(?<!@|\w|\.|\[\])[a-zA-Z]\w*/', "{{= d.$0 }}", $value));
                    }else{
                        $updateAttrs["on" . substr($attr, 1)] = preg_replace_callback('/^(\w+)(\(.*\))?$/', function ($match) {
                            if (empty($match[2])){
                                return $match[1];
                            }
                            $param = preg_replace('/(?<![@\w\.\[\]])[\w\.\[\]]+/', '{{= d.$0 }}', $match[2]);
                            $param = preg_replace('/@(\w)/', '{{= $1 }}', $param);

                            return $match[1] . $param;
                        }, $value);
                    }
                }
            }
            foreach ($deleteAttrs as $attr) {
                $element->setAttr($attr, null);
            }

            $element->setAttrs($updateAttrs);
        });

        mt_srand();
        $id = "Format" . mt_rand(1, 999);

        $format = El::double('script')->setId($id)->setAttr('type', 'text/html')->append($format);

        Html::html()->find('body')->after($format);

        return $id;
    }

    /**
     * 行事件处理
     *
     * @param \Sc\Util\HtmlStructure\Table $table
     *
     * @date 2023/6/1
     */
    private function rowEventHandle(\Sc\Util\HtmlStructure\Table $table): void
    {
        /**
         * 让处理程序和事件 dom 关联
         */
        $eventLabels = [];
        foreach ($table->getRowEvents() as $name => ['el' => $el, 'handler' => $handler]) {
            if (is_string($el) || $el instanceof TextCharacters) {
                $el = El::double('button')
                    ->addClass('layui-btn layui-btn-normal layui-btn-xs')
                    ->append($el);
            }
            $eventLabels[] = $el->setAttr('@click', sprintf("%s(LAY_INDEX)", $name));

            $row = JsVar::def('row',
                JsFunc::call('layui.table.getData', $table->getId())->index('@index')
            );
            Html::js()->defFunc($name, ['index'], JsCode::create($row)->then($handler));
        }
        if (!$eventLabels) {
            return;
        }

        /**
         * 查找 event 列，没有则添加
         * 然后添加事件 dom
         */
        $events = array_filter($table->getColumns(), function (Column $column) {
            return $column->getAttr('mark-event');
        });
        if (!$eventColumn = current($events)) {
            $eventColumn = Column::event();
            $table->addColumns($eventColumn);
        }

        $eventColumn->setFormat(El::fictitious()->append(...$eventLabels));
    }
}