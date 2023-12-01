<?php
/**
 * datetime: 2023/5/27 23:59
 **/

namespace Sc\Util\HtmlStructure\Theme\ElementUI;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlElement\ElementType\DoubleLabel;
use Sc\Util\HtmlElement\ElementType\FictitiousLabel;
use Sc\Util\HtmlElement\ElementType\TextCharacters;
use Sc\Util\HtmlStructure\Form;
use Sc\Util\HtmlStructure\Form\FormItemSubmit;
use Sc\Util\HtmlStructure\Html\Html;
use Sc\Util\HtmlStructure\Html\Js\Axios;
use Sc\Util\HtmlStructure\Html\Js\JsCode;
use Sc\Util\HtmlStructure\Html\Js\JsFunc;
use Sc\Util\HtmlStructure\Html\Js\Grammar;
use Sc\Util\HtmlStructure\Html\Js\JsIf;
use Sc\Util\HtmlStructure\Html\Js\JsVar;
use Sc\Util\HtmlStructure\Table;
use Sc\Util\HtmlStructure\Table\Column;
use Sc\Util\HtmlStructure\Theme\Interfaces\TableThemeInterface;
use Sc\Util\Tool;

/**
 * Class Table
 *
 * @package Sc\Util\HtmlStructure\Theme\ElementUI
 * @date    2023/5/28
 */
class TableTheme implements TableThemeInterface
{

    public function render(Table $table): AbstractHtmlElement
    {
        $el = El::double('el-table');

        $attrs = $table->getAttrs();
        if (empty($attrs['v-loading']) && is_string($table->getData())) {
            $attrs['v-loading'] = $table->getId() . 'Loading';
            Html::js()->vue->set( $table->getId() . 'Loading', false);
        }

        $dataVarName = $this->dataSet($table);

        if ($table->getId()) {
            $attrs['ref'] = $table->getId();
        }

        Html::css()->addCss('html,body{height: 100%}body{margin: 0 8px;padding-top: 8px;box-sizing: border-box;}');

        if (empty($attrs['header-cell-class-name'])) {
            Html::css()->addCss('.vue--table-header-center{text-align: center !important;}');
            $attrs['header-cell-class-name'] = 'vue--table-header-center';
        }
        if (empty($attrs['cell-class-name'])) {
            Html::css()->addCss('.vue--table-row-center{text-align: center !important;}');
            $attrs['cell-class-name'] = 'vue--table-row-center';
        }

        $attrs['style'] = ($attrs['style'] ?? '') . ';margin-top:5px';
        $el->setAttr(':data', $dataVarName);
        $el->setAttrs($attrs);

        $this->rowEventHandle($table);
        $headerEl = $this->headerEventHandle($table);

        $el->append(...array_map(fn($column) => $column->render('ElementUI'), $table->getColumns()));

        $this->selection($el);

        $pagination = $this->pagination($table);
        $search = $this->searchHandle($table);

        return El::fictitious()->append($search, $headerEl, $el, $pagination);
    }

    /**
     * 数据设置并返回数据变量名
     *
     * @param Table $table
     *
     * @return string
     * @date 2023/5/28
     */
    private function dataSet(Table $table): string
    {
        $id   = $table->getId();
        $data = $table->getData();
        $dataVarName = $id ?: Tool::random('table')->get();
        $table->setId($dataVarName);

        // 设置数据变量和数组总数变量
        Html::js()->vue->set($dataVarName, is_string($data) ? [] : $data);
        Html::js()->vue->set($dataVarName . 'Total', is_string($data) ? 0 : count($data));

        if (!is_string($data)) return $dataVarName;

        // 字符不是http开头
        if(!str_starts_with($data, 'http')) return $data;

        /**
         * 如果是字符串，且是http开头则识别为请求地址
         * 设置搜索参数数据:       dataVar + Search
         * 设置搜索地址获取method: dataVar + Url
         * 设置获取数据method:    dataVar + GetData
         *
         * *** 根据以上规则可在渲染完成后，重新设置对应代码，可替换 ***
         */
        Html::js()->vue->set($dataVarName . 'Search', new \stdClass());
        Html::js()->vue->addMethod($dataVarName . 'Url', [], "return '$data';");
        Html::js()->vue->set('urlSearch', '@location.search');
        Html::js()->vue->addMethod('getUrlSearch', [], JsCode::make(
            JsVar::def('urlSearch'),
            JsIf::when('this.urlSearch')
                ->then(JsCode::make(
                    JsVar::assign('urlSearch', '@this.urlSearch.substring(1)'),
                    JsVar::assign('this.urlSearch', "@this.urlSearch.replace(/global_search=.*&?/, '')"),
                ))->else(
                    JsVar::assign('urlSearch', '')
                ),
            JsCode::create('return urlSearch;')
        ));

        $query = [
            'search' => [
                "search"      => Grammar::mark("this.{$dataVarName}Search"),
                "searchType"  => Grammar::mark("this.{$dataVarName}SearchType"),
                "searchField" => Grammar::mark("this.{$dataVarName}SearchField"),
            ],
            'query' => Grammar::mark("this.getUrlSearch()")
        ];
        if ($table->isOpenPagination()) {
            $query['page']     = Grammar::mark("this.{$table->getId()}Page");
            $query['pageSize'] = Grammar::mark("this.{$table->getId()}PageSize");
        }
        Html::js()->vue->addMethod($dataVarName . 'GetData', [],
            JsCode::create(JsVar::assign($table->getId() . 'Loading', true))
                ->then(
                    Axios::get(
                        url: Grammar::mark("this.{$dataVarName}Url()"),
                        query: $query
                    )->then(JsFunc::arrow(["{ data }"], JsCode::make(
                        JsVar::assign("this.{$table->getId()}Loading", false),
                        JsIf::when('data.code === 200')
                            ->then(
                                JsVar::assign("this.{$dataVarName}", '@data.data.data'),
                                JsVar::assign("this.{$dataVarName}Total", '@data.data.total')
                            )->else(
                                "this.\$message.warning(data.msg)"
                            )
                    )))
                )
        );


        Html::js()->vue->event('created', "this.{$dataVarName}GetData();");

        return $dataVarName;
    }

    /**
     * 事件处理
     *
     * @param Table $table
     *
     * @return void
     */
    private function rowEventHandle(Table $table): void
    {
        /**
         * 让处理程序和事件 dom 关联
         */
        $eventHandlers = $table->getRowEvents();

        $eventLabels = $this->rowGroupEventHandle($table->getRowGroupEvents(), $eventHandlers);

        foreach ($eventHandlers as $name => ['el' => $el, 'handler' => $handler]) {
            $el = $this->getEl($el)->setAttr('link');

            $eventLabels[] = $el->setAttr('@click', sprintf("%s(@scope)", $name));

            Html::js()->vue->addMethod($name, ['scope'], JsCode::create(JsVar::def('row', '@scope.row'))->then($handler));
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
            $eventColumn = Column::event()->fixed();
            $table->addColumns($eventColumn);
        }

        $eventColumn->setFormat(El::fictitious()->append(...$eventLabels));
    }

    /**
     * @param Table $table
     *
     * @return AbstractHtmlElement
     */
    private function headerEventHandle(Table $table): AbstractHtmlElement
    {
        $left  = El::double('div');
        $right = El::double('div');
        $header = El::double('div')->setAttr('style', 'display:flex;justify-content: space-between;');
        foreach ($table->getHeaderEvents() as $name => ['el' => $el, 'handler' => $handler, 'position' => $position]) {
            $el = $this->getEl($el)->setAttr('bg');
            if ($el->getAttr('plain') === null && $el->getAttr('default') === null) {
                $el->setAttr('text');
            }

            $el->setAttr('@click', $name);

            $position === 'left' ? $left->append($el) : $right->append($el);

            $code = JsCode::create("let row,selection = this.{$table->getId()}Selection;");

            // 检查后续是否有使用 selection ,有的话则增加判断
            $handler = (string)$handler;
            if (str_contains($handler, 'selection')) {
                $code->thenIf('selection.length <= 0', 'return this.$message.error("请选择要操作的数据")');
            }
            Html::js()->vue->addMethod($name, [],
                $code->then($handler)
            );
        }

        return $header->append($left, $right);
    }

    /**
     * @param DoubleLabel $el
     *
     * @return void
     */
    private function selection(DoubleLabel $el): void
    {
        if (!$el->find('[type=selection]') || $el->hasAttr('@selection-change')) {
           return;
        }

        Html::js()->vue->set($el->getAttr('ref') . 'Selection', []);
        $el->setAttr('@selection-change', "(selection) => {$el->getAttr('ref')}Selection = selection");
    }

    /**
     * @param mixed $el
     *
     * @return mixed|DoubleLabel
     */
    private function getEl(mixed $el): mixed
    {
        if ((is_string($el) && !str_starts_with($el, '@')) || $el instanceof TextCharacters) {
            $el = El::double('el-button')
                ->setAttr('type', 'primary')
                ->setAttr(':underline', 'false')
                ->append($el);
        }

        if (is_string($el) && str_starts_with($el, '@')) {
            $bt   = explode('.', substr($el, 1));
            $type = $bt[0];
            if (count($bt) > 2) {
                $bt[1] = preg_replace('/(?<=\w)[A-Z]/', '-$0', $bt[1]);
                $icon  = El::double('el-icon')->append(El::double($bt[1]));
                $title = "&nbsp;" . $bt[2];
            } else {
                $title = $bt[1] ?? '';
                $icon  = '';
            }
            if (str_contains($title, '[')){
                preg_match("/([^\[]+)\[(.*)\]/", $title, $match);
                $title = $match[1];
                $theme = $match[2];
            }

            $el = El::double('el-button')
                ->setAttr('type', $type)
                ->append($icon)
                ->append($title);
            if (isset($theme)) {
                $el->setAttr($theme);
            }
        }
        return $el;
    }

    /**
     * @param array $rowGroupEvents
     * @param array $eventHandlers
     *
     * @return array
     */
    private function rowGroupEventHandle(array $rowGroupEvents, array &$eventHandlers): array
    {
        $groups = [];
        $newEventHandlers = $eventHandlers;
        foreach ($rowGroupEvents as $group => $title) {
            $handlers = 'Handlers' . substr($group, 0, 6);
            $el       = El::double('el-dropdown')->addClass('vue--dropdown')->addClass('vue--rowhandle')
                ->setAttr('trigger', 'click')
                ->setAttr('@command', $handlers);

            $handleEl = El::double('el-link')->setAttr('type', 'primary')->append($title)
                ->append(El::double('el-icon')->append(El::double('Arrow-Down')));

            $handleListEl = El::double('el-dropdown-menu');
            $handlerFun   = JsFunc::anonymous(['command']);

            foreach ($eventHandlers as $name => $handler) {
                if ($handler['group'] === $group) {
                    $command = substr(md5($handler['el']), 0, 6);
                    $handleListEl->append(
                        El::double('el-dropdown-item')
                            ->setAttr(':command', "{ @command: '$command', @scope: @scope }")
                            ->append(...$this->getEl($handler['el'])->getChildren())
                    );

                    Html::js()->vue->addMethod('Command' . $command, ['scope'], JsCode::create('let row = scope.row;')->then($handler['handler']));

                    $handlerFun->appendCode(
                        JsIf::when("command.command === '$command'")->then(
                            JsFunc::call("this.Command$command", '@command.scope')
                        )
                    );

                    unset($newEventHandlers[$name]);
                }
            }

            Html::css()->addCss('.vue--dropdown{
                cursor: pointer;
                color: var(--el-color-primary);
                align-items: center;
                display: inline-block;
                line-height: 23px;
            }');

            Html::js()->vue->addMethod($handlers, $handlerFun);

            $el->append($handleEl)->append(
                El::double('template')->setAttr('#dropdown')->append($handleListEl)
            );

            $groups[] = $el;
        }

        $eventHandlers = $newEventHandlers;

        return $groups;
    }

    private function pagination(Table $table): DoubleLabel|string
    {
        if (!$table->isOpenPagination()) {
            return '';
        }

        // 如需自定义，重新设置JS变量值就可以了

        Html::js()->vue->set("{$table->getId()}Page", 1);
        Html::js()->vue->set("{$table->getId()}PageSize", 20);

        $pagination = El::double('el-pagination')->setAttrs([
            'background'      => '',
            'layout'          => 'sizes, prev, pager, next, jumper, total',
            ':total'          => "{$table->getId()}Total",
            ':current-page'   => "{$table->getId()}Page",
            ':page-size'      => "{$table->getId()}PageSize",
            ':page-sizes'     => "[10, 15, 20, 50, 100, 200, 500, 1000]",
            "@current-change" => "{$table->getId()}PageChange",
            "@size-change"    => "{$table->getId()}SizeChange"
        ]);

        Html::css()->addCss('.el-pagination{margin-top: 10px}');

        Html::js()->vue->addMethod("{$table->getId()}PageChange", ['current'], JsCode::make(
            JsVar::assign("this.{$table->getId()}Page", '@current'),
            JsFunc::call("this.{$table->getId()}GetData")
        ));

        Html::js()->vue->addMethod("{$table->getId()}SizeChange", ['size'], JsCode::make(
            JsVar::assign("this.{$table->getId()}PageSize", '@size'),
            JsVar::assign("this.{$table->getId()}Page", 1),
            JsFunc::call("this.{$table->getId()}GetData")
        ));

        return $pagination;
    }

    private function searchHandle(Table $table): AbstractHtmlElement|string
    {
        if (!$searchItems = $table->getSearchForms()){
            return '';
        }
        $searchForms = array_column($searchItems, 'form');
        $searchTypes = array_column($searchItems, 'type');
        $searchFields = [];

        $searchType = array_combine(
            array_map(function (Form\FormItemInterface|Form\FormItemAttrGetter $item) use (&$searchFields){
                if (str_contains($name = $item->getName(), '.')){
                    $newName = Tool::random("searchF")->get();
                    $searchFields[$newName] = $name;
                    $item->setName($name = $newName);
                }

                return $name;
            }, $searchForms),
            $searchTypes
        );
        $searchType = array_filter($searchType, fn($v) => $v !== '=');

        Html::js()->vue->set("{$table->getId()}SearchType", $searchType);
        Html::js()->vue->set("{$table->getId()}SearchField", $searchFields);

        $searchForms[] = Form\FormItem::submit('搜索')->setSubmit(JsCode::make(
            JsVar::assign("this.{$table->getId()}Page", 1),
            JsVar::assign("this.{$table->getId()}SearchLoading", false),
            JsFunc::call("this.{$table->getId()}GetData")
        ));

        $form = Form::create($table->getId() . 'Search')->config(':inline', 'true');

        $form->addFormItems(...$searchForms);

        return $form->render('ElementUI');
    }
}