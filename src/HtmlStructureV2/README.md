# HtmlStructureV2

`HtmlStructureV2` 是一套独立于旧 `HtmlStructure` 的后台页面 DSL。它把后台开发拆成三层：

- 页面入口：`Pages::make()`
- 重交互组件：`Forms / Fields / Tables / Dialogs / Actions / Lists`
- 轻布局展示组件：`Layouts / Blocks / Displays`

目标不是继续堆一个大门面，而是让“页面是什么场景”和“页面里有哪些组件”都清楚可读。

## 页面入口

- `Pages::make()` 页面容器

## 页面组织建议

- 所有页面统一从 `Pages::make()` 开始
- `Pages::make('...')` 的第一个参数用于 HTML `<title>`
- 页面可视页头统一通过 `->header(...)` 自定义组合
- 页面根背景默认白色，可通过 `->backgroundPreset('white'|'muted'|'transparent')` 或 `->background('linear-gradient(...)')` 覆盖
- 表单页直接在 `addSection()` 里放 `Forms::make()`
- 列表页直接在 `addSection()` 里放 `Lists::make()` 或 `Tables::make()`
- 需要混合布局时继续组合 `Forms / Lists / Tables / Layouts / Blocks`

## 组件入口

- `Forms::make()` 表单容器
- `Lists::make()` 可复用列表组件
- `Fields::*()` 字段工厂
- `Tables::make()` / `Tables::column()` 表格和列
- `Dialogs::make()` 弹窗
- `Actions::*()` 动作按钮，当前支持 `create/edit/submit/close/refresh/request/custom`
- `Layouts::*()` 轻量布局容器
- `Blocks::*()` 轻量标题/说明/分割线/提示块
- `Displays::*()` 轻量详情展示块

列表列的展示型配置统一使用 `display*` 前缀方法，例如 `displayTag()`、`displayDatetime()`、`displayImage()`、`displayPlaceholder()`，用来明确区分“展示设置”和“结构/查询设置”。

`Table` 在运行时按组件 `key` 隔离状态和事件，同一个页面里可以组合多个独立 table / list-like 区块；同页 `table key / list key` 都必须唯一。和表格本身强相关的行为配置优先收口到 `Table`，例如 `deleteUrl()` / `deleteKey()`。

`List` 是复合组件，需要混合布局时，直接在 `Pages::make()` 里组合 `Lists::make()`、`Tables::make()`、`Forms::make()` 即可。

## 表格导出

V2 现在支持原生表格导出，不再依赖 V1 渲染链路。

- `Table::export('文件名')` 开启导出按钮
- `Table::exportLabel()` / `exportType()` / `exportIcon()` 可调整按钮表现
- `Table::exportQuery([...])` 可追加远程导出请求参数；默认会带上 `is_export=1`
- `Table::openExportExcel()` 作为旧写法兼容别名继续可用

导出规则：

- 当前有勾选行时，优先导出当前选中数据
- 没有勾选时，远程表格会复用当前筛选和排序重新拉取全量数据
- `Column::onlyExportExcel()` 会进入导出但不在页面展示
- 页面可见列默认参与导出；若用户在“列设置”里手动隐藏某列，该列也会同步从导出结果中排除

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

## 状态切换按钮栏

V2 现在支持表格顶部的快速状态切换按钮栏，适合列表页里高频的枚举筛选。

- `Table::statusToggle($name, $options, $label = null)` 是原生写法
- `Table::statusTogglesNewLine()` 控制多组按钮按换行模式展示
- `Table::addStatusToggleButtons()` / `setStatusToggleButtonsNewLine()` 作为旧写法兼容别名继续可用

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

`Pages::make()` 下直接放置的 `Form / Table / ListWidget` 默认按“组件本体”渲染，不自动补一层 card 壳；如果需要卡片、标题、分组说明，显式用 `Layouts::card()` / `Blocks::title()` 组合。

混合布局里如果页面头部动作需要显式指向某个 list / table，统一使用 `->forList('orders')` / `->forTable('audit-table')`，不要再依赖“当前主表”这类隐式约定。显式 key 写错时不会再回退到主 list，而是会在构建期直接抛错，避免误绑到别的组件。

像 `Actions::refresh()`、`RequestAction::reloadTable()` 这类“数据目标型动作”，如果当前位置没有局部 table/list 上下文，也没有显式 `forTable()/forList()`，现在会在构建期直接报错，不再生成隐式 `loadTableData()` 之类的回退调用。

`Action::when()` 现在支持两种语义：`->when($condition)` 会直接在 PHP 层禁用当前动作，不再参与渲染、target 校验和 dialog 收集；`->when($condition, fn (Action $action) => ...)` 则继续作为条件链式配置使用。

仓库里可直接渲染的综合示例见 `test/Fixtures/View/ScEngine/v2-full-demo.sc.php`，对应测试入口是 `ScEngine.v2-full-demo`。

## 设计约束

- 不依赖旧版 `Html::create()` 全局状态
- V2 当前仍在建设期，默认以最新 DSL/运行时设计为准；调整时不要求兼容旧写法，也不保留历史 API 壳层
- `Form / Table / Dialog / List / Action` 属于重交互组件，继续走显式 key、构建期校验、运行时隔离
- `Layouts / Blocks / Displays` 属于轻组件，优先做到零注册、零运行时、自由组合
- `Form` 不再长期限定为“字段数组”；后续以表单节点树为基础，字段只是叶子节点
- `List` 负责 `filters + table + managed dialogs` 这一组复合交互，可以和其他独立组件任意组合
- 筛选协议归 `Table/Column` 定义；`filters()` 用于显式自定义筛选 UI，不写时会尝试按 searchable/searchSchema 自动生成默认筛选表单，写了时也会自动补齐缺失筛选项
- `Dialog` 自己持有保存地址，推荐直接内联到 `Actions::create/edit()`，`dialogs()` 只保留给高级场景
- 常见动作优先走结构化 DSL，`JsExpression` 只保留为兜底 escape hatch
- 字段能力按类型收口，不再所有字段都暴露同一批方法
- 主题层按 `Theme -> Renderer -> RuntimeBuilder -> runtime scripts` 分层
- 不再提供 `V2::xxx()` 门面
- 不回退到旧版 `Html::js()->vue` 这类全局单例模式
- 重组件强调“渲染独立但运行时仍受页面聚合管理”；轻组件强调“渲染独立且运行时自包含”

## 轻组件

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

## 表单结构

V2 表单现在按“节点树”组织，不再只面向 `Field[]`。

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

$form = Forms::make('profile')->addNodes(
    Forms::section(
        '基础信息',
        Forms::object(
            'base',
            Fields::text('name', '名称')->span(12),
            Fields::text('code', '编码')->span(12)
        ),
        Forms::tabs(
            Forms::tab(
                '高级配置',
                Forms::collapse(
                    Forms::collapseItem(
                        '同步设置',
                        Fields::text('sync_key', '同步键')->span(12),
                        Fields::number('sync_sort', '同步排序')->span(12)
                    )
                )->accordion()->span(24)
            )->lazy()
        )->type('border-card')->span(24),
        Forms::inline(
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
        Actions::custom('查看源数据', JsExpression::make("console.log('open source group')"))
    ),
    Forms::grid(
        Fields::text('source_id', '源数据')
            ->suffixContent('需要时可跳转查看原始记录')
            ->suffixActions(
                Actions::custom('打开', JsExpression::make("console.log('open source')"))
            )
            ->span(24),
        Fields::textarea('remark', '备注')->span(24)
    )->gutter(20),
    Forms::table(
        'items',
        Forms::section(
            '商品',
            Fields::text('sku', 'SKU')->required(),
            Fields::number('qty', '数量')->default(1)
        ),
        Forms::object(
            'extra',
            Fields::text('note', '备注')
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
- `Forms::tabs()` / `Forms::collapse()` 属于纯结构节点，主要补“分标签布局 / 折叠分组”这类高级表单布局能力；它们继续复用表单树 walker，不单独引入 managed runtime。
- `Forms::table()` 里的 `section()` / `tab()` / `collapseItem()` 现在会按列数决定表头策略：如果某个结构节点下面最终展开出多列，会生成真正的分组表头；如果只展开出单列，则继续拍平成 `商品 / SKU` 这类可读列名，避免出现空白二级表头。
- `Forms::tab()` / `Forms::collapseItem()` 的标题仍会参与单列路径标签推导，所以它们只包一列时，会自动形成类似 `基础信息 / 扩展信息 / 编码` 的平铺列标题；如果下面有多列，则会优先生成合并后的分组表头。
- `Forms::table()` 现在支持 `custom()` 行内容节点，适合放按钮列、说明列、自定义展示块；可以直接用 `Forms::custom(...)->columnLabel('源数据')` 指定列标题，也可以继续配合 `section('操作', ...)` / 其他结构节点参与分组表头。
- `Forms::custom()` 现在支持三类内容：原始字符串、`AbstractHtmlElement`、轻组件树（`Layouts / Blocks / Displays`）。表单内部推荐优先放轻组件树，像标题、分割线、提示语、说明块、详情展示都走这条路径。
- `Forms::custom()` 不允许混入 `Form / Table / ListWidget / Dialog / Action` 这类重组件；即使外层包了 `Layouts::stack()` 之类轻布局，也会在构建期直接拦截，避免把额外 runtime 和事件目标塞进表单节点树。
- 字段右侧附加内容统一走 `suffixContent()` / `suffixActions()`，不要再把这类辅助按钮硬塞进表单布局容器。
- `suffixActions()` 里的结构化 action 现在和页面其他 action 一样参与 target 校验；如果绑定的是 `Dialog` 对象，也会自动进入页面 dialog 收集。
- `Forms::section()` 现在支持 `->headerActions(...)`，适合在分组头部放“查看源数据”“同步本组配置”这类入口，不用再退回到 `custom()` 拼 header。

## 弹窗能力

V2 的 `Dialog` 不再只是“一个表单弹窗”，现在支持三类弹窗体：

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
- `->titleTemplate('编辑 {name}')` 根据当前行动态标题
- `->beforeOpen()` / `->afterOpen()` / `->beforeClose()` / `->afterClose()` 生命周期钩子
- `->context([...])` 显式注入弹窗上下文，支持 `@row.id` / `@filters.keyword` / `@selection`
- `->iframeHost()` 为 iframe 子页面开启宿主桥接
- `->iframeFullscreenToggle()` 为 iframe 弹窗启用头部全屏切换
- `->componentOpenMethod()` / `->componentCloseMethod()` 控制组件弹窗打开关闭时调用的实例方法

列表页工具栏和请求动作可以通过 `Table::selection()` 开启勾选列，之后请求动作和弹窗上下文里都能拿到 `selection`。如果这个列表页是作为 iframe 选择页被其他表单打开，V2 还会自动把选中结果暴露到子页面全局：

- `window.__scV2Selection`: 当前主表或默认表的选中结果
- `window.__scV2Selections[tableKey]`: 多表页里按表 key 区分的选中结果

因此 `Fields::picker()` 指向 V2 列表页时，通常可以直接省略 `selectionPath()`；只有旧页面或多表页才需要额外指定。

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

`dialogs()` 可以直接挂在 `Pages::make()` 上。头部 `Actions::create/edit($dialog)` 会自动收集对应弹窗；如果弹窗是从自定义按钮、独立表格行按钮或自定义 JS 打开的，推荐显式写 `->dialogs($dialog)`。

如果动作直接绑定的是 `Dialog` 对象，V2 会在页面构建时自动把它收集进当前页面，包括独立 `Table` 区块和 dialog footer 里的二级弹窗；如果只写字符串 key，例如 `Actions::create('打开', 'editor')`、`Actions::submit('保存', 'editor')`、`Actions::request(...)->dialog('editor')`，对应 key 必须能在当前页面解析到，否则会在构建期直接抛错。

## 目录结构

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

## 完整列表页示例

下面这个示例覆盖了：

- 页面头部动作
- 筛选表单
- 表格工具栏动作
- 行级请求动作
- 多弹窗
- `Actions::custom()` 兜底 JS

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
        Actions::create('新建商品', $normalDialog),
        Actions::create($batchDialog),
        Actions::custom(
            '同步说明',
            JsExpression::make("openDialog('sync-tips')")
        ),
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
                    ->toolbar(
                        Actions::refresh(),
                        Actions::request('重建索引')
                            ->post('/admin/product/rebuild-index')
                            ->payload([
                                'status' => '@filters.status',
                            ])
                            ->confirm('确认重建当前筛选结果的索引？')
                            ->successMessage('索引重建任务已提交'),
                        Actions::create('普通新增', $normalDialog),
                        Actions::delete()->confirm('确认删除当前选中商品？')
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
                        Actions::edit('编辑', $normalDialog),
                        Actions::custom(
                            '预览',
                            JsExpression::make("openDialog('preview-product', scope.row)")
                        ),
                        Actions::custom(
                            '复制链接',
                            JsExpression::make(<<<'JS'
(async () => {
  if (!scope.row.share_url) {
    ElementPlus.ElMessage.error('当前记录没有分享链接');
    return;
  }

  await navigator.clipboard.writeText(String(scope.row.share_url));
  ElementPlus.ElMessage.success('链接已复制');
})()
JS)
                        ),
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

## 编辑弹窗示例

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

## 非表单弹窗示例

```php
<?php

use Sc\Util\HtmlStructureV2\Dsl\Actions;
use Sc\Util\HtmlStructureV2\Dsl\Dialogs;
use Sc\Util\HtmlStructureV2\Support\JsExpression;

$contentDialog = Dialogs::make('help', '操作说明')
    ->content('<div style="line-height:1.8">这里放说明内容、富文本或自定义组件。</div>')
    ->footer(
        Actions::close('我知道了', 'help')->type('primary')
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

iframe 弹窗如果要配合 `Actions::submit()` 使用，除了配置 `saveUrl()/createUrl()/updateUrl()` 外，子页面还需要暴露一个提交方法。默认读取 `"VueApp.submit"`，也可以改成别的路径：

```injectablephp
<?php

$fillDialog = Dialogs::make('work-order-fill', '填写')
    ->updateUrl('/admin/work-order/update')
    ->iframe('/admin/work-order/fill', [
        'id' => '@row.id',
    ])
    ->iframeSubmitHandler('VueApp.submit')
    ->footer(
        Actions::close('取消', 'work-order-fill'),
        Actions::submit('提交结果', 'work-order-fill')->confirm('确认提交吗？')
    );
```

子页面里的 `"VueApp.submit"` 应返回最终要提交给接口的数据，也可以返回 Promise。这个能力依赖宿主页直接访问 `iframe.contentWindow`，通常要求子页面与宿主页同源。

如果你更希望配置就近写在按钮上，也可以直接在 action 上覆盖默认地址；优先级高于 dialog / table 默认配置：

```injectablephp
<?php

Actions::submit('保存', 'editor')
    ->updateUrl('/admin/user/update-direct');

Actions::delete()
    ->deleteUrl('/admin/user/delete-direct')
    ->deleteKey('user_id');
```

## iframe 宿主桥

开启 `->iframeHost()` 后，宿主页会监听 iframe 子页面的 `postMessage`。子页面可以发：

```js
window.parent.postMessage({
  __scV2DialogHost: {
    action: 'close'
  }
}, '*');
```

支持的 `action`：

- `close`
- `reloadTable`
- `openDialog`
- `setTitle`
- `setFullscreen`
- `toggleFullscreen`
- `refreshIframe`

如果不显式传 `dialogKey`，宿主会按消息来源窗口自动反查所属 iframe 弹窗。

## 表单页 / 页面弹窗

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
        Actions::create($helpDialog)
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
                Actions::edit('编辑', 'editor')
            )
    )
    ->dialogs($editorDialog);
```

## 常见列表页示例

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
        Actions::create('新建用户', $editorDialog)
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
                        Actions::edit($editorDialog),
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

## 常见表单页示例

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

## 组合页面示例

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

## 动作系统

当前动作建议分三类使用：

- `create/edit/submit/close`：弹窗动作，推荐直接传 `Dialog`
- `request`：结构化异步请求动作，优先使用
- `custom`：原生 JS 兜底，只在确实无法抽象时使用

### 弹窗动作推荐写法

推荐直接把 `Dialog` 交给 `Actions::create()/edit()`，页面会自动注册：

```php
$editorDialog = Dialogs::make('editor', '编辑用户')
    ->saveUrl('/admin/user/save')
    ->form(...);

Actions::create('新建用户', $editorDialog)
Actions::edit($editorDialog)
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
        Actions::create('新建用户', 'editor')
    )
    ->addSection(
        Tables::make('user-table')->rowActions(
            Actions::edit('编辑', 'editor')
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
- `->confirm('确认消息')`
- `->loadingText('处理中...')`
- `->successMessage('操作成功')`
- `->errorMessage('操作失败')`
- `->reloadTable()`
- `->reloadPage()`
- `->dialog('editor')->closeAfterSuccess()`
- `->before(...)`
- `->afterSuccess(...)`
- `->afterFail(...)`
- `->afterFinally(...)`

### 请求动作上下文取值

`payload()` 里可以直接写这些 token：

- `@row.id`
- `@filters.keyword`
- `@filters.status`
- `@dialogs.create-normal.name`
- `@forms.setting-form.site_name`

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

`before/afterSuccess/afterFail/afterFinally` 接收 `JsExpression`，推荐写成函数：

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
- `ctx.payload`
- `ctx.error`
- `ctx.request`
- `ctx.vm`

## 统一事件

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
- `Events::message()`
- `Events::request()`

轻组件上的 DOM 事件也走同一套事件对象协议；如果它们渲染在 `Forms::custom()` 里，还会额外注入 `ctx.model`，便于直接用 `@model.source_id` 这类 token 取当前表单片段数据。

当前已经接入 runtime 的常用事件：

- `Form`: `validateSuccess` / `validateFail` / `optionsLoaded` / `optionsLoadFail` / `uploadSuccess` / `uploadFail` / `arrayRowAdd` / `arrayRowRemove` / `arrayRowMove`
- `Table`: `loadBefore` / `loadSuccess` / `loadFail` / `pageChange` / `pageSizeChange` / `sortChange` / `selectionChange` / `dragSort` / `deleteSuccess` / `deleteFail`
- `List`: `filterSubmit` / `filterReset` / `reload`
- `Dialog`: `beforeOpen` / `afterOpen` / `beforeClose` / `afterClose` / `submitSuccess` / `submitFail`
- `RequestAction`: `click` / `before` / `success` / `fail` / `finally`

### 原生 JS 兜底

确实没法抽象时，再用 `Actions::custom()`：

```php
Actions::custom(
    '复制链接',
    JsExpression::make(<<<'JS'
(async () => {
  await navigator.clipboard.writeText(String(scope.row.share_url || ''));
  ElementPlus.ElMessage.success('链接已复制');
})()
JS)
)->confirm('确认复制当前链接？');
```

这条通道是 escape hatch，不建议作为主路径。

## 筛选和排序

- `filters(Form)` 只描述筛选输入 UI；也支持直接写成 `filters(Fields::text(...), Fields::select(...))`
- 省略 `filters()` 时，`List` 会尝试按 `searchable()/search()/searchSchema()` 自动生成默认筛选项；这些自动项默认隐藏 label，只保留 placeholder；已显式声明的字段会保留，其余缺失项继续自动补齐
- `Fields::*()` 的 label 参数现在可省略；省略时默认隐藏该字段标签，想保留说明可显式传第二参，或单独调用 `placeholder()`
- `Fields::icon()` 用于选择 Element Plus 图标，默认提供“搜索面板 + 手输图标名”组合输入
- 想在筛选条里放更多条件时，可对显式表单使用 `->hideLabels()` 隐藏字段标签，只保留 placeholder
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
    isUp: isMoveDown ? 1 : 0,
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
- `isUp` 仍然保留，但它是为了兼容原版 `setDraw()` 的旧语义，等价于 `isMoveDown`
- 树表场景还会补充 `oldParentRow` / `newParentRow` / `sameParent`

## 字段能力分层

- `Fields::toggle()`、`Fields::hidden()` 只保留最小通用能力
- `Fields::text()`、`Fields::textarea()`、`Fields::password()`、`Fields::icon()` 才暴露文本校验快捷方法
- `Fields::select()`、`Fields::radio()`、`Fields::checkbox()` 才暴露 `options()`、`remoteOptions()`、`linkageUpdate()`
- `Fields::cascader()` 才暴露 `cascaderProps()`、`emitPath()`、`checkStrictly()`
- `Fields::upload()`、`Fields::image()` 才暴露上传相关方法
- `Fields::number()` 才暴露 `minValue()`、`maxValue()`、`step()`、`precision()`
- `Fields::date()`、`Fields::datetime()`、`Fields::daterange()` 才暴露 `format()`、`valueFormat()`

## 表格列展示能力

- `Column::displayMapping()` 枚举值转文字
- `Column::displayTag()` 状态标签
- `Column::displayImage()` 单图预览
- `Column::displayImages()` 多图预览
- `Column::displayBoolean()` 布尔文案
- `Column::displayBooleanTag()` 布尔标签
- `Column::displayDate()`、`Column::displayDatetime()` 日期格式化
- `Column::displayPlaceholder()` 空值占位

## 远程筛选协议

默认输出以下查询结构：

- `search[search]`
- `search[searchType]`
- `search[searchField]`
- `page`
- `pageSize`
- `order[field]`
- `order[order]`

## 远程列表返回结构

支持以下常见结构：

- `{ code, data: { data: [], total } }`
- `{ code, data: { list: [], total } }`
- `{ code, data: { rows: [], total } }`
- `{ data: [] }`
- `{ rows: [] }`

## 运行时脚本

- 列表页运行时拆在 `Theme/ElementPlusAdmin/Runtime/scripts/list/`
- 普通表单运行时拆在 `Theme/ElementPlusAdmin/Runtime/scripts/simple/`
- 结构化请求动作 runtime 在 `request-action-factory.js`
- 公共 helper 在 `runtime-helpers.js`
- 公共表单 runtime 工厂在 `form-runtime-factory.js`
- 渲染时会优先把 runtime 按原始模块文件名发布到 `public/js/sc-v2/`，页面里直接加载 `runtime-helpers.js`、`managed-dialog-factory.js` 这类具体脚本，便于定位问题
- URL 通过查询参数 hash 做缓存失效，不靠 bundle 文件名隐藏真实模块名
- 如果静态文件发布失败，会自动回退到“按模块分段内联”，不会再退回成单个超大 inline script
