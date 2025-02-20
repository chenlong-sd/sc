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
use Sc\Util\HtmlStructure\Html\Html;
use Sc\Util\HtmlStructure\Html\Js;
use Sc\Util\HtmlStructure\Html\Js\Axios;
use Sc\Util\HtmlStructure\Html\Js\JsCode;
use Sc\Util\HtmlStructure\Html\Js\JsFunc;
use Sc\Util\HtmlStructure\Html\Js\JsService;
use Sc\Util\HtmlStructure\Html\StaticResource;
use Sc\Util\HtmlStructure\Table;
use Sc\Util\HtmlStructure\Table\Column;
use Sc\Util\HtmlStructure\Theme\Interfaces\TableThemeInterface;
use Sc\Util\ScTool;
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

        if (empty($attrs['header-cell-class-name'])) {
            Html::css()->addCss('.vue--table-header-center{text-align: center !important;}');
            $attrs['header-cell-class-name'] = 'vue--table-header-center';
        }
        if (empty($attrs['cell-class-name'])) {
            Html::css()->addCss('.el-table .el-table__cell.vue--table-row-center{text-align: center;}');
            $attrs['cell-class-name'] = 'vue--table-row-center';
        }

        $attrs['style'] = ($attrs['style'] ?? '') . ';margin-top:5px';
        if ($sortMethod = $this->sortHandle($table)) {
            $attrs['@sort-change'] = $sortMethod;
        }

        $this->heightRestrictions($table, $attrs);

        $attrs = array_merge($attrs, ['highlight-current-row' => '']);

        $el->setAttr(':data', $dataVarName);
        $el->setAttrs($attrs);

        $this->rowEventHandle($table);
        $headerEl = $this->headerEventHandle($table);

        $el->append(...array_map(fn($column) => $column->render('ElementUI'), $table->getColumns()));

        $this->trashHandle($table, $el, $headerEl);

        $this->selection($el);

        $pagination = $this->pagination($table);
        $search = $this->searchHandle($table);

        return El::fictitious()->append($search, $headerEl, $el, $pagination);
    }

    private function drawHandle(Table $table): void
    {
        $draw = $table->getDraw();
        if (!$draw['able']) {
            return;
        }

        if (empty($table->getAttrs()['row-key'])) {
            throw new \Exception("请设置表格的row-key属性，以保证数据渲染正确性");
        }

        $table->setRowEvent(is_string($draw['el']) ? [$draw['el'], ['class' => 'sc-ft-draw']] : $draw['el']->addClass('sc-ft-draw'), " ");

        $updateHandle = is_array($draw['updateHandle'])
            ? $draw['updateHandle'][0]
            : Js::code(
                Js::code("const currRow = this.{$table->getId()}.splice(evt.oldIndex, 1)[0];"),
                Js::code("this.{$table->getId()}.splice(evt.newIndex, 0, currRow)"),
                JsFunc::call('setTimeout', JsFunc::arrow()->code(
                    $draw['updateHandle']
                ))
            );

        $initSortMethodName = "{$table->getId()}InitSort";
        Html::js()->load(StaticResource::SORT_ABLE_JS);
        Html::js()->vue->addMethod($initSortMethodName, JsFunc::anonymous()->code(
            Js::let("ElDraw{$table->getId()}", "@this.\$refs['{$table->getId()}'].\$el.querySelectorAll('table > tbody')[0]"),
            JsFunc::call('new Sortable', "@ElDraw{$table->getId()}", [
                "handle"    => ".sc-ft-draw",
                "animation" => 150,
                'onUpdate'  => JsFunc::arrow(['evt'])->code(
                    $updateHandle
                ),
                ...$draw['config']
            ])
        ));

        Html::js()->vue->event('mounted', Js::code(
            Js::code("this.$initSortMethodName()")
        ));
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

        if (is_array($data)) {
            Html::js()->vue->addMethod($dataVarName . 'GetData', ['query'],
                $this->dataGet($dataVarName, [])
            );
        }else{
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
            Html::js()->vue->addMethod('getUrlSearch', [], Js::code(
                Js::let('urlSearch'),
                Js::if('this.urlSearch')
                    ->then(Js::code(
                        Js::assign('urlSearch', '@this.urlSearch.substring(1)'),
                        Js::assign('this.urlSearch', "@this.urlSearch.replace(/global_search=.*&?/, '')"),
                    ))->else(
                        Js::assign('urlSearch', '')
                    ),
                Js::code('return urlSearch;')
            ));

            $query = [
                'search' => [
                    "search"      => Js::grammar("this.{$dataVarName}Search"),
                    "searchType"  => Js::grammar("this.{$dataVarName}SearchType"),
                    "searchField" => Js::grammar("this.{$dataVarName}SearchField"),
                ],
                'order' => Js::grammar("this.{$dataVarName}Sort"),
                'query' => ScTool::url($data)->getQueryParam('query', '') ?: Js::grammar("this.getUrlSearch()")
            ];

            $query["temp"] = "@query";
            if ($table->isOpenExportExcel()) {
                Html::js()->vue->addMethod($dataVarName . 'ExportData', $this->exportDataGet($table, $dataVarName, $query));
            }

            if ($table->isOpenPagination()) {
                $query['page']     = Js::grammar("this.{$dataVarName}Page");
                $query['pageSize'] = Js::grammar("this.{$dataVarName}PageSize");
            }

            Html::js()->vue->addMethod($dataVarName . 'GetData', ['query', 'notLoading'], $this->remoteDataGet($table, $dataVarName, $query));
        }

        Html::js()->vue->event('created', "this.{$dataVarName}GetData();");

        return $dataVarName;
    }

    /**
     * 事件处理
     *
     * @param Table $table
     *
     * @return void
     * @throws \Exception
     */
    private function rowEventHandle(Table $table): void
    {
        $this->drawHandle($table);

        /**
         * 让处理程序和事件 dom 关联
         */
        $eventHandlers = $table->getRowEvents();
        $eventLabels   = [];
        $groupEventLabels = $this->rowGroupEventHandle($table->getRowGroupEvents(), $eventHandlers);

        foreach ($eventHandlers as $name => ['el' => $el, 'handler' => $handler]) {
            $el = $this->getEl($el)->setAttr('link');

            $eventLabels[] = $el->setAttr('@click', sprintf("%s(@scope)", $name));

            Html::js()->vue->addMethod($name, ['scope'], Js::code(Js::let('row', '@scope.row'))->then($handler));
        }
        $eventLabels = array_merge($eventLabels, $groupEventLabels);
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
        $headerBox = El::fictitious();

        if ($table->getTrash() && is_string($table->getData())) {
            $table->setHeaderRightEvent(["@danger.delete.回收站", ['v-if' => 'is_super']], function () use ($table){
                return Table\EventHandler::window("回收站")->setUrl(
                    "@location.href", ['is_delete' => 1]
                );
            });
        }
        if ($table->isOpenExportExcel()) {
            $table->setHeaderRightEvent(["@primary.TakeawayBox.导出"], function () use ($table){
                return Js::code("this.{$table->getId()}ExportData()");
            });
        }

        $statusToggleButtons = $this->statusToggleButtonsHandle($table);
        if (!$statusToggleButtons->isEmpty()) {
            $headerBox->append($statusToggleButtons);
        }

        foreach ($table->getHeaderEvents() as $name => ['el' => $el, 'handler' => $handler, 'position' => $position]) {
            $el = $this->getEl($el)->setAttr('bg');
            if ($el->getAttr('plain') === null && $el->getAttr('default') === null) {
                $el->setAttr('text');
            }

            $el->setAttr('@click', $name);

            $position === 'left' ? $left->append($el) : $right->append($el);

            $code = Js::code("let row,selection = this.{$table->getId()}Selection;");

            // 检查后续是否有使用 selection ,有的话则增加判断
            $handler = (string)$handler;
            if (str_contains($handler, 'selection')) {
                $code->thenIf('selection.length <= 0', 'return this.$message.error("请选择要操作的数据")');
            }
            Html::js()->vue->addMethod($name, [],
                $code->then($handler)
            );
        }

        return $headerBox->append($header->append($left, $right));
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
        $attr = [];
        if (is_array($el)) {
            $attr  = $el[1] ?? [];
            $el    = $el[0];
        }

        if (is_string($el) && str_starts_with($el, '@')) {
            $bt   = explode('.', substr($el, 1));
            $type = $bt[0];
            if (count($bt) > 2) {
                $bt[1] = preg_replace('/(?<=\w)[A-Z]/', '-$0', $bt[1]);
                $icon  = $bt[1];
                $title = $bt[2];
            } else {
                $title = $bt[1] ?? '';
                $icon  = null;
            }
            if (str_contains($title, '[')){
                preg_match("/([^\[]+)\[(.*)\]/", $title, $match);
                $title = $match[1];
                $theme = $match[2];
            }

            $attr = array_merge(['type' => $type, 'icon' => $icon], $attr);

            $el = El::double('el-button')->setAttrs($attr)->append($title);
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

                    Html::js()->vue->addMethod('Command' . $command, ['scope'], Js::code('let row = scope.row;')->then($handler['handler']));

                    $handlerFun->appendCode(
                        Js::if("command.command === '$command'")->then(
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
                margin: 0 5px;
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
        Html::js()->vue->set("{$table->getId()}PageSize", 50);

        $pagination = El::double('el-pagination')->setAttrs([
            'background'      => '',
            'layout'          => 'sizes, prev, pager, next, jumper, total',
            ':total'          => "{$table->getId()}Total",
            ':current-page'   => "{$table->getId()}Page",
            ':page-size'      => "{$table->getId()}PageSize",
            ':page-sizes'     => "[10, 15, 20, 50, 100, 200, 500, 1000]",
            "@current-change" => "{$table->getId()}PageChange",
            "@size-change"    => "{$table->getId()}SizeChange",
        ]);

        Html::css()->addCss('.el-pagination{margin-top: 10px}');

        Html::js()->vue->addMethod("{$table->getId()}PageChange", ['current'], Js::code(
            Js::assign("this.{$table->getId()}Page", '@current'),
            JsFunc::call("this.{$table->getId()}GetData")
        ));

        Html::js()->vue->addMethod("{$table->getId()}SizeChange", ['size'], Js::code(
            Js::assign("this.{$table->getId()}PageSize", '@size'),
            Js::assign("this.{$table->getId()}Page", 1),
            JsFunc::call("this.{$table->getId()}GetData")
        ));

        return $pagination;
    }

    /**
     * 高度限制
     *
     * @param Table $table
     * @param array $attrs
     *
     * @return void
     */
    private function heightRestrictions(Table $table, array &$attrs): void
    {
        if (!$table->getMaxHeight()) {
            return;
        }

        $heightName = "maxHeight{$table->getId()}";
        $attrs[":max-height"] = $heightName;
        Html::js()->vue->set($heightName, max($table->getMaxHeight(), 0));
        Html::js()->vue->set("vueWindowHeight", "@window.innerHeight");

        if ($table->getMaxHeight() < 0) {
            Html::js()->vue->event('mounted', Js::code(
                JsFunc::call('setTimeout', JsFunc::arrow()->code(
                    Js::assign("this." . $heightName, "@this.vueWindowHeight - this.\$refs.{$table->getId()}.\$el.getBoundingClientRect().top " . $table->getMaxHeight())),
                )
            ));
        }
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

        $searchForms[] = Form\FormItem::submit('搜索')->setSubmit(Js::code(
            Js::assign("this.{$table->getId()}Page", 1),
            Js::assign("this.{$table->getId()}SearchLoading", false),
            JsFunc::call("this.{$table->getId()}GetData")
        ));

        $form = Form::create($table->getId() . 'Search')->config(':inline', 'true');

        $form->addFormItems(...$searchForms);

        return $form->render('ElementUI');
    }

    /**
     * @param Table $table
     *
     * @return FictitiousLabel
     */
    private function statusToggleButtonsHandle(Table $table): FictitiousLabel
    {
        $el = El::fictitious();

        foreach ($table->getStatusToggleButtons() as $index => $toggleButton) {
            $status = El::double('el-button-group')->addClass('ml-4')
                ->setAttr('style', 'margin-right:10px');

            $statusVarName = Html::js()->vue->getAvailableDataName("statusToggle" . $table->getId() . $index);
            $method        = Html::js()->vue->getAvailableMethod("statusToggleMethod" . $table->getId() . $index);

            Html::js()->vue->set($statusVarName, null);
            Html::js()->vue->addMethod($method, JsFunc::anonymous(['status'])->code(
                Js::assign("this.{$table->getId()}Search['{$toggleButton['searchField']}']", '@status'),
                Js::assign("this.$statusVarName", '@status'),
                JsFunc::call("this.{$table->getId()}GetData")
            ));

            $status->append(El::double('el-button')->setAttrs([
                'type'   => 'primary',
                ':plain' => "$statusVarName !== null",
                'bg'     => '',
                '@click' => "$method(null)",
            ])->append("全部"));

            foreach ($toggleButton['mapping'] as $key => $value) {
                if (is_array($value)) {
                    $key   = $value['value'];
                    $value = $value['label'];
                }

                $status->append(El::double('el-button')->setAttrs([
                    'type' => 'primary',
                    ':plain' => "$statusVarName !== $key",
                    'bg' => '',
                    '@click' => "$method($key)",
                ])->append($value));
            }

            $label = '';
            if (!empty($toggleButton['label'])) {
                $label = $toggleButton['label'] instanceof DoubleLabel
                    ? $toggleButton['label']
                    : El::elText("{$toggleButton['label']}：")->setAttr('style', 'margin-right: 10px;font-weight: bold');
            }

            $status = El::double('div')->setAttr('style', 'margin-bottom:10px')
                ->append($label)
                ->append($status);

            $el->append($status);
        }

        return $el;
    }

    /**
     * @param Table $table
     *
     * @return string|null
     */
    private function sortHandle(Table $table): ?string
    {
        Html::js()->vue->set("{$table->getId()}Sort", null);

        $fieldMap = array_map(fn(Column $column) => [$column->getAttr('prop') => $column->getSortField()], $table->getColumns());
        $fieldMap = array_filter(array_merge(...$fieldMap));

        if (!$fieldMap){
            return null;
        }

        Html::js()->vue->set("{$table->getId()}SortFieldMap", $fieldMap);
        $sortMethod = Html::js()->vue->getAvailableMethod($table->getId() . 'SortMethod');

        Html::js()->vue->addMethod($sortMethod, ['{ column, prop, order }'], Js::code(
            Js::log("@column, prop, order"),
            Js::assign("this.{$table->getId()}Sort", [
                'order' => Js::grammar("order"),
                'field' => Js::grammar("this.{$table->getId()}SortFieldMap.hasOwnProperty(prop) ? this.{$table->getId()}SortFieldMap[prop] : prop")
            ]),
            Js::log("@this.{$table->getId()}Sort"),
            JsFunc::call("this.{$table->getId()}GetData"),
        ));

        return $sortMethod;
    }

    /**
     * 回收站处理
     *
     * @param Table       $table
     * @param DoubleLabel $el
     * @param DoubleLabel $headerEl
     *
     * @return void
     */
    private function trashHandle(Table $table, DoubleLabel $el, AbstractHtmlElement $headerEl): void
    {
        if (!$table->getTrash()) {
            return;
        }

        Html::js()->vue->set("isTrash", "@location.search.includes('is_delete=1')");

        $el->find('[mark-event]')?->setAttr('v-if', '{#v-if|1} && !isTrash');
        $headerEl->each(function (AbstractHtmlElement $el){
            if ($el instanceof TextCharacters && $el->getText() != "刷新数据") {
                $parent = $el->getParent();

                $parent?->setAttr('v-if','{#v-if|1} && !isTrash');
            }
        });

        if (empty($recoverUrl = $table->getTrash()['recoverUrl'])) {
            return;
        }

        $recoverMethod = $table->getId() . "Recover";
        $headerEl->getChildren()[0]->append(
            El::double('el-button')->setAttrs([
                'type' => 'success',
                'bg' => '',
                'text'  => '',
                'icon'  => 'RefreshLeft',
                'v-if'  => 'isTrash',
                '@click' => $recoverMethod
            ])->append("恢复数据")
        );

        Html::js()->vue->addMethod($recoverMethod, [], Js::code(
            Js::let('selection',  "@this.{$table->getId()}Selection"),
            Axios::post($recoverUrl, [
                'ids' => "@selection.map(v => v.id)"
            ])->addLoading()
                ->confirmMessage("确认恢复该数据吗？")
                ->success(Js::code(
                    JsService::message("恢复成功"),
                    JsFunc::call("this.{$table->getId()}GetData")
                ))
                ->fail("this.\$message(data.msg ? data.msg : '服务器错误')")
        ));
    }

    /**
     * @param Table  $table
     * @param string $dataVarName
     * @param array  $query
     *
     * @return JsCode
     */
    private function remoteDataGet(Table $table, string $dataVarName, array $query): JsCode
    {
        return Js::code(
            Js::if('!notLoading')->then(
                Js::assign("this." . $table->getId() . "Loading", true)
            ),
            Js::assign('query', '@query ? query : {}'),
            Axios::get(
                url: Js::grammar("this.{$dataVarName}Url()"),
                query: $query
            )->then(JsFunc::arrow(["{ data }"], Js::code(
                Js::assign("this.{$table->getId()}Loading", false),
                Js::if('data.code === 200')
                    ->then(
                        Js::assign("this.{$dataVarName}", '@data.data.data'),
                        Js::assign("this.{$dataVarName}Total", '@data.data.total')
                    )->else(
                        "this.\$message.warning(data.msg)"
                    )
            )))
        );
    }

    private function dataGet(string $dataVarName, array $query): JsCode
    {
        return Js::code(
            Js::if("this.{$dataVarName}Page")->then(
                Js::code("return this.{$dataVarName}.slice((this.{$dataVarName}Page - 1) * this.{$dataVarName}PageSize, this.{$dataVarName}PageSize)"),
            ),
            Js::code("return this.{$dataVarName};")
        );
    }

    private function exportDataGet(Table $table, string $dataVarName, array $query): JsFunc
    {
//        Html::js()->load('https://cdn.sheetjs.com/xlsx-0.20.3/package/dist/xlsx.full.min.js');
        Html::js()->load('/js/xlsx.full.min.js');

        $titles  = $showMap = $useKeys = [];
        $formatCode = Js::switch("titlesMap[keys[i]]");
        $columns = $table->getColumns(true);
        $columns = array_filter($columns, fn(Column $column) => $column->getAttr('prop') && $column->getImportExcel()['allow']);
        $sortIndex = 0;
        $columns = array_map(function(Column $column) use (&$sortIndex){
            if ($column->getImportExcel()['sort'] === null) {
                $column->importExcel(true, $sortIndex++);
            }
            return $column;
        }, $columns);
        usort($columns, fn(Column $a, Column $b) => $a->getImportExcel()['sort'] - $b->getImportExcel()['sort']);

        foreach ($columns as $column) {
            $titles[$column->getAttr('prop')] = $column->getAttr("label");
            if ($column->getShow() && in_array($column->getShow()['type'], ['switch', 'tag', 'mapping'])) {
                $options = $column->getShow()['config']['options'];
                $showMap[$column->getAttr('prop')] = count($options) == count($options, COUNT_RECURSIVE)
                    ? $options
                    : array_column($options, 'label', 'value');

                $showMap[$column->getAttr('prop')] = array_map(fn($v) => $v instanceof AbstractHtmlElement ? $v->getContent() : (string)$v, $showMap[$column->getAttr('prop')]);
            }elseif ($column->getFormat()){
                $formatCode->case($column->getAttr('label'), Js::code(
                    $this->exportFormatHandle($column->getFormat(), $useKeys)
                ));
            }
        }
        $formatCode->default(Js::code("row.push(data.data.data[j][keys[i]])"));

        $query['is_export'] = 1;
        return JsFunc::anonymous()->code(
            Js::let("loading", JsService::loading()),
            Js::let('query', '@{}'),
            Axios::get(
                url: Js::grammar("this.{$dataVarName}Url()"),
                query: $query
            )->then(JsFunc::arrow(["{ data }"], Js::code(
                Js::code("loading.close();"),
                Js::if('data.code === 200')->then(
                    Js::if("data.data.data.length <= 0")->then(
                        "return;"
                    ),
                    Js::let("titlesMap", $titles),
                    Js::let("showMap", $showMap),
                    Js::let("keys", "@Object.keys(titlesMap)"),
                    Js::let('exportData', []),
                    Js::for("let j = 0; j < data.data.data.length; j++")->then(
                        Js::let("row", []),
                        Js::for("let i = 0; i < keys.length; i++")->then(
                            Js::if("showMap.hasOwnProperty(keys[i])")->then(
                                Js::code("row.push(showMap[keys[i]][data.data.data[j][keys[i]]])"),
                            )->else(
                                Js::code($useKeys ? "let { ". implode(', ', array_unique($useKeys)) ." } = data.data.data[j];" : ""),
                                $formatCode->toCode()
                            )
                        ),
                        Js::code('exportData.push(row)')
                    ),
                    Js::let("worksheet", "@XLSX.utils.json_to_sheet(exportData)"),
                    Js::let("workbook", "@XLSX.utils.book_new()"),
                    Js::code('XLSX.utils.book_append_sheet(workbook, worksheet, "Sheet1")'),
                    Js::code('XLSX.utils.sheet_add_aoa(worksheet, [Object.values(titlesMap)], { origin: "A1" })'),
                    Js::code("XLSX.writeFile(workbook, '{$table->getExcelFilename()}.xlsx', { compression: true })")
                )->else(
                    "this.\$message.warning(data.msg)"
                )
            )))
        );
    }

    /**
     * @param AbstractHtmlElement|string $format
     * @param $useKeys
     * @return JsCode
     */
    private function exportFormatHandle($format, &$useKeys, string $saveVar = 'row'): JsCode
    {
        $code    = Js::code();
        /** @var Js\JsIf $if */
        $if = null;

        $format = El::fictitious()->append($format);
        $format->eachChildren(function (AbstractHtmlElement $element) use ($code, &$if, &$useKeys, $saveVar){
            if ($element instanceof TextCharacters) {
                $currentCode = $this->exportDataParamHandle($element->getText(), $useKeys);
                $currentCode = preg_replace('/@(\w)/', 'this.$1', $currentCode);
                $code->then($if ?: '')->then(JsFunc::call("$saveVar.push", strtr($currentCode, ['@' => ''])));
                $if = null;
                return;
            }

            $currentCode = $this->exportDataParamHandle($element->getContent(), $useKeys);
            $currentCode = preg_replace('/@(\w)/', 'this.$1', strtr($currentCode, ['@' => '']));
            if ($element->getAttr('v-if')) {
                $code->then($if ?: '');
                $where = $this->exportFormatWhereHandle($element->getAttr('v-if'), $useKeys);
                $if = Js::if($where)->then(
                    JsFunc::call("$saveVar.push", $currentCode)
                );
            }elseif ($element->getAttr('v-else-if')) {
                $where = $this->exportFormatWhereHandle($element->getAttr('v-else-if'), $useKeys);
                $if->elseIf($where)->then(
                    JsFunc::call("$saveVar.push", $currentCode)
                );
            }elseif ($element->hasAttr('v-else')){
                $if->else(JsFunc::call("$saveVar.push", $currentCode));
            }elseif ($element->getAttr('v-for')){
                if(str_starts_with(trim($element->getAttr('v-for')), '(')){
                    preg_match('/\(\s*(\w+)\s*,\s*(\w+)\)\s*in\s*(@?)(\w+)/', $element->getAttr('v-for'), $match);
                    empty($match[3]) and $useKeys[] = $match[4];
                    $forVarName = empty($match[3]) ? $match[4] : "this.$match[4]";
                }else{
                    preg_match('/\s*(\w+)\s*in\s*(@?)(\S+)/', $element->getAttr('v-for'), $match);
                    if(empty($match[2])){
                        if (preg_match('/^\w+$/', $match[3])) {
                            $forVarName = $useKeys[] = $match[3];
                        }else{
                            preg_match('/^\w+/', strtr($match[3], ['scope.row.' => '']), $match1);
                            $useKeys[] = $match1[0];
                            $forVarName = strtr($match[3], ['scope.row.' => '']);
                        }
                    }else{
                        $forVarName = "this.$match[3]";
                    }
                }
                $forFormat = ScTool::random('for')->get();
                $for = Js::for("let index in $forVarName")->then(Js::let($match[1], "@{$forVarName}[index]"));
                $for->then($this->exportFormatHandle($element->getContent(), $useKeys, $forFormat));
                $code->then(Js::let($forFormat, []))
                    ->then($for)
                    ->then("$saveVar.push($forFormat.join(''));");
            } else {
                $code->then($if ?: '')->then(JsFunc::call("$saveVar.push", $currentCode));
                $if = null;
            }
        });

        return $code->then($if ?: '');
    }

    private function exportDataParamHandle(string $format, &$useKeys): string
    {
        $format = strtr(strip_tags(strtr($format, ['<=' => '=<'])), ['=<' => '<=']);
        $format = preg_replace('/^\{\{/', '" + (', $format);
        $format = preg_replace('/}}$/', ') + "', $format);
        $format = strtr($format, ['{{' => '" + (', '}}' => ') + "', "\r" => '', "\n" => '']);

        preg_replace_callback('/\((.*)\)/', function ($match) use (&$useKeys) {
            preg_match_all('/(((?<!@|\w|\.|\[])[a-zA-Z]\w*).*?)+/', $match[1], $useKey);
            $useKeys = array_merge($useKeys, array_unique($useKey[0]));
        }, $format);

        $format = strtr($format, ['@item' => 'item']);

        return $this->globalVarHandle($format);
    }

    /**
     * @param string $where
     * @param        $useKeys
     *
     * @return string
     */
    private function exportFormatWhereHandle(string $where, &$useKeys): string
    {
        preg_match_all('/(((?<!@|\w|\.|\[])[a-zA-Z]\w*).*?)+/', $where, $useKey);
        if ($useKey) {
            $useKeys = array_merge($useKeys, array_unique($useKey[0]));
        }

        $where = strtr($where, ['@item' => 'item']);

        return $this->globalVarHandle($where, true);
    }

    /**
     * @param string $format
     * @param bool   $isWhere
     *
     * @return string
     */
    private function globalVarHandle(string $format, $isWhere = false): string
    {
        if (preg_match_all('/@(\w+)/', $format, $vars)) {
            foreach ($vars[1] as $var) {
                if (Html::js()->vue->hasVar($var)) {
                    $format = strtr($format, ['@' . $var => 'this.' . $var]);
                }
                if ($isWhere){
                    $format = strtr($format, ['@' => '']);
                }
            }
        }
        return $format;
    }
}