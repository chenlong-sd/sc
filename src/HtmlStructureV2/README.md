# HtmlStructureV2

`HtmlStructureV2` 用来快速编写后台页面。推荐把它当成一套“按场景拼页面”的 DSL 来使用，而不是继续把它理解成旧 `HtmlStructure` 的兼容外壳。

这份 README 现在按“先上手，再按场景查，再查附录”的顺序组织，优先回答使用方最常见的问题：这个页面该从哪里开始写、该选哪些组件、常见页面应该怎么拼。

## 这份文档怎么读

- 想先跑一个页面：先看“快速开始”
- 想做列表页：看“列表和表格怎么写”以及“常见页面示例”里的列表示例
- 想做表单页：看“表单怎么写”以及“常见页面示例”里的表单示例
- 想做弹窗或 iframe 子页：看“弹窗怎么写”“动作怎么写”
- 想查字段、表格列、筛选协议：直接看文末附录

## 快速开始

V2 页面通常直接从 `Pages::make()` 开始，在 `.sc.php` 模板里直接 `return` 页面对象即可：

```php
<?php

use Sc\Util\HtmlStructureV2\Dsl\Blocks;
use Sc\Util\HtmlStructureV2\Dsl\Lists;
use Sc\Util\HtmlStructureV2\Dsl\Pages;
use Sc\Util\HtmlStructureV2\Dsl\Tables;

return Pages::make('用户管理')
    ->header(
        Blocks::title('用户管理')
    )
    ->addSection(
        Lists::make('user-list')
            ->table(
                Tables::make('user-table')
                    ->dataUrl('/admin/user/list')
                    ->addColumns(
                        Tables::column('ID', 'id')->width(80),
                        Tables::column('用户名', 'username')->minWidth(180),
                        Tables::column('状态', 'status')->displayBooleanTag('启用', '禁用')
                    )
            )
    );
```

先记住这几个规则：

- 页面统一从 `Pages::make()` 开始
- `Pages::make('...')` 的第一个参数用于 HTML `<title>`
- 页面可见标题统一通过 `->header(...)` 自定义组合
- 页面主体通过 `->addSection(...)` 追加
- 列表页通常使用 `Lists::make(...)->table(...)`
- 表单页通常直接放 `Forms::make(...)`
- 弹窗先定义 `Dialogs::make(...)`，再通过 `Actions::*()->dialog(...)` 打开

如果放在现有 `.sc.php` 模板里交给 `ScEngine` 渲染，V2 直接返回页面对象即可，不要再混用旧版 `Html::create()` / `Html::html()`。

## 按场景选择组件

最常见的入口如下：

- `Pages::make()`：页面根容器，所有 V2 页面都从这里开始
- `Lists::make()`：标准列表页容器，负责 `filters + table + managed dialogs`
- `Tables::make()`：独立表格区块，适合简单表格页或组合页面里的子表格
- `Forms::make()`：独立表单页、弹窗表单、筛选表单
- `Fields::*()`：字段工厂，只负责字段本身
- `Dialogs::make()`：表单弹窗、说明弹窗、iframe 弹窗、组件弹窗
- `Actions::*()`：按钮动作，当前支持 `create/edit/delete/import/submit/close/refresh/save/resetForm/back/request/custom`
- `Layouts::*()` / `Blocks::*()` / `Displays::*()`：轻布局和轻展示组件

常见组合方式：

- 纯列表页：`Pages + Lists + Tables`
- 纯表单页：`Pages + Forms`
- 列表 + 编辑弹窗：`Pages + Lists + Tables + Dialogs + Actions`
- 混合看板页：`Pages + Tables/Forms + Layouts/Blocks/Displays`

## 页面如何组织

推荐按下面的方式组织页面：

- 所有页面统一从 `Pages::make()` 开始
- 页面根背景默认白色，可通过 `->backgroundPreset('white'|'muted'|'transparent')` 或 `->background('linear-gradient(...)')` 覆盖
- 表单页直接在 `addSection()` 里放 `Forms::make()`
- 列表页直接在 `addSection()` 里放 `Lists::make()` 或 `Tables::make()`
- 需要混合布局时继续组合 `Forms / Lists / Tables / Layouts / Blocks / Displays`
- `Pages::make()` 下直接放置的 `Form / Table / ListWidget` 默认按组件本体渲染，不自动补一层 card；需要卡片或说明时再显式用 `Layouts::card()` / `Blocks::title()` 组合

和目标数据源强相关的动作，尽量显式写清作用对象：

- 页面头部或独立区块中的动作，统一使用 `->forList('orders')` / `->forTable('audit-table')`
- key 写错时会在构建期直接抛错，不再回退到“当前主表”
- 像 `Actions::refresh()`、`RequestAction::reloadTable()` 这类动作，如果当前位置没有局部 table/list 上下文，也没有显式 `forTable()/forList()`，构建期会直接报错

条件动作推荐这样理解：

- `->when($condition)`：PHP 层直接移除当前动作，不参与后续渲染和校验
- `->when($condition, fn (Action $action) => ...)`：条件成立时再继续链式配置

仓库里可直接渲染的综合示例见 `test/Fixtures/View/ScEngine/v2-full-demo.sc.php`，对应测试入口是 `ScEngine.v2-full-demo`。

## 列表和表格怎么写

列表相关最常见的两种写法：

- 只有表格：`Pages::make()->addSection(Tables::make(...))`
- 标准列表页：`Pages::make()->addSection(Lists::make(...)->filters(...)->table(...))`

这里有几个使用规则需要先知道：

- `Lists::make()` 负责 `filters + table + managed dialogs` 这一组复合交互
- `Tables::make()` 适合独立表格区块，或者组合页面里的第二张表
- 列展示型配置统一使用 `display*` 前缀方法，例如 `displayTag()`、`displayDatetime()`、`displayImage()`、`displayPlaceholder()`
- `Table` 在运行时按组件 `key` 隔离状态和事件，同一个页面里可以组合多个独立 table / list-like 区块；同页 `table key / list key` 都必须唯一
- 和表格本身强相关的行为配置优先收口到 `Table`，例如 `deleteUrl()` / `deleteKey()`
- `filters()` 只描述筛选 UI；如果不写，会尝试按 `searchable()/search()/searchSchema()` 自动生成默认筛选项
- 默认自动生成的筛选项会隐藏 label，只保留 placeholder；显式声明的筛选项会保留，缺失项继续自动补齐

### 表格导出

V2 现在支持原生表格导出，不再依赖 V1 渲染链路。

- `Table::export('文件名')` 开启导出按钮
- `Table::exportLabel()` / `exportType()` / `exportIcon()` 可调整按钮表现
- `Table::exportQuery([...])` 可追加远程导出请求参数；默认会带上 `is_export=1`

导出规则：

- 当前有勾选行时，优先导出当前选中数据
- 没有勾选时，远程表格会复用当前筛选和排序重新拉取全量数据
- `Column::onlyExportExcel()` 会进入导出但不在页面展示
- 页面可见列默认参与导出
- 开启 `settings()` 后，可在“列设置”的“展示设置”里拖动调整列顺序；拖动结果会直接决定页面展示顺序
- 开启 `settings()` 后，可在“列设置”里单独控制每列是否导出；导出顺序由“导出设置”tab 内拖动决定
- “列设置”里现在拆成“展示设置 / 导出设置”两个 tab；在不同 tab 中拖动时，会分别作用于展示顺序或导出顺序
- 当前激活的设置 tab 只是界面状态，不会写入列设置持久化结果
- 配了独立导出开关后，导出不再强依赖页面展示状态；隐藏列是否导出以“列设置”里的导出开关为准

```injectablephp
<?php

use Sc\Util\HtmlStructureV2\Dsl\Tables;

$table = Tables::make('orders')
    ->addColumns(
        Tables::column('订单号', 'order_no'),
        Tables::column('状态', 'status')->displayMapping([1 => '已支付', 0 => '待支付']),
        Tables::column('内部备注', 'remark')->onlyExportExcel()
    )
    ->export('订单列表')
    ->exportLabel('导出Excel');
```

仓库里的真实组合示例可参考 `plugins/QA/Http/Admin/View/QaCase/lists.sc.php`。

### 回收站

V2 现在支持原生表格回收站能力，行为按原版表格语义对齐。

- `Table::trash($recoverUrl = null)` 开启回收站
- `Table::recoverUrl()` 可单独补恢复接口
- 仅远程数据表格生效

行为规则：

- 工具栏右侧会自动补一个“回收站”按钮
- 点击后会以 iframe 弹窗打开当前页，并自动追加 `is_delete=1`
- 回收站模式下，普通工具栏动作、导出、列设置、行操作会隐藏
- `Actions::refresh()` 这类刷新动作会继续保留
- 配了 `recoverUrl()` 后，回收站模式右侧会显示“恢复数据”，按当前 selection 批量提交 `{"ids": [...]}` 到恢复接口
- 恢复主键默认沿用 `deleteKey()`，不再额外重复配置

```injectablephp
<?php

use Sc\Util\HtmlStructureV2\Dsl\Actions;
use Sc\Util\HtmlStructureV2\Dsl\Tables;

$table = Tables::make('orders')
    ->dataUrl('/admin/orders/list')
    ->selection()
    ->deleteKey('id')
    ->trash('/admin/orders/recover')
    ->toolbarLeft(
        Actions::delete()->confirm('确认删除当前选中数据？')
    )
    ->toolbarRight(
        Actions::refresh('刷新')
    );
```

### 状态切换按钮栏

V2 现在支持表格顶部的快速状态切换按钮栏，适合列表页里高频的枚举筛选。

- `Table::statusToggle($name, $options, $label = null)` 是原生写法
- `Table::statusTogglesNewLine()` 控制多组按钮按换行模式展示

行为规则：

- 按钮点击后会复用当前表格已有的搜索协议、分页和排序流程
- 如果当前表格挂在 `ListWidget` 里，会同步写入该 list 的 filter model，而不是额外维护一套独立 search 状态
- 未显式勾选时，“全部”会清空对应筛选值
- 后端真实字段映射继续通过 `Table::search()` / `searchSchema()` / `Column::searchable()` 定义，不再塞进 status toggle 自身参数里

```injectablephp
<?php

use Sc\Util\HtmlStructureV2\Dsl\Tables;

$table = Tables::make('qa')
    ->addColumns(
        Tables::column('案件状态', 'case_status')
    )
    ->statusToggle(
        'case_status',
        [
            ['value' => 1, 'label' => '待确认'],
            ['value' => 2, 'label' => '处理中'],
        ],
        '案件状态'
    )
    ->statusTogglesNewLine();
```

和 `ListWidget` 自动筛选联动的真实组合示例同样可参考 `plugins/QA/Http/Admin/View/QaCase/lists.sc.php`。

## 轻组件怎么用

V2 后续页面灵活性优先通过轻组件补齐，而不是继续把标题、提示语、说明文案、详情块之类内容塞进重交互组件。

- `Layouts::stack(...)` 纵向堆叠多个独立组件
- `Layouts::grid(...)` 用 CSS Grid 组合多个独立组件
- `Layouts::card(...)` 在页面里放一个轻量卡片容器
- `Blocks::title(...)` 页面中间标题
- `Blocks::divider(...)` 分割线
- `Blocks::text(...)` 纯文本说明
- `Blocks::alert(...)` 提示块
- `Blocks::button(...)` 轻量交互按钮
- `Displays::descriptions(...)` 轻量详情展示
- 以上轻组件现在统一支持 `attr()` / `attrs()` / `className()` / `style()`，属性会挂到各自渲染根节点上；动态绑定请自行写成 `":prop"` / `"@event"`

轻组件可以和 `ListWidget / Table / Form / Dialog` 混排；它们不参与 table/list/dialog key 注册，也不扩展当前重 runtime。

但如果 `Layouts::stack()` / `Layouts::grid()` / `Layouts::card()` 里包的是重组件，那么这些重组件仍会正常参与页面级的 key 注册、dialog 收集和 action target 校验；layout 本身只是组合容器，不会把内部重组件“藏起来”。

`Action` 也可以当作独立 renderable 直接放进 `Layouts` 或页面 section 里，不只用于页头、表格工具栏、行操作。只要它绑定了 `Dialog` 或显式 `forTable()/forList()` 目标，仍会正常参与页面级 dialog 收集和 target 校验。

```injectablephp
<?php

use Sc\Util\HtmlStructureV2\Dsl\Blocks;
use Sc\Util\HtmlStructureV2\Dsl\Displays;
use Sc\Util\HtmlStructureV2\Dsl\Layouts;
use Sc\Util\HtmlStructureV2\Dsl\Pages;

$page = Pages::make('概览页')
    ->header(
        Blocks::title('运营概览')->description('轻组件之间可以自由组合')
    )
    ->backgroundPreset('white')
    ->addSection(
        Layouts::grid(
            Blocks::alert('提示', '这是一个提示块'),
            Displays::descriptions()
                ->title('基础信息')
                ->items([
                    '状态' => '草稿',
                    '作者' => 'system',
                ])
        )->columns(2)
    );
```

## 表单怎么写

V2 表单现在按“节点树”组织，不再只面向 `Field[]`。

如果只是普通表单，直接 `Forms::make()->addFields(...)` 即可；只有在需要分组、分栏、折叠、标签页、数组表格时，再继续往下使用结构节点。

- 叶子节点：`Field`
- 结构节点：`Forms::section()` / `Forms::inline()` / `Forms::grid()` / `Forms::tabs()` / `Forms::collapse()` / `Forms::custom()`
- 数据作用域节点：`Forms::object()`
- 数组节点：`Forms::arrayGroup()` / `Forms::table()`

这套方向的目标是补齐原版表单在布局上的灵活性，但不照搬原版的全局 JS 注入和展示表格承载编辑表格的实现方式。

```injectablephp
<?php

use Sc\Util\HtmlStructureV2\Dsl\Actions;
use Sc\Util\HtmlStructureV2\Dsl\Blocks;
use Sc\Util\HtmlStructureV2\Dsl\Fields;
use Sc\Util\HtmlStructureV2\Dsl\Forms;
use Sc\Util\HtmlStructureV2\Dsl\Layouts;
use Sc\Util\HtmlStructureV2\Support\JsExpression;

$form = Forms::make('profile')->addContent(
    Forms::section('基础信息')->addContent(
        Forms::object('base')->addSchema(
            Fields::text('name', '名称')->span(12),
            Fields::text('code', '编码')->span(12)
        ),
        Forms::tabs()->addTabs(
            Forms::tab('高级配置')->addContent(
                Forms::collapse()->addItems(
                    Forms::collapseItem('同步设置')->addContent(
                        Fields::text('sync_key', '同步键')->span(12),
                        Fields::number('sync_sort', '同步排序')->span(12)
                    )
                )->accordion()->span(24)
            )->lazy()
        )->type('border-card')->span(24),
        Forms::inline()->addItems(
            Fields::text('city', '城市'),
            Fields::number('sort', '排序')
        ),
        Forms::custom(
            Layouts::stack(
                Blocks::text('这里可以插入自定义说明内容')->type('muted'),
                Blocks::alert('提示', '支持直接插轻组件，不建议继续堆大段原始 HTML')
            )
        )->span(24)
    )->headerActions(
        Actions::custom('查看源数据')->onClick(JsExpression::make("console.log('open source group')"))
    ),
    Forms::grid()->addItems(
        Fields::text('source_id', '源数据')
            ->suffixContent('需要时可跳转查看原始记录')
            ->suffixActions(
                Actions::custom('打开')->onClick(JsExpression::make("console.log('open source')"))
            )
            ->span(24),
        Fields::textarea('remark', '备注')->span(24)
    )->gutter(20),
    Forms::table('items')->addColumns(
        Forms::section('商品')->addContent(
            Fields::text('sku', 'SKU')->required()->columnWidth(180),
            Fields::number('qty', '数量')->default(1)->columnWidth(100)->columnAlign('center')
        ),
        Forms::object('extra')->addSchema(
            Fields::text('note', '备注')->columnMinWidth(220)
        )
    )->title('明细')->minRows(1)->reorderable()
);
```

这几个节点当前的边界要明确：

- `Forms::object()` 只负责切换数据路径，不额外引入运行时实例。
- `Forms::arrayGroup()` 现在已经可渲染，适合“重复分组表单”；每组会按共享数组节点 runtime 管理，支持 `title()`、`minRows()`、`maxRows()`、`addable()`、`reorderable()`、`removable()`。
- `Forms::table()` 是数组节点的表格化 renderer，和 `Forms::arrayGroup()` 共用同一套数组节点初始化与增删排序协议。
- `Forms::arrayGroup()` 现在支持继续嵌套 `Forms::arrayGroup()` / `Forms::table()`；渲染时会按层级生成显式数组路径表达式和独立循环变量，避免嵌套作用域混淆。
- `Forms::table()` 继续复用数组节点协议，行内现在也支持继续嵌套 `Forms::arrayGroup()` / `Forms::table()`；它们会作为单个单元格里的嵌套数组编辑器渲染，并沿用同一套数组路径和 runtime 协议。
- `Forms::table()` 现在支持在行节点里继续使用 `object()`、`section()`、`grid()`、`inline()` 做结构组织；最终会在构建期展开成列。
- `Forms::table()` 在 `->reorderable()` / `->removable()` 时会自动追加内置“操作”列；当前支持拖动排序和删除行。
- `Forms::table()` 的叶子列节点现在支持 `columnWidth()` / `columnMinWidth()` / `columnAlign()` / `columnFixed()` / `columnProps()`；可直接写在 `Fields::*()`、`Forms::custom()`、嵌套 `Forms::arrayGroup()` / `Forms::table()` 上。
- `Fields::*()` 原本已有 `prop()` / `props()`；现在也补了统一别名 `attr()` / `attrs()` / `className()` / `style()`，仍然直接作用到字段实际渲染的组件根节点，不作用于外层 form-item。
- `Forms::section()` / `Forms::inline()` / `Forms::grid()` / `Forms::tabs()` / `Forms::tab()` / `Forms::collapse()` / `Forms::collapseItem()` / `Forms::arrayGroup()` / `Forms::table()` / `Forms::custom()` 现在统一支持 `attr()` / `attrs()` / `className()` / `style()`；属性会挂到各自的主要渲染根节点上。
- `Forms::tabs()` / `Forms::collapse()` 属于纯结构节点，主要补“分标签布局 / 折叠分组”这类高级表单布局能力；它们继续复用表单树 walker，不单独引入 managed runtime。
- `Forms::section()` / `Forms::tab()` / `Forms::collapseItem()` 用 `->addContent(...)` 追加面板内容。
- `Forms::inline()` / `Forms::grid()` 用 `->addItems(...)` 追加布局项。
- `Forms::object()` / `Forms::arrayGroup()` 用 `->addSchema(...)` 追加结构。
- `Forms::table()` 用 `->addColumns(...)` 追加表格列 schema。
- `Forms::tabs()` / `Forms::collapse()` 也不再在构建入口里塞子节点，分别通过 `->addTabs(...)` / `->addItems(...)` 继续追加。
- `Forms::table()` 里的 `section()` / `tab()` / `collapseItem()` 现在会按列数决定表头策略：如果某个结构节点下面最终展开出多列，会生成真正的分组表头；如果只展开出单列，则继续拍平成 `商品 / SKU` 这类可读列名，避免出现空白二级表头。
- `Forms::tab()` / `Forms::collapseItem()` 的标题仍会参与单列路径标签推导，所以它们只包一列时，会自动形成类似 `基础信息 / 扩展信息 / 编码` 的平铺列标题；如果下面有多列，则会优先生成合并后的分组表头。
- `Forms::table()` 现在支持 `custom()` 行内容节点，适合放按钮列、说明列、自定义展示块；可以直接用 `Forms::custom(...)->columnLabel('源数据')` 指定列标题，也可以继续配合 `Forms::section('操作')->addContent(...)` / 其他结构节点参与分组表头。
- `Forms::table()` 的单元格模板内现在统一可直接使用 `scope.row` / `scope.$index`；即使它嵌套在 `arrayGroup()` 或其他表格里，也不需要关心内部实际生成的是 `scope0` 还是 `scope1`。
- `Forms::custom()` 现在支持三类内容：原始字符串、`AbstractHtmlElement`、轻组件树（`Layouts / Blocks / Displays`）。表单内部推荐优先放轻组件树，像标题、分割线、提示语、说明块、详情展示都走这条路径。
- `Forms::custom()` 不允许混入 `Form / Table / ListWidget / Dialog / Action` 这类重组件；即使外层包了 `Layouts::stack()` 之类轻布局，也会在构建期直接拦截，避免把额外 runtime 和事件目标塞进表单节点树。
- 字段右侧附加内容统一走 `suffixContent()` / `suffixActions()`，不要再把这类辅助按钮硬塞进表单布局容器。
- `suffixActions()` 里的结构化 action 现在和页面其他 action 一样参与 target 校验；如果绑定的是 `Dialog` 对象，也会自动进入页面 dialog 收集。
- `Forms::section()` 现在支持 `->headerActions(...)`，适合在分组头部放“查看源数据”“同步本组配置”这类入口，不用再退回到 `custom()` 拼 header。

## 弹窗怎么写

V2 的 `Dialog` 不再只是“一个表单弹窗”，现在支持三类弹窗体：

推荐先决定“弹窗里放什么”，再决定“由哪个动作打开它”。最常见写法仍然是先定义 `Dialogs::make(...)`，再在 `Actions::create()` / `Actions::edit()` / `Actions::submit()` 上链式 `->dialog(...)`；但如果 `Actions::submit()` / `Actions::close()` 已经写在当前 dialog 的 `footer(...)` 里，则会自动识别当前 dialog，不必再重复写 `->dialog(...)`。

- `->form(...)` 表单弹窗
- `->content(...)` 说明/确认/自定义内容弹窗
- `->iframe($url, $query)` 内嵌页面弹窗
- `->component($name, $props, $attrs)` Vue 组件弹窗

并且补齐了列表页常见的编辑场景：

- `->createUrl()` / `->updateUrl()` 区分新建和编辑提交地址
- `->saveUrl()` 作为统一提交地址兜底
- `->load($url)` 在打开时拉取详情数据
- `->loadPayload(...)` 定义详情请求参数，支持 `@row.id` / `@filters.xxx` / `@selection.0.id`
- `->loadDataPath('data')` 指定从响应中取哪一段作为表单数据
- `->loadWhen('edit'|'create'|'always')` 控制仅编辑时加载、仅新建时加载或总是加载
- `Dialogs::make('userEditor', '编辑 {name}')` 的 title 参数现在也支持和 `titleTemplate()` 相同的模板语法
- `->titleTemplate('编辑 {name}')` 根据当前行动态标题
- `->beforeOpen()` / `->afterOpen()` / `->beforeClose()` / `->afterClose()` 生命周期钩子
- `->context([...])` 显式注入弹窗上下文，支持 `@row.id` / `@filters.keyword` / `@selection`
- `->iframeHost()` 为 iframe 子页面开启宿主桥接
- `->iframeFullscreenToggle()` 为 iframe 弹窗启用头部全屏切换
- `->componentOpenMethod()` / `->componentCloseMethod()` 控制组件弹窗打开关闭时调用的实例方法

列表页工具栏和请求动作可以通过 `Table::selection()` 开启勾选列，之后请求动作和弹窗上下文里都能拿到 `selection`。如果这个列表页是作为 iframe 选择页被其他表单打开，V2 还会自动把选中结果暴露到子页面全局：

- `window.__scV2Selection`: 当前主表或默认表的选中结果
- `window.__scV2Selections[tableKey]`: 多表页里按表 key 区分的选中结果

因此 `Fields::picker()` 指向 V2 列表页时，通常可以直接省略 `selectionPath()`；只有多表页或自定义选择页才需要额外指定。

```injectablephp
<?php

$userPickerDialog = Dialogs::make('userPicker', '选择用户')
    ->iframe('/admin/user/list');

Fields::picker('user_ids', '用户')
    ->dialog($userPickerDialog);
// 如果子页是 V2 列表页并开启了 Table::selection()，默认读取 "__scV2Selection"
```

多表页可显式指定某一个表格：

```injectablephp
Fields::picker('user_ids', '用户')
    ->dialog($userPickerDialog)
    ->selectionPath('__scV2Selections.userTable');
```

`dialogs()` 可以直接挂在 `Pages::make()` 上。头部 `Actions::create('新建')->dialog($dialog)`、`Actions::edit('编辑')->dialog($dialog)` 会自动收集对应弹窗；如果弹窗是从自定义按钮、独立表格行按钮或自定义 JS 打开的，推荐显式写 `->dialogs($dialog)`。

如果动作直接通过 `->dialog($dialog)` 绑定的是 `Dialog` 对象，V2 会在页面构建时自动把它收集进当前页面，包括独立 `Table` 区块和 dialog footer 里的二级弹窗；如果只写字符串 key，例如 `Actions::create('打开')->dialog('editor')`、`Actions::submit('保存')->dialog('editor')`、`Actions::request(...)->dialog('editor')`，对应 key 必须能在当前页面解析到，否则会在构建期直接抛错。

## 常见页面示例

下面这些示例按后台开发里最常见的页面类型组织。建议先看和自己场景最接近的写法，再回头查动作、事件、附录能力。

### 完整列表页示例

下面这个示例覆盖了：

- 页面头部动作
- 筛选表单
- 表格工具栏动作
- 行级请求动作
- 多弹窗
- `Actions::custom()->onClick(...)` 兜底 JS

```injectablephp
<?php

use Sc\Util\HtmlStructureV2\Dsl\Actions;
use Sc\Util\HtmlStructureV2\Dsl\Dialogs;
use Sc\Util\HtmlStructureV2\Dsl\Fields;
use Sc\Util\HtmlStructureV2\Dsl\Forms;
use Sc\Util\HtmlStructureV2\Dsl\Pages;
use Sc\Util\HtmlStructureV2\Dsl\Tables;
use Sc\Util\HtmlStructureV2\Support\JsExpression;

$normalDialog = Dialogs::make('product-editor', '编辑商品')
    ->createUrl('/admin/product/create')
    ->updateUrl('/admin/product/update')
    ->load('/admin/product/detail')
    ->loadPayload([
        'id' => '@row.id',
    ])
    ->loadDataPath('data')
    ->loadWhen('edit')
    ->titleTemplate('编辑 {name}')
    ->form(
        Forms::make('product-editor')->addFields(
            Fields::hidden('id'),
            Fields::text('name', '商品名')->required(),
            Fields::number('price', '价格')->minValue(0),
            Fields::select('status', '状态')->options([
                1 => '上架',
                0 => '下架',
            ]),
            Fields::image('cover', '封面')
                ->uploadUrl('/admin/upload/image')
                ->uploadResponsePath('data.url')
        )
    );

$batchDialog = Dialogs::make('create-batch', '批量新增')
    ->saveUrl('/admin/product/batchSave')
    ->form(
        Forms::make('product-batch')->addFields(
            Fields::textarea('codes', '商品编码')->required(),
            Fields::select('category_id', '分类')
                ->remoteOptions('/admin/category/options', 'id', 'name')
        )
    );

$previewDialog = Dialogs::make('preview-product', '预览商品')
    ->iframe('/admin/product/preview', [
        'id' => '@row.id',
    ])
    ->titleTemplate('预览 {name}');

$tipsDialog = Dialogs::make('sync-tips', '同步说明')
    ->width('560px')
    ->content(<<<'HTML'
<div style="line-height:1.8;color:#606266">
  当前操作会按筛选条件批量同步价格缓存。
  如果列表开启了勾选，也可以只同步已勾选的数据。
</div>
HTML);

$page = Pages::make('商品列表')
    ->header(
        Blocks::title('商品列表')->description('完整示例：筛选、表格、多弹窗、结构化请求动作、自定义 JS 兜底')
    )
    ->actions(
        Actions::create('新建商品')->dialog($normalDialog),
        Actions::create('批量操作')->dialog($batchDialog),
        Actions::custom('同步说明')
            ->onClick(JsExpression::make("openDialog('sync-tips')")),
        Actions::request('同步价格缓存')
            ->key('sync-price-cache')
            ->post('/admin/product/sync-price-cache')
            ->payload([
                'keyword' => '@filters.keyword',
                'status' => '@filters.status',
                'ids' => '@selection',
            ])
            ->confirm('确认同步当前筛选条件下的价格缓存？')
            ->loadingText('正在同步缓存...')
            ->successMessage('缓存同步任务已提交')
            ->afterSuccess(
                JsExpression::make('(ctx) => console.log("sync ok", ctx.payload)')
            )
    )
    ->addSection(
        Lists::make('product-list')
            ->filters(
                Forms::make('product-filters')->inline()->addFields(
                    Fields::text('keyword', '关键词')->placeholder('商品名 / 编码'),
                    Fields::select('status', '状态')->options([
                        '' => '全部',
                        1 => '上架',
                        0 => '下架',
                    ]),
                    Fields::daterange('created_at', '创建时间')
                )
            )
            ->table(
                Tables::make('product-table')
                    ->selection()
                    ->dataUrl('/admin/product/list')
                    ->search('keyword', 'LIKE', 'name&code')
                    ->addColumns(
                        Tables::column('ID', 'id')->width(80),
                        Tables::column('商品名', 'name')->minWidth(200)->searchable('LIKE'),
                        Tables::column('状态', 'status')->displayTag([
                            1 => ['label' => '上架', 'type' => 'success'],
                            0 => ['label' => '下架', 'type' => 'danger'],
                        ])->searchable(),
                        Tables::column('创建时间', 'created_at')->displayDatetime()->searchable('BETWEEN')
                    )
                    ->toolbarLeft(
                        Actions::request('重建索引')
                            ->post('/admin/product/rebuild-index')
                            ->payload([
                                'status' => '@filters.status',
                            ])
                            ->confirm('确认重建当前筛选结果的索引？')
                            ->successMessage('索引重建任务已提交'),
                        Actions::create('普通新增')->dialog($normalDialog),
                        Actions::delete()->confirm('确认删除当前选中商品？')
                    )
                    ->toolbarRight(
                        Actions::refresh()
                    )
                    ->rowActions(
                        Actions::request('上架')
                            ->post('/admin/product/publish')
                            ->payload([
                                'id' => '@row.id',
                                'status' => 1,
                            ])
                            ->confirm('确认上架当前商品？')
                            ->successMessage('上架成功')
                            ->reloadTable()
                            ->afterSuccess(
                                JsExpression::make('(ctx) => console.log("publish ok", ctx.row, ctx.payload)')
                            ),
                        Actions::edit('编辑')->dialog($normalDialog),
                        Actions::custom('预览')
                            ->onClick(JsExpression::make("openDialog('preview-product', scope.row)")),
                        Actions::custom('复制链接')
                            ->onClick(JsExpression::make(<<<'JS'
(async () => {
  if (!scope.row.share_url) {
    ElementPlus.ElMessage.error('当前记录没有分享链接');
    return;
  }

  await navigator.clipboard.writeText(String(scope.row.share_url));
  ElementPlus.ElMessage.success('链接已复制');
})()
JS)),
                        Actions::request('删除')
                            ->post('/admin/product/delete')
                            ->payload([
                                'id' => '@row.id',
                            ])
                            ->type('danger')
                            ->icon('Delete')
                            ->confirm('确认删除当前商品？')
                            ->reloadTable()
                    )
                    ->deleteUrl('/admin/product/delete')
            )
    )
    ->dialogs($previewDialog, $tipsDialog);

echo $page->toHtml();
```

表格工具栏默认分左右两区：

- `toolbarLeft(...)` 放左侧，适合新增、批量操作这类主操作
- `toolbarRight(...)` 放右侧，适合刷新、辅助工具这类次级操作
- `export()` 和列设置按钮会继续固定在右侧

```php
Tables::make('product-table')
    ->toolbarLeft(
        Actions::create('新增')->dialog($normalDialog)
    )
    ->toolbarRight(
        Actions::refresh('刷新')
    );
```

如果放在项目现有的 `.sc.php` 模板里交给 `ScEngine` 渲染，V2 直接返回页面对象即可：

```php
return $page;
```

`ScEngine` 会把这类返回值当成完整文档输出，不再走原版 `Html::create()` / `Html::html()` 的全局页面装配流程。
同时它会在每次渲染前后清理旧版 `Html` 页面状态，避免长进程/测试环境里串到上一次经典页面。

同一个模板里不要混用这两种模式：

- 原版模式：`Html::create()` + `Html::html()` + 返回片段
- V2 模式：直接 `return` V2 page/document

如果 V2 模板里同时出现了直接 `echo/print` 输出，或者又调用了旧版 `Html::create()` / `Html::html()`，`ScEngine` 现在会在渲染期直接抛错，而不是继续兜底混过去。

### 编辑弹窗示例

下面这个弹窗展示了“新建/编辑不同提交地址 + 编辑前加载详情”的典型配置：

```php
<?php

use Sc\Util\HtmlStructureV2\Dsl\Dialogs;
use Sc\Util\HtmlStructureV2\Dsl\Fields;
use Sc\Util\HtmlStructureV2\Dsl\Forms;

$editorDialog = Dialogs::make('editor', '编辑用户')
    ->createUrl('/admin/user/create')
    ->updateUrl('/admin/user/update')
    ->load('/admin/user/detail')
    ->loadPayload([
        'id' => '@row.id',
    ])
    ->loadDataPath('data')
    ->loadWhen('edit')
    ->titleTemplate('编辑 {username}')
    ->form(
        Forms::make('user-editor')->addFields(
            Fields::hidden('id'),
            Fields::text('username', '用户名')->required(),
            Fields::password('password', '密码')->minLength(6)
        )
    );
```

如果接口无论新建还是编辑都走同一个保存地址，也可以只写 `->saveUrl('/admin/user/save')`。

### 非表单弹窗示例

```php
<?php

use Sc\Util\HtmlStructureV2\Dsl\Actions;
use Sc\Util\HtmlStructureV2\Dsl\Dialogs;
use Sc\Util\HtmlStructureV2\Support\JsExpression;

$contentDialog = Dialogs::make('help', '操作说明')
    ->content('<div style="line-height:1.8">这里放说明内容、富文本或自定义组件。</div>')
    ->footer(
        Actions::close('我知道了')->type('primary')
    );

$componentDialog = Dialogs::make('editor-component', '组件编辑')
    ->context([
        'currentId' => '@row.id',
        'selectedIds' => '@selection.0.id',
    ])
    ->component('user-editor-panel', [
        'user-id' => '@currentId',
        'selected-id' => '@selectedIds',
    ], [
        'class' => 'sc-v2-component-dialog',
    ])
    ->componentOpenMethod('onShow')
    ->componentCloseMethod('onHide');

$iframeDialog = Dialogs::make('preview', '页面预览')
    ->width('88vw')
    ->height('78vh')
    ->iframe('/admin/user/preview', [
        'id' => '@row.id',
    ])
    ->iframeHost()
    ->iframeFullscreenToggle()
    ->titleTemplate('预览 {username}')
    ->beforeOpen(JsExpression::make('(ctx) => console.log("opening", ctx.row)'))
    ->afterClose(JsExpression::make('(ctx) => console.log("closed", ctx.dialogKey)'));
```

`component()` 适合已经在页面全局注册过的 Vue 组件。打开弹窗后，runtime 会在 `nextTick` 后尝试调用组件实例上的 `onShow(ctx)`；关闭完成后如果配置了 `componentCloseMethod()`，也会调用对应实例方法。

`context()` 解析后会同时挂到弹窗上下文的顶层字段和 `ctx.dialogContext` 上，所以你既可以写 `@currentId`，也可以在 hook 里用 `ctx.dialogContext.currentId`。

iframe 弹窗如果要配合 `Actions::submit()` 使用，除了配置 `saveUrl()/createUrl()/updateUrl()` 外，子页面还需要暴露一个提交方法。默认读取 `"__SC_V2_PAGE__.submit"`：

```injectablephp
<?php

$fillDialog = Dialogs::make('work-order-fill', '填写')
    ->updateUrl('/admin/work-order/update')
    ->iframe('/admin/work-order/fill', [
        'id' => '@row.id',
    ])
    ->iframeSubmitHandler('__SC_V2_PAGE__.submit')
    ->footer(
        Actions::close('取消'),
        Actions::submit('提交结果')->confirm('确认提交吗？')
    );
```

`footer(...)` 里的 `Actions::submit()` / `Actions::close()` 现在会默认作用到当前 dialog；只有把动作写到弹窗外部时，才需要显式 `->dialog('work-order-fill')`。

V2 子页面默认可直接使用：

- `"__SC_V2_PAGE__.submit(scope = null)"`: 校验并返回表单数据
- `"__SC_V2_PAGE__.validateForm(scope = null)"`
- `"__SC_V2_PAGE__.cloneFormModel(scope = null)"`
- `"__SC_V2_PAGE__.setFormModel(values)"` 或 `"__SC_V2_PAGE__.setFormModel('formKey', values)"`
- `"__SC_V2_PAGE__.initializeFormModel(values)"` 或 `"__SC_V2_PAGE__.initializeFormModel('formKey', values)"`
- `"__SC_V2_PAGE__.resetForm(scope = null)"`: 恢复到当前表单最近一次初始化快照

如果页面里只有一个可提交表单，`scope` 可以省略；否则应显式传表单 key。这个能力依赖宿主页直接访问 `iframe.contentWindow`，通常要求子页面与宿主页同源。

区别是：

- `setFormModel()`：按 `defaults + 传入值` 回填，适合普通整表覆盖
- `initializeFormModel()`：按表单 schema 回填，会剔除未声明字段；数组组会按行 schema 递归裁剪
- `resetForm()`：恢复到“当前初始值快照”，不是单纯退回 schema defaults

当前初始值快照来源：

- 页面首次渲染时的 `Form::setData()` / 表单默认值
- 独立表单页 `Form::load()` 最近一次成功加载后的数据
- form 弹窗最近一次打开并初始化/加载后的数据

如果你更希望配置就近写在按钮上，也可以直接在 action 上覆盖默认地址；优先级高于 dialog / table 默认配置：

```injectablephp
<?php

Actions::submit('保存')->dialog('editor')
    ->updateUrl('/admin/user/update-direct');

Actions::delete()
    ->deleteUrl('/admin/user/delete-direct')
    ->deleteKey('user_id');
```

### iframe 宿主桥

开启 `->iframeHost()` 后，宿主页会为 iframe 子页面提供宿主桥。通常直接调用
页面 runtime / action context 暴露的方法即可，不需要手写
`window.parent.postMessage(...)`。

常用公共方法：

- `ctx.closeHostDialog(dialogKey = null)`
- `ctx.reloadHostTable(tableKey = null, dialogKey = null)`
- `ctx.openHostDialog(dialogKey, row = null, tableKey = null)`
- `ctx.openHostTab(route | { route, title?, index? }, title = '', index = null)`
- `ctx.setHostDialogTitle(title, dialogKey = null)`
- `ctx.setHostDialogFullscreen(value = true, dialogKey = null)`
- `ctx.toggleHostDialogFullscreen(dialogKey = null)`
- `ctx.refreshHostDialogIframe(dialogKey = null)`

底层宿主桥仍然识别这些 `action`，只有在对接非 V2 子页面时才建议直接发消息：

- `close`
- `reloadTable`
- `openDialog`
- `setTitle`
- `setFullscreen`
- `toggleFullscreen`
- `refreshIframe`

如果不显式传 `dialogKey`，宿主会按消息来源窗口自动反查所属 iframe 弹窗。

### 表单页 / 页面弹窗

```php
<?php

use Sc\Util\HtmlStructureV2\Dsl\Actions;
use Sc\Util\HtmlStructureV2\Dsl\Dialogs;
use Sc\Util\HtmlStructureV2\Dsl\Fields;
use Sc\Util\HtmlStructureV2\Dsl\Forms;
use Sc\Util\HtmlStructureV2\Dsl\Pages;
use Sc\Util\HtmlStructureV2\Dsl\Tables;

$helpDialog = Dialogs::make('help', '帮助说明')
    ->content('<div>这里是说明内容</div>');

$editorDialog = Dialogs::make('editor', '编辑记录')
    ->saveUrl('/admin/demo/save')
    ->form(
        Forms::make('demo-editor')->addFields(
            Fields::hidden('id'),
            Fields::text('name', '名称')->required()
        )
    );

$formPage = Pages::make('系统设置')
    ->actions(
        Actions::create('查看说明')->dialog($helpDialog)
    )
    ->addSection(
        Forms::make('settings')->addFields(
            Fields::text('site_name', '站点名称')
        )
    );

$mixedPage = Pages::make('组合页面')
    ->addSection(
        Tables::make('demo-table')
            ->selection()
            ->rows([
                ['id' => 1, 'name' => 'A'],
            ])
            ->addColumns(
                Tables::column('名称', 'name')
            )
            ->rowActions(
                Actions::edit('编辑')->dialog('editor')
            )
    )
    ->dialogs($editorDialog);
```

### 常见列表页示例

适合“列表 + 筛选 + 一个编辑弹窗”的常见后台页。

```php
<?php

use Sc\Util\HtmlStructureV2\Dsl\Actions;
use Sc\Util\HtmlStructureV2\Dsl\Dialogs;
use Sc\Util\HtmlStructureV2\Dsl\Fields;
use Sc\Util\HtmlStructureV2\Dsl\Forms;
use Sc\Util\HtmlStructureV2\Dsl\Pages;
use Sc\Util\HtmlStructureV2\Dsl\Tables;

$editorDialog = Dialogs::make('editor', '编辑用户')
    ->saveUrl('/admin/user/save')
    ->form(
        Forms::make('user-editor')->addFields(
            Fields::hidden('id'),
            Fields::text('username', '用户名')->required(),
            Fields::password('password', '密码')->minLength(6)
        )
    );

$page = Pages::make('用户管理')
    ->actions(
        Actions::create('新建用户')->dialog($editorDialog)
    )
    ->addSection(
        Lists::make('user-list')
            ->filters(
                Forms::make('user-filters')->inline()->addFields(
                    Fields::text('keyword', '关键词')->placeholder('用户名 / 手机号')
                )
            )
            ->table(
                Tables::make('user-table')
                    ->dataUrl('/admin/user/list')
                    ->search('keyword', 'LIKE', 'username&mobile')
                    ->addColumns(
                        Tables::column('用户名', 'username')->minWidth(180),
                        Tables::column('状态', 'status')->displayBooleanTag('启用', '禁用')->searchable()
                    )
                    ->rowActions(
                        Actions::edit('编辑')->dialog($editorDialog),
                        Actions::request('删除')
                            ->post('/admin/user/delete')
                            ->payload([
                                'id' => '@row.id',
                            ])
                            ->type('danger')
                            ->icon('Delete')
                            ->confirm('确认删除当前用户？')
                            ->reloadTable()
                    )
            )
    );

echo $page->toHtml();
```

### 常见表单页示例

适合系统设置、配置页、详情编辑页。页面头部请求动作同样可用。

```php
<?php

use Sc\Util\HtmlStructureV2\Dsl\Actions;
use Sc\Util\HtmlStructureV2\Dsl\Fields;
use Sc\Util\HtmlStructureV2\Dsl\Forms;
use Sc\Util\HtmlStructureV2\Dsl\Pages;

$page = Pages::make('系统设置')
    ->actions(
        Actions::request('清理缓存')
            ->post('/admin/system/clear-cache')
            ->confirm('确认清理系统缓存？')
            ->successMessage('缓存已清理')
    )
    ->addSection(
        Forms::make('setting-form')->addFields(
            Fields::text('site_name', '站点名称')->required(),
            Fields::select('role_id', '默认角色')
                ->remoteOptions('/admin/role/options', 'id', 'title'),
            Fields::image('logo', '站点 Logo')
                ->uploadUrl('/admin/upload/image')
                ->uploadResponsePath('data.url')
        )
    );

echo $page->toHtml();
```

### 组合页面示例

适合需要手工拼多个区块的页面。

```php
<?php

use Sc\Util\HtmlStructureV2\Dsl\Actions;
use Sc\Util\HtmlStructureV2\Dsl\Fields;
use Sc\Util\HtmlStructureV2\Dsl\Forms;
use Sc\Util\HtmlStructureV2\Dsl\Lists;
use Sc\Util\HtmlStructureV2\Dsl\Pages;
use Sc\Util\HtmlStructureV2\Dsl\Tables;

$page = Pages::make('运营看板')
    ->actions(
        Actions::refresh('刷新订单列表')->forList('orders'),
        Actions::request('同步汇总')
            ->post('/admin/dashboard/sync-summary')
            ->forTable('summary-table')
            ->successMessage('汇总任务已提交')
    )
    ->addSection(
        Forms::make('quick-form')->addFields(
            Fields::text('keyword', '关键词')
        )
    )
    ->addSection(
        Lists::make('orders')
            ->filters(
                Forms::make('order-filters')->inline()->addFields(
                    Fields::text('keyword', '订单关键词')
                )
            )
            ->table(
                Tables::make('order-table')->dataUrl('/admin/order/list')
                    ->search('keyword', 'LIKE', 'order_no&buyer_name')
                    ->addColumns(
                        Tables::column('订单号', 'order_no'),
                        Tables::column('状态', 'status')->displayTag([
                            1 => ['label' => '待处理', 'type' => 'warning'],
                            2 => ['label' => '已完成', 'type' => 'success'],
                        ])
                    )
            )
    )
    ->addSection(
        Tables::make('summary-table')->rows([
            ['name' => '今日注册', 'value' => 18],
            ['name' => '今日付费', 'value' => 3],
        ])->addColumns(
            Tables::column('指标', 'name'),
            Tables::column('值', 'value')
        )
    );

echo $page->toHtml();
```

## 动作怎么写

当前动作建议分四类使用：

- `create/edit/submit/close`：弹窗动作，推荐先创建动作再用 `->dialog(...)` 绑定目标
- `request`：结构化异步请求动作，优先使用
- `import`：前端解析 Excel 后再提交的导入动作，适合后台批量导入场景
- `custom`：原生 JS 兜底，只在确实无法抽象时使用

### 弹窗动作推荐写法

推荐直接在动作后链式 `->dialog($dialog)`，页面会自动注册：

```php
$editorDialog = Dialogs::make('editor', '编辑用户')
    ->saveUrl('/admin/user/save')
    ->form(...);

Actions::create('新建用户')->dialog($editorDialog)
Actions::edit('编辑')->dialog($editorDialog)
```

也就是说：

- 写页面时，动作和弹窗定义可以放在一起
- 页面内部仍然按 dialog key 建 runtime 配置
- 同一个 dialog 可以被多个 action 复用

如果动作不在目标 table/list 的局部工具栏里，而是在页面头部或其他独立区块里，建议显式绑定：

```php
Actions::refresh('刷新订单')->forList('orders');
Actions::request('重算汇总')->post('/admin/dashboard/rebuild')->forTable('summary-table');
```

### 显式注册 `dialogs()`

只有在这些场景，再单独写 `dialogs()`：

- 想把 dialog 定义集中管理
- action 只想引用 key，不想直接持有 `Dialog`
- 想复用旧式 `editor/create-normal` 这类命名约定

```php
$editorDialog = Dialogs::make('editor', '编辑用户')->saveUrl('/admin/user/save')->form(...);

$page = Pages::make('用户列表')
    ->dialogs($editorDialog)
    ->actions(
        Actions::create('新建用户')->dialog('editor')
    )
    ->addSection(
        Tables::make('user-table')->rowActions(
            Actions::edit('编辑')->dialog('editor')
        )
    );
```

### 结构化请求动作

`Actions::request()` 当前支持这些能力：

- `->get('/url')`
- `->post('/url')`
- `->put('/url')`
- `->patch('/url')`
- `->request('/url', 'delete')`
- `->payload([...])`
- `->validateForm('profile')`
- `->payloadFromForm('profile')`
- `->submitForm('profile')`
- `->saveUrls('/create/url', '/update/url', 'id')`
- `->returnTo('/list/url')`
- `->confirm('确认消息')`
- `->loadingText('处理中...')`
- `->successMessage('成功')`
- `->errorMessage('失败')`
- `->reloadTable()`
- `->reloadPage()`
- `->dialog('editor')->closeAfterSuccess()`
- `->before(...)`
- `->afterSuccess(...)`
- `->afterFail(...)`
- `->afterFinally(...)`

### 导入动作

如果是“选择 Excel 文件，前端先解析，再把结果提交到后端”的场景，直接使用 `Actions::import()`。

默认行为：

- 点击后打开一个导入面板
- 面板内默认支持 Excel / CSV 文件导入
- 默认支持下载 Excel 模板
- 默认支持 JSON 导入
- 默认支持复制 AI 测试数据提示词
- 面板内自带导入预览和结果展示
- 前端使用 `xlsx` 解析 `xlsx/xls/csv`
- 默认把解析结果放到请求体的 `"rows"`
- `"rows"` 里的每一行默认会带 `"_row"`，用于和原版一样给后端标识导入行号
- 默认同时把导入列配置放到 `"import_column_info"`
- `"import_column_info"` 默认也按原版格式输出；如果某列配置了 `options` 简写映射，会自动归一化成 `[{ label, value }]`
- 仍然复用 `RequestAction` 的 `post()` / `payload()` / `successMessage()` / `reloadTable()` / `before()` / `afterSuccess()` 这一整套能力

最小写法：

```php
use Sc\Util\HtmlStructureV2\Dsl\Actions;

Actions::import('导入')
    ->post('/admin/user/import')
    ->importColumns([
        'name' => '名称',
        'mobile' => '手机号',
        'status' => [
            'title' => '状态',
            'options' => [1 => '启用', 0 => '禁用'],
        ],
    ])
    ->successMessage('导入成功')
    ->reloadTable();
```

如果导入字段基本和现有 V2 表单一致，也可以直接从表单声明自动推导：

```php
$form = Forms::make('user-form')->addFields(
    Fields::text('name', '名称'),
    Fields::select('status', '状态')->options([1 => '启用', 0 => '停用'])
);

Actions::import('导入')
    ->post('/admin/user/import')
    ->importColumnsFromForm($form);
```

iframe 子页表单也可以，但必须显式传入子页对应的 `Form` 或 `Page` 对象，不做纯 URL 猜测：

```php
$childForm = Forms::make('user-form')->addFields(
    Fields::text('name', '名称')
);

$childPage = Pages::make('用户表单页')->addSection($childForm);

Actions::import('导入')
    ->post('/admin/user/import')
    ->importColumnsFromPage($childPage, 'user-form');
```

如果默认请求体不够用，也可以继续自定义：

```php
Actions::import('导入')
    ->post('/admin/user/import')
    ->importColumns([
        'name' => '名称',
    ])
    ->payload('(ctx) => ({ rows: ctx.import?.rows ?? [], fileName: ctx.import?.fileName ?? "" })');
```

导入动作在运行时会额外提供：

- `ctx.import.rows`
- `ctx.import.headers`
- `ctx.import.fileName`
- `ctx.import.sheetName`

常用补充配置：

- `importDialogTitle('导入用户资料')`: 设置导入弹窗标题
- `importTemplateFileName('用户导入模板.xlsx')`: 设置“下载模板”的文件名；不带后缀也可以
- `enableImportJson(false)`: 关闭 JSON 导入页签
- `enableImportAiPrompt(false)`: 关闭 “复制 AI 提示词” 按钮
- `importAiPromptText('请生成 10 条测试 JSON 数据...')`: 自定义 AI 提示词；不传时会按 `importColumns()` 自动生成
- `importColumnsFromForm($form, [...])`: 从 V2 表单自动推导顶层导入列，第二个参数可覆盖同名字段配置
- `importColumnsFromPage($page, 'form-key', [...])`: 从 V2 页面中定位表单并自动推导；页面只有一个表单时可省略 form key
- `importColumnsFromDialog($dialog, $iframePage, 'form-key', [...])`: 从 V2 dialog 推导；普通 form dialog 可直接传 dialog，iframe dialog 需显式补充子页 Form/Page 来源
- `importRowsKey('items')`: 把默认 `"rows"` 改成别的字段名
- `importColumnInfoKey('columnInfo')`: 改导入列说明字段名；传 `null` 表示不自动附带
- `importAccept('.xlsx,.xls')`: 限制文件类型
- `importHeaderRow(2)`: 指定第几行是表头，按 1 开始计数

### 独立表单页加载

独立表单页现在也可以像弹窗一样直接声明“编辑态详情加载”，不需要先在 PHP 里手写
`$id = request()->query('id')` 再分支查模型。

如果数据本身已经在 PHP 层拿到了，也可以直接：

```php
Forms::make('profile')->setData($data);
```

`setData($data)` 会按当前表单 schema 初始化，只保留已声明字段；适合从 V1 的 `$form->setData($data)` 直接迁移过来。

```php
use Sc\Util\HtmlStructureV2\Dsl\Forms;
use Sc\Util\HtmlStructureV2\Dsl\Fields;

Forms::make('profile')
    ->modeQueryKey('id')
    ->load('/admin/profile/detail')
    ->addFields(
        Fields::text('name', '名称')->required()
    );
```

默认规则：

- `modeQueryKey('id')` 默认值就是 `id`
- 当前页面 query 中该值非空时，页面模式为 `edit`，否则为 `create`
- `load('/detail')` 默认只在 `edit` 模式下触发
- 如果没写 `loadPayload()`，会默认提交 `["id" => "@page.query.id"]` 这种同名参数
- 若接口返回结构不是直接对象，可用 `loadDataPath('data.form')` 指定回填路径

如果查询参数名不是 `id`，可以直接改：

```php
Forms::make('profile')
    ->modeQueryKey('user_id')
    ->load('/admin/profile/detail');
```

### 表单保存快捷方式

独立表单页现在推荐把保存配置和提交流程事件直接挂在 `Form` 上，再用
`Actions::save()` 触发提交；不要再手写 `ctx.vm.validateSimpleForm(...)` /
`ctx.vm.getSimpleFormModel(...)` 这类内部运行时方法名。

如果是常规表单页，希望像原版那样在表单底部直接放“重置 / 保存 / 取消”，可以把动作写在
`Form::footerActions()` 上：

```php
use Sc\Util\HtmlStructureV2\Dsl\Actions;
use Sc\Util\HtmlStructureV2\Dsl\Fields;
use Sc\Util\HtmlStructureV2\Dsl\Forms;

$form = Forms::make('profile')
    ->saveUrls('/admin/profile/create', '/admin/profile/update')
    ->footerActions(
        Actions::resetForm(),
        Actions::save('保存')
    )
    ->addFields(
        Fields::text('name', '名称')->required()
    );
```

`footerActions()` 只作用于普通表单，不用于列表筛选表单；动作默认会优先绑定当前表单作用域，
所以单表单页面通常不需要再手动写 `submitForm('profile')` / `Actions::resetForm('重置', 'profile')`。

```php
use Sc\Util\HtmlStructureV2\Dsl\Actions;
use Sc\Util\HtmlStructureV2\Dsl\Forms;

Forms::make('profile')
    ->saveUrls('/admin/profile/create', '/admin/profile/update')
    ->loadingText('保存中...')
    ->successMessage('成功')
    ->returnTo('/admin/profile/lists');

Actions::save('保存');
```

如果要拆开控制，也可以分别写：

```php
Actions::request('保存')
    ->post('/admin/profile/save')
    ->validateForm('profile')
    ->payloadFromForm('profile');
```

规则：

- `submitForm("profile")` 等价于 `validateForm("profile")->payloadFromForm("profile")`
- `Actions::save("保存")` 默认会对当前运行时里唯一可解析的表单执行提交；多表单页面请显式写 `->submitForm("profile")`
- `Actions::resetForm("重置")` 默认会把目标表单恢复到最近一次初始值快照，而不是 schema 默认值
- `Form::saveUrls("/create", "/update")` 会按当前表单的 `modeQueryKey()` 自动切换请求地址
- `Form::saveUrls("/save")` 表示新建和编辑都走同一个保存地址
- 不传表单 key 时，只会在当前运行时能唯一定位表单时自动解析
- 自动解析优先当前 dialog 表单，其次页面内唯一的非 dialog 表单
- 当前初始值快照默认来自 `Form::setData()` / 首次渲染值；独立表单页 `load()` 成功后、form 弹窗打开并初始化后，也会刷新这份快照
- `payload()` 会覆盖 `payloadFromForm()`，适合你要完全自定义请求体时使用
- `Form::returnTo("/list")` 等价于给表单 `submitSuccess` 事件追加 `Events::returnTo("/list")->hostTable()`
- `Form::returnTo()` / `RequestAction::returnTo()` 的 `url` 现在可选；若当前不在宿主 iframe 弹窗中且又没传 URL，则静默跳过

最常见的独立表单页头部动作现在可以直接写：

```php
use Sc\Util\HtmlStructureV2\Dsl\Actions;
use Sc\Util\HtmlStructureV2\Dsl\Forms;

$form = Forms::make('profile')
    ->saveUrls('/admin/profile/create', '/admin/profile/update')
    ->successMessage('成功')
    ->returnTo('/admin/profile/lists');

Actions::back('/admin/profile/lists');
Actions::save('保存');
```

### 请求动作上下文取值

`payload()` 里可以直接写这些 token：

- `@row.id`
- `@filters.keyword`
- `@filters.status`
- `@import.rows`
- `@import.fileName`
- `@dialogs.create-normal.name`
- `@forms.setting-form.site_name`
- `@page.query.id`
- `@page.mode`

例如：

```php
Actions::request('上架')
    ->post('/admin/product/publish')
    ->payload([
        'id' => '@row.id',
        'keyword' => '@filters.keyword',
    ])
    ->reloadTable();
```

### 请求动作自定义钩子

`before/afterSuccess/afterFail/afterFinally` 可接收 `JsExpression` 或结构化 `Events::*`；
需要写 JS 时，推荐写成函数：

```php
use Sc\Util\HtmlStructureV2\Support\JsExpression;

Actions::request('同步')
    ->post('/admin/product/sync')
    ->afterSuccess(
        JsExpression::make('(ctx) => console.log(ctx.row, ctx.payload)')
    );
```

钩子里常用上下文：

- `ctx.row`
- `ctx.filters`
- `ctx.dialogs`
- `ctx.forms`
- `ctx.formScope`
- `ctx.payload`
- `ctx.error`
- `ctx.request`
- `ctx.vm`
- `ctx.resolveFormScope()`
- `ctx.validateForm()`
- `ctx.getFormModel()`
- `ctx.cloneFormModel()`
- `ctx.setFormModel(values)` / `ctx.setFormModel('formKey', values)`
- `ctx.initializeFormModel(values)` / `ctx.initializeFormModel('formKey', values)`

### iframe 子页面宿主桥

V2 iframe 子页面里，优先推荐直接用结构化事件；只有确实需要自定义逻辑时，再调用宿主桥方法。

最常见的“保存成功后返回列表页；如果当前是 iframe 子页则先刷新宿主表格并关闭宿主弹窗”现在可以直接写：

```php
use Sc\Util\HtmlStructureV2\Dsl\Events;

Actions::request('保存')
    ->post('/admin/profile/save')
    ->submitForm('profile')
    ->afterSuccess(
        Events::returnTo('/admin/profile/lists')->hostTable()
    );
```

取消按钮也可以直接写：

```php
Actions::custom('取消')->onClick(Events::returnTo());
```

结构化宿主桥事件：

- `Events::closeHostDialog()`
- `Events::reloadHostTable($table = null)`
- `Events::returnTo($url = null)->hostTable($table = null)`

说明：

- `Events::returnTo()` 只有在当前页面确实由启用 `iframeHost()` 的 V2 iframe 弹窗打开时，才会优先关闭宿主弹窗
- 普通页面或普通 tab iframe 页面会直接回退到 `url` 跳转；若 `url` 为空，则静默跳过，不会把“存在 parent window”误判成宿主弹窗

如果确实要写 JS，动作上下文里也能直接调用宿主桥方法，不需要再手写
`window.parent.postMessage(...)`：

```php
use Sc\Util\HtmlStructureV2\Support\JsExpression;

Actions::request('保存')
    ->post('/admin/profile/save')
    ->submitForm('profile')
    ->afterSuccess(JsExpression::make(<<<'JS'
(ctx) => {
  ctx.reloadHostTable();
  ctx.closeHostDialog();
}
JS));
```

可用宿主桥方法：

- `ctx.notifyDialogHost(payload)`
- `ctx.closeHostDialog(dialogKey = null)`
- `ctx.reloadHostTable(tableKey = null, dialogKey = null)`
- `ctx.openHostDialog(dialogKey, row = null, tableKey = null)`
- `ctx.openHostTab(route | { route, title?, index? }, title = '', index = null)`
- `ctx.setHostDialogTitle(title, dialogKey = null)`
- `ctx.setHostDialogFullscreen(value = true, dialogKey = null)`
- `ctx.toggleHostDialogFullscreen(dialogKey = null)`
- `ctx.refreshHostDialogIframe(dialogKey = null)`

如果是 `Actions::custom()` 这类直接执行模板表达式的场景，也可以直接走页面 runtime 方法：

```php
Actions::custom('取消')->onClick('vm.closeHostDialog()');
```

## 事件怎么写

`Form / Table / List / Dialog / Action / Layouts / Blocks / Displays` 都统一走 `->on($event, $handler)` / `->events([...])`。默认优先用结构化 `Events::*()`，只有在确实抽不出结构时再退回 `JsExpression::make('(ctx) => { ... }')`。

```php
use Sc\Util\HtmlStructureV2\Dsl\Events;
use Sc\Util\HtmlStructureV2\Support\JsExpression;

Forms::make('profile')
    ->on('validateFail', Events::message('表单校验失败', 'error'));

Tables::make('orders')
    ->on('loadSuccess', Events::message('订单已刷新', 'success'));

Dialogs::make('editor', '编辑')
    ->on('afterOpen', Events::message('弹窗已打开'));

Actions::request('同步')
    ->on('success', Events::reloadTable('orders'));

Actions::request('回填表单')
    ->on('success', Events::setFormModel([
        'status' => 1,
        'remark' => '@response.data.remark',
    ])->form('profile'));

Actions::request('按 schema 初始化表单')
    ->on('success', Events::initializeFormModel('@response.data')->form('profile'));

Blocks::button('查看源数据')
    ->on('click', Events::openUrl('/source/view', ['id' => '@forms.profile.source_id'])->newTab());
```

当前内置的结构化事件包括：

- `Events::openUrl()`
- `Events::openDialog()`
- `Events::closeDialog()`
- `Events::reloadTable()`
- `Events::reloadList()`
- `Events::reloadPage()`
- `Events::closeHostDialog()`
- `Events::reloadHostTable()`
- `Events::returnTo()`
- `Events::setFormModel()->form('formKey')`
- `Events::initializeFormModel()->form('formKey')`
- `Events::message()`
- `Events::request()`

轻组件上的 DOM 事件也走同一套事件对象协议；如果它们渲染在 `Forms::custom()` 里，还会额外注入 `ctx.model`，便于直接用 `@model.source_id` 这类 token 取当前表单片段数据。

当前已经接入 runtime 的常用事件：

- `Form`: `submitBefore` / `submitSuccess` / `submitFail` / `submitFinally` / `validateSuccess` / `validateFail` / `optionsLoaded` / `optionsLoadFail` / `uploadSuccess` / `uploadFail` / `arrayRowAdd` / `arrayRowRemove` / `arrayRowMove`
- `Table`: `loadBefore` / `loadSuccess` / `loadFail` / `pageChange` / `pageSizeChange` / `sortChange` / `selectionChange` / `dragSort` / `deleteSuccess` / `deleteFail`
- `List`: `filterSubmit` / `filterReset` / `reload`
- `Dialog`: `beforeOpen` / `afterOpen` / `beforeClose` / `afterClose` / `submitSuccess` / `submitFail`
- `RequestAction`: `click` / `before` / `success` / `fail` / `finally`

### 复杂联动怎么处理

遇到“字段显示/禁用/说明块内容”依赖远端选项、数组行数量、上传结果、弹窗上下文等复杂状态时，推荐按下面顺序处理：

当前这类场景的分析结论是：

- “手动触发远端请求再更新选项”已经是合理需求，不应再要求调用方直接改 `optionState`
- 单字段值更新已有 `vm.setFormPathValue(scope, fieldName, value)`，够用
- 选项更新原先缺少正式 API，现已补成 `vm.setFieldOptions(scope, fieldName, options)`，后续优先使用它
- `setFormModel()` 仍适合整表替换，不适合“请求成功后顺手改几个字段”

1. 先看 DSL 是否已有现成功能

- 静态/远端选项：`options()` / `remoteOptions()`
- 选项联动回填：`linkageUpdate()` / `linkageUpdates()`
- 显示/禁用/只读条件：`visibleWhen()` / `disabledWhen()` / `readonlyWhen()`
- 结构化事件：`Events::*()`

2. 如果最终展示条件依赖“运行时派生状态”，先把它转成 `model` 字段

- `visibleWhen()` / `disabledWhen()` / `readonlyWhen()` 默认只保证 `model` 可用
- 不建议直接在 `visibleWhen()` 里依赖 `dialogOptions` / `tableStates` / `vm.xxx` 这类页面 runtime 变量
- 更稳的做法是：在事件回调里把派生结果写回表单模型，再让展示逻辑只依赖 `model`

典型例子：根据某个远端 select 的选项数量决定是否显示补充字段。

```php
use Sc\Util\HtmlStructureV2\Support\JsExpression;

Forms::make('qa-case')
    ->addFields(
        Fields::hidden('_typeOptionCount')->default(0),
        Fields::select('type_id', '案件分类')
            ->remoteOptions('/admin/qa/type-options', 'id', 'name')
            ->remoteOptionsDependsOn('scene_id'),
        Fields::text('remark', '补充说明')
            ->visibleWhen('model._typeOptionCount > 1')
    )
    ->on('optionsLoaded', JsExpression::make(<<<'JS'
({ fieldName, options, scope, vm }) => {
  if (fieldName !== 'type_id') return;
  vm.setFormPathValue(
    scope,
    '_typeOptionCount',
    Array.isArray(options) ? options.length : 0
  );
}
JS))
    ->on('optionsLoadFail', JsExpression::make(<<<'JS'
({ fieldName, scope, vm }) => {
  if (fieldName !== 'type_id') return;
  vm.setFormPathValue(scope, '_typeOptionCount', 0);
}
JS));
```

这个模式本质上是：

- 事件层负责“拿到真实运行时数据”
- `vm` 负责“把派生状态写回当前作用域”
- `visibleWhen()` / `disabledWhen()` / `readonlyWhen()` 只负责“读 model 做纯展示判断”

3. 如果不是字段，而是说明块/自定义块，也尽量复用同一套思路

- 推荐先把派生值写入 `model`
- 再在 `Forms::custom()` / 轻组件根节点上写 `v-if`

例如：

```php
Forms::custom(
    Blocks::text('当前分类可继续补充说明')
)->attr('v-if', 'model._typeOptionCount > 1');
```

### 手动刷新选项与回填

这类需求现在统一按下面三档处理：

1. 字段已经声明了 `remoteOptions()`，只是改成“手动点一下再刷新”

```php
Forms::custom(
    Blocks::button('刷新分类')->type('primary')
        ->on('click', JsExpression::make(<<<'JS'
({ vm }) => {
  return vm.loadFormFieldOptions('qa-case', 'type_id', true)
    .then((options) => {
      vm.setFormPathValue('qa-case', 'type_id', '');
      ElementPlus.ElMessage.success(`已刷新 ${Array.isArray(options) ? options.length : 0} 个选项`);
    });
}
JS))
);
```

2. 需要自己请求接口，再把返回值替换成字段选项

```php
Forms::custom(
    Blocks::button('查询分类')->type('primary')
        ->on('click', JsExpression::make(<<<'JS'
async ({ vm, model }) => {
  const response = await axios.get('/admin/qa/type-options', {
    params: {
      keyword: model.keyword || '',
    }
  });

  const rows = Array.isArray(response?.data?.data?.options)
    ? response.data.data.options
    : [];

  vm.setFieldOptions('qa-case', 'type_id', rows);
  ElementPlus.ElMessage.success('分类选项已更新');
}
JS))
);
```

`setFieldOptions()` 会负责把传入数组归一化为标准选项结构，并同步更新当前字段的 `optionLoading / optionLoaded` 状态。

3. 请求成功后，不仅要更新选项，还要顺手改几个表单值

```php
Forms::custom(
    Blocks::button('查询并回填')->type('primary')
        ->on('click', JsExpression::make(<<<'JS'
async ({ vm, model }) => {
  const scope = 'qa-case';
  const response = await axios.get('/admin/qa/type-options', {
    params: {
      keyword: model.keyword || '',
    }
  });

  const rows = Array.isArray(response?.data?.data?.options)
    ? response.data.data.options
    : [];
  const options = vm.setFieldOptions(scope, 'type_id', rows);
  const first = options[0] || null;

  vm.withDependencyResetSuspended?.(scope, () => {
    vm.setFormPathValue(scope, 'type_id', first?.value ?? '');
    vm.setFormPathValue(scope, 'type_name', first?.label ?? '');
    vm.setFormPathValue(scope, 'type_code', first?.code ?? '');
  });

  ElementPlus.ElMessage.success('已更新选项并回填表单');
}
JS))
);
```

这里的建议很明确：

- 改一个字段：`setFormPathValue()`
- 改多个字段：连续调用多个 `setFormPathValue()`；如果这些字段还会触发依赖重置，再包一层 `withDependencyResetSuspended()`
- 不要用 `setFormModel()` 去做“局部补丁更新”

### 原生 JS 兜底

确实没法抽象时，再用 `Actions::custom()`：

```php
Actions::custom('复制链接')->onClick(JsExpression::make(<<<'JS'
(async () => {
  await navigator.clipboard.writeText(String(scope.row.share_url || ''));
  ElementPlus.ElMessage.success('链接已复制');
})()
JS))->confirm('确认复制当前链接？');
```

这条通道是 escape hatch，不建议作为主路径。

### 极端场景兜底

如果出现下面这种场景，可以直接进入原生兜底，不必继续硬套 DSL：

- 当前所有 `Fields::*()` / `Events::*()` 组合都无法表达需求
- 逻辑依赖多个运行时状态交叉计算
- 需要直接操作当前页面的 table / dialog / form runtime
- 需要短期验证某个复杂交互，后续再沉淀 DSL

推荐的兜底顺序：

1. 优先用结构化事件

- 能用 `Events::setFormModel()` / `Events::initializeFormModel()` / `Events::reloadTable()` 解决时，不要退回原生 JS

2. 再用 `JsExpression`，但尽量只写在事件回调里

- 推荐写法：`({ scope, model, vm, ...ctx }) => {}`
- 不推荐把大段业务逻辑直接塞进字段属性表达式里

3. 在原生回调里，优先调用 runtime 暴露的方法，而不是直接改内部状态对象

- 表单：`vm.setFormPathValue(scope, fieldName, value)` / `vm.setFieldOptions(scope, fieldName, options)` / `vm.loadFormFieldOptions(scope, fieldName, true)`
- 表格：`vm.reloadTable(tableKey)` / `vm.getTableSelection(tableKey)`
- 弹窗：`vm.openDialog(dialogKey, row, tableKey)` / `vm.closeDialog(dialogKey)`
- 宿主桥：`vm.closeHostDialog()` / `vm.reloadHostTable()` / `vm.openHostTab(...)`

4. 只有 runtime 方法也不够时，才直接访问页面响应式状态

- 例如 `vm.dialogForms` / `vm.tableStates` / `vm.dialogVisible`
- 例如历史兼容代码里直接改 `vm.getOptionState(scope)` 返回值
- 这属于最后兜底，写法灵活，但后续重构时也最容易受影响

一个典型的原生兜底例子：

```php
Forms::make('profile')->on('optionsLoaded', JsExpression::make(<<<'JS'
({ fieldName, options, scope, vm, model }) => {
  if (fieldName !== 'dept_id') return;

  const count = Array.isArray(options) ? options.length : 0;
  vm.setFormPathValue(scope, '_deptOptionCount', count);

  if (count === 1 && !model.dept_id) {
    vm.setFormPathValue(scope, 'dept_id', options[0]?.value ?? null);
  }
}
JS));
```

这里要点只有两个：

- 复杂逻辑放到事件回调里
- 回调结果回落到 `model` 或显式 runtime 方法，不把页面模板变成“直接操作内部状态”的大杂烩

## 附录：筛选和排序

- `filters(Form)` 只描述筛选输入 UI；也支持直接写成 `filters(Fields::text(...), Fields::select(...))`
- 省略 `filters()` 时，`List` 会尝试按 `searchable()/search()/searchSchema()` 自动生成默认筛选项；这些自动项默认隐藏 label，只保留 placeholder；已显式声明的字段会保留，其余缺失项继续自动补齐
- `Fields::*()` 的 label 参数现在可省略；省略时默认隐藏该字段标签，想保留说明可显式传第二参，或单独调用 `placeholder()`
- `Fields::icon()` 用于选择 Element Plus 图标，默认提供“搜索面板 + 手输图标名”组合输入
- 想在筛选条里放更多条件时，可对显式表单使用 `->showLabels(false)` 隐藏字段标签，只保留 placeholder
- `Table::searchSchema()` 适合显式定义完整筛选协议
- `Table::search($name, $type, $field)` 适合逐项追加筛选定义
- `Column::searchable()` 适合同名字段的快捷声明；用于 `List` 自动筛选时，会优先继承当前列的 label 和 display 信息
- `Column::searchable('LIKE', 'table.field')` 可以映射后端真实字段
- `displayMapping()` / `displayTag()` / `displayBoolean*()` + `searchable()` 会优先自动推导成 select
- `Column::sortable()` 开启远程排序
- `Column::sortable('table.field')` 可以映射后端真实排序字段

### 表格拖拽排序

`Table` 现在支持原生 `dragSort()`，平铺表格和树表都可以直接声明：

```php
use Sc\Util\HtmlStructureV2\Dsl\Tables;

Tables::make('route-table')
    ->dataUrl('/admin/route/list')
    ->pagination(false)
    ->rowKey('id')
    ->tree()
    ->dragSort()
    ->on('dragSort', <<<'JS'
({ movedRow, anchorRow, isMoveDown, oldParentRow, newParentRow, vm }) => {
  if (!movedRow || !anchorRow) {
    return;
  }

  return axios.post('/admin/route/sort', {
    oldId: anchorRow.id,
    newId: movedRow.id,
    isMoveDown: isMoveDown ? 1 : 0,
  }).then(() => {
    ElementPlus.ElMessage.success('排序已更新');
    return vm.reloadTable('route-table');
  });
}
JS);
```

- `rowKey()` 是必需项；`tree()` / `treeProps()` 用来声明树表
- `dragSort()` 会在“操作”列自动补一个“排序”拖拽手柄按钮，默认 `type=primary`、`icon=Rank`
- 可通过 `dragSortLabel()` / `dragSortType()` / `dragSortIcon()` / `dragSortConfig()` 调整文案、样式和 Sortable 额外参数
- `dragSort` 事件里最常用的是 `movedRow`、`anchorRow`、`previousRow`、`nextRow`、`visibleRows` / `flatRows`、`oldIndex`、`newIndex`、`isDown` / `isMoveDown`
- 树表场景还会补充 `oldParentRow` / `newParentRow` / `sameParent`

## 附录：字段能力分层

- 通用字段状态：`disabled()` / `disabledWhen()` / `readonly()` / `readonlyWhen()`
- `readonly()` 会优先输出组件自身的 `readonly`；不支持 readonly 的组件会自动退化为 `disabled`
- 当前直接走 `readonly` 的字段类型：`text` / `password` / `textarea` / `date` / `datetime` / `date_range`
- 其余如 `select` / `radio` / `checkbox` / `cascader` / `upload` / `switch` / `picker` / `editor` 等会自动退化为 `disabled`
- `Form::readonly()` 会把整表单切为只读，并自动关闭 array/table 的新增、删除、排序入口；筛选表单默认提交/重置按钮也会隐藏
- `Forms::section()` / `Forms::inline()` / `Forms::grid()` / `Forms::tabs()` / `Forms::tab()` / `Forms::collapse()` / `Forms::collapseItem()` / `Forms::object()` / `Forms::arrayGroup()` / `Forms::table()` 也支持 `readonly()`，只影响各自子树
- `Fields::toggle()`、`Fields::hidden()` 只保留最小通用能力
- `Fields::text()`、`Fields::textarea()`、`Fields::password()`、`Fields::icon()` 才暴露文本校验快捷方法
- `Fields::select()`、`Fields::radio()`、`Fields::checkbox()` 才暴露 `options()`、`remoteOptions()`、`linkageUpdate()`
- `Fields::cascader()` 才暴露 `cascaderProps()`、`emitPath()`、`checkStrictly()`、`closeAfterSelection()`
- `Fields::upload()`、`Fields::image()` 才暴露上传相关方法
- 上传字段值约定：单图字段存字符串路径；普通文件和多图字段存 `[{ uid, url, name, status }]`
- 上传时会默认提示“文件上传中,请稍后...”，成功/失败也会自动提示具体文件名
- `Fields::number()` 才暴露 `minValue()`、`maxValue()`、`step()`、`precision()`
- `Fields::date()`、`Fields::datetime()`、`Fields::daterange()` 才暴露 `format()`、`valueFormat()`

## 附录：表格列展示能力

- `Column::displayMapping()` 枚举值转文字
- `Column::displayTag()` 状态标签
- `Column::displayImage()` 单图预览
- `Column::displayImages()` 多图预览
- `Column::displayBoolean()` 布尔文案
- `Column::displayBooleanTag()` 布尔标签
- `Column::displayDate()`、`Column::displayDatetime()` 日期格式化
- `Column::displayPlaceholder()` 空值占位

## 附录：远程筛选协议

默认输出以下查询结构：

- `search[search]`
- `search[searchType]`
- `search[searchField]`
- `page`
- `pageSize`
- `order[field]`
- `order[order]`

## 附录：远程列表返回结构

支持以下常见结构：

- `{ code, data: { data: [], total } }`
- `{ code, data: { list: [], total } }`
- `{ code, data: { rows: [], total } }`
- `{ data: [] }`
- `{ rows: [] }`

## 附录：运行时脚本

- 列表页运行时拆在 `Theme/ElementPlusAdmin/Runtime/scripts/list/`
- 普通表单运行时拆在 `Theme/ElementPlusAdmin/Runtime/scripts/simple/`
- 结构化请求动作 runtime 在 `request-action-factory.js`
- 公共 helper 在 `runtime-helpers.js`
- 公共表单 runtime 工厂在 `form-runtime-factory.js`
- 渲染时会优先把 runtime 按原始模块文件名发布到 `public/js/sc-v2/`，页面里直接加载 `runtime-helpers.js`、`managed-dialog-factory.js` 这类具体脚本，便于定位问题
- 主题 CSS 也会从 `Theme/ElementPlusAdmin/Runtime/styles/element-plus-admin-theme.css` 自动发布到 `public/js/sc-v2/`；如果发布失败，会回退成 inline style
- URL 通过查询参数 hash 做缓存失效，不靠 bundle 文件名隐藏真实模块名
- 如果静态文件发布失败，会自动回退到“按模块分段内联”，不会再退回成单个超大 inline script

## 附录：目录结构

- `Components/`: 表单、字段、表格、动作、弹窗
- `Components/Block/`: 轻量内容块
- `Components/Layout/`: 轻量布局容器
- `Components/Display/`: 轻量展示组件
- `Components/Fields/`: 各字段类型实现
- `Dsl/`: 推荐使用的 DSL 入口
- `Page/`: 页面模型，当前只保留 `Page`
- `Theme/`: 主题适配层，目前提供 `ElementPlusAdminTheme`
- `Theme/ElementPlusAdmin/`: 具体 renderer
- `Theme/ElementPlusAdmin/Runtime/`: runtime builder 与脚本加载器
- `Theme/ElementPlusAdmin/Runtime/scripts/list/`: 列表页运行时分片
- `Theme/ElementPlusAdmin/Runtime/scripts/simple/`: 简单表单运行时分片
- `Theme/ElementPlusAdmin/Runtime/scripts/request-action-factory.js`: 结构化请求动作的共享 runtime

## 附录：设计说明

下面这些更偏设计约定，放在附录里，主要用于理解为什么 V2 这样组织；日常写页面时不需要先看这里。

- 不依赖旧版 `Html::create()` 全局状态
- V2 当前仍在建设期，默认以最新 DSL/运行时设计为准；调整时不要求兼容旧写法，也不保留历史 API 壳层
- `Form / Table / Dialog / List / Action` 属于重交互组件，继续走显式 key、构建期校验、运行时隔离
- `Layouts / Blocks / Displays` 属于轻组件，优先做到零注册、零运行时、自由组合
- `Form` 不再长期限定为“字段数组”；后续以表单节点树为基础，字段只是叶子节点
- `List` 负责 `filters + table + managed dialogs` 这一组复合交互，可以和其他独立组件任意组合
- 筛选协议归 `Table/Column` 定义；`filters()` 用于显式自定义筛选 UI，不写时会尝试按 searchable/searchSchema 自动生成默认筛选表单，写了时也会自动补齐缺失筛选项
- `Dialog` 自己持有保存地址，推荐通过 `Actions::create('新建')->dialog(...)`、`Actions::edit('编辑')->dialog(...)` 就近绑定，`dialogs()` 只保留给高级场景
- 常见动作优先走结构化 DSL，`JsExpression` 只保留为兜底 escape hatch
- 字段能力按类型收口，不再所有字段都暴露同一批方法
- 主题层按 `Theme -> Renderer -> RuntimeBuilder -> runtime scripts` 分层
- 不再提供 `V2::xxx()` 门面
- 不回退到旧版 `Html::js()->vue` 这类全局单例模式
- 重组件强调“渲染独立但运行时仍受页面聚合管理”；轻组件强调“渲染独立且运行时自包含”
