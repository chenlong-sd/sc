# HtmlStructureV2

`HtmlStructureV2` 是一套独立于旧 `HtmlStructure` 的后台页面 DSL。它把后台开发拆成三层：

- 页面场景：`custom / form / list / crud`
- 重交互组件：`Forms / Fields / Tables / Dialogs / Actions / Lists`
- 轻布局展示组件：`Layouts / Blocks / Displays`

目标不是继续堆一个大门面，而是让“页面是什么场景”和“页面里有哪些组件”都清楚可读。

## 页面入口

- `Pages::custom()` 自由拼装页
- `Pages::form()` 纯表单页
- `Pages::list()` 列表主导页，支持 `filters + table + inline dialogs`
- `Pages::crud()` 标准 CRUD 页，是 `list` 场景的快捷入口

## 页面选型建议

- “列表 + 搜索 + 工具栏 + 多个新增/编辑弹窗” 用 `Pages::list()`
- “标准列表 + 一个 editor 弹窗” 用 `Pages::crud()`
- “纯配置表单 / 设置页 / 详情编辑页” 用 `Pages::form()`
- “看板、混合布局、非标准后台页” 用 `Pages::custom()`

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

`List` 现在是复合组件，不再只属于 `Pages::list()`。`Pages::list()` / `Pages::crud()` 更接近“页面壳 + 一个主 list”的快捷入口；需要混合布局时，直接在 `Pages::custom()` 里组合 `Lists::make()`、`Tables::make()`、`Forms::make()` 即可。

混合布局里如果页面头部动作需要显式指向某个 list / table，统一使用 `->forList('orders')` / `->forTable('audit-table')`，不要再依赖“当前主表”这类隐式约定。显式 key 写错时不会再回退到主 list，而是会在构建期直接抛错，避免误绑到别的组件。

像 `Actions::refresh()`、`RequestAction::reloadTable()` 这类“数据目标型动作”，如果当前位置没有局部 table/list 上下文，也没有显式 `forTable()/forList()`，现在会在构建期直接报错，不再生成隐式 `loadTableData()` 之类的回退调用。

## 设计约束

- 不依赖旧版 `Html::create()` 全局状态
- V2 当前仍在建设期，默认以最新 DSL/运行时设计为准；调整时不要求兼容旧写法，也不保留历史 API 壳层
- `Form / Table / Dialog / List / Action` 属于重交互组件，继续走显式 key、构建期校验、运行时隔离
- `Layouts / Blocks / Displays` 属于轻组件，优先做到零注册、零运行时、自由组合
- `Form` 不再长期限定为“字段数组”；后续以表单节点树为基础，字段只是叶子节点
- `List` 负责 `filters + table + managed dialogs` 这一组复合交互，可以和其他独立组件任意组合
- 筛选协议归 `Table/Column` 定义，`filters()` 只负责筛选 UI
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
- `Displays::descriptions(...)` 轻量详情展示

轻组件可以和 `ListWidget / Table / Form / Dialog` 混排；它们不参与 table/list/dialog key 注册，也不扩展当前重 runtime。

但如果 `Layouts::stack()` / `Layouts::grid()` / `Layouts::card()` 里包的是重组件，那么这些重组件仍会正常参与页面级的 key 注册、dialog 收集和 action target 校验；layout 本身只是组合容器，不会把内部重组件“藏起来”。

```injectablephp
<?php

use Sc\Util\HtmlStructureV2\Dsl\Blocks;
use Sc\Util\HtmlStructureV2\Dsl\Displays;
use Sc\Util\HtmlStructureV2\Dsl\Layouts;
use Sc\Util\HtmlStructureV2\Dsl\Pages;

$page = Pages::custom('概览页')->addSection(
    Layouts::stack(
        Blocks::title('运营概览')->description('轻组件之间可以自由组合'),
        Layouts::grid(
            Blocks::alert('提示', '这是一个提示块'),
            Displays::descriptions()
                ->title('基础信息')
                ->items([
                    '状态' => '草稿',
                    '作者' => 'system',
                ])
        )->columns(2)
    )
);
```

## 表单结构

V2 表单现在按“节点树”组织，不再只面向 `Field[]`。

- 叶子节点：`Field`
- 结构节点：`Forms::section()` / `Forms::inline()` / `Forms::grid()` / `Forms::custom()`
- 数据作用域节点：`Forms::object()`
- 数组节点：`Forms::arrayGroup()` / `Forms::table()`

这套方向的目标是补齐原版表单在布局上的灵活性，但不照搬原版的全局 JS 注入和展示表格承载编辑表格的实现方式。

```injectablephp
<?php

use Sc\Util\HtmlStructureV2\Dsl\Actions;
use Sc\Util\HtmlStructureV2\Dsl\Fields;
use Sc\Util\HtmlStructureV2\Dsl\Forms;
use Sc\Util\HtmlStructureV2\Support\JsExpression;

$form = Forms::make('profile')->addNodes(
    Forms::section(
        '基础信息',
        Forms::object(
            'base',
            Fields::text('name', '名称')->span(12),
            Fields::text('code', '编码')->span(12)
        ),
        Forms::inline(
            Fields::text('city', '城市'),
            Fields::number('sort', '排序')
        ),
        Forms::custom('<div style="color:#909399">这里可以插入自定义说明内容</div>')
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
- `Forms::table()` 继续复用数组节点协议，但“表格行内再套数组节点”仍暂不支持；命中时会在构建期直接抛错。
- `Forms::table()` 现在支持在行节点里继续使用 `object()`、`section()`、`grid()`、`inline()` 做结构组织；最终会在构建期展开成列。
- `Forms::table()` 里的 `section()` 当前只参与列标题前缀组织，不生成真正的分组表头；例如 `商品 / SKU`。
- `Forms::table()` 现在支持 `custom()` 行内容节点，适合放按钮列、说明列、自定义展示块；如果需要列标题，推荐配合 `section('操作', Forms::custom(...))` 使用。
- 字段右侧附加内容统一走 `suffixContent()` / `suffixActions()`，不要再把这类辅助按钮硬塞进表单布局容器。
- `suffixActions()` 里的结构化 action 现在和页面其他 action 一样参与 target 校验；如果绑定的是 `Dialog` 对象，也会自动进入页面 dialog 收集。

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

列表页工具栏和请求动作可以通过 `Table::selection()` 开启勾选列，之后请求动作和弹窗上下文里都能拿到 `selection`。

`dialogs()` 现在不只属于 `ListPage`，`Pages::form()` 和 `Pages::custom()` 也可以直接挂 managed dialogs。头部 `Actions::create/edit($dialog)` 会自动收集对应弹窗；如果弹窗是从自定义按钮、独立表格行按钮或自定义 JS 打开的，推荐显式写 `->dialogs($dialog)`。

如果动作直接绑定的是 `Dialog` 对象，V2 会在页面构建时自动把它收集进当前页面，包括独立 `Table` 区块和 dialog footer 里的二级弹窗；如果只写字符串 key，例如 `Actions::create('打开', 'editor')`、`Actions::submit('保存', 'editor')`、`Actions::request(...)->dialog('editor')`，对应 key 必须能在当前页面解析到，否则会在构建期直接抛错。

## 目录结构

- `Components/`: 表单、字段、表格、动作、弹窗
- `Components/Block/`: 轻量内容块
- `Components/Layout/`: 轻量布局容器
- `Components/Display/`: 轻量展示组件
- `Components/Fields/`: 各字段类型实现
- `Dsl/`: 推荐使用的 DSL 入口
- `Page/`: 页面模型，当前提供 `CustomPage`、`FormPage`、`ListPage`、`CrudPage`
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

$page = Pages::list('商品列表')
    ->description('完整示例：筛选、表格、多弹窗、结构化请求动作、自定义 JS 兜底')
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
                Actions::create('普通新增', $normalDialog)
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
                Actions::delete()->confirm('确认删除当前商品？')
            )
            ->deleteUrl('/admin/product/delete')
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

## 表单页 / 自定义页弹窗

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

$formPage = Pages::form('系统设置')
    ->actions(
        Actions::create($helpDialog)
    )
    ->form(
        Forms::make('settings')->addFields(
            Fields::text('site_name', '站点名称')
        )
    );

$customPage = Pages::custom('自定义页')
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

## 标准 CRUD 页示例

适合“一个列表 + 一个编辑弹窗”的标准后台页。

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

$page = Pages::crud('用户管理')
    ->actions(
        Actions::create('新建用户', $editorDialog)
    )
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
                Actions::delete()
            )
            ->deleteUrl('/admin/user/delete')
    );

echo $page->toHtml();
```

## 纯表单页示例

适合系统设置、配置页、详情编辑页。页面头部请求动作同样可用。

```php
<?php

use Sc\Util\HtmlStructureV2\Dsl\Actions;
use Sc\Util\HtmlStructureV2\Dsl\Fields;
use Sc\Util\HtmlStructureV2\Dsl\Forms;
use Sc\Util\HtmlStructureV2\Dsl\Pages;

$page = Pages::form('系统设置')
    ->actions(
        Actions::request('清理缓存')
            ->post('/admin/system/clear-cache')
            ->confirm('确认清理系统缓存？')
            ->successMessage('缓存已清理')
    )
    ->form(
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

## 自定义页面示例

适合需要手工拼多个区块，但又不属于标准 `form/list/crud` 的页面。

```php
<?php

use Sc\Util\HtmlStructureV2\Dsl\Actions;
use Sc\Util\HtmlStructureV2\Dsl\Fields;
use Sc\Util\HtmlStructureV2\Dsl\Forms;
use Sc\Util\HtmlStructureV2\Dsl\Lists;
use Sc\Util\HtmlStructureV2\Dsl\Pages;
use Sc\Util\HtmlStructureV2\Dsl\Tables;

$page = Pages::custom('运营看板')
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

$page = Pages::list('用户列表')
    ->dialogs($editorDialog)
    ->actions(
        Actions::create('新建用户', 'editor')
    )
    ->table(
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

`Form / Table / List / Dialog / Action` 统一走 `->on($event, $handler)` / `->events([...])`，推荐都传 `JsExpression::make('(ctx) => { ... }')` 这种函数式写法，不要再为单个组件单独扩一批私有 hook API。

```php
use Sc\Util\HtmlStructureV2\Support\JsExpression;

Forms::make('profile')
    ->on('validateFail', JsExpression::make('(ctx) => console.log("form invalid", ctx.error)'));

Tables::make('orders')
    ->on('loadSuccess', JsExpression::make('(ctx) => console.log("rows", ctx.rows)'));

Dialogs::make('editor', '编辑')
    ->on('afterOpen', JsExpression::make('(ctx) => console.log("dialog opened", ctx.dialogKey)'));

Actions::request('同步')
    ->on('success', JsExpression::make('(ctx) => console.log("done", ctx.payload)'));
```

当前已经接入 runtime 的常用事件：

- `Form`: `validateSuccess` / `validateFail` / `optionsLoaded` / `optionsLoadFail` / `uploadSuccess` / `uploadFail` / `arrayRowAdd` / `arrayRowRemove` / `arrayRowMove`
- `Table`: `loadBefore` / `loadSuccess` / `loadFail`
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

- `filters(Form)` 只描述筛选输入 UI
- `Table::searchSchema()` 适合显式定义完整筛选协议
- `Table::search($name, $type, $field)` 适合逐项追加筛选定义
- `Column::searchable()` 适合同名字段的快捷声明
- `Column::searchable('LIKE', 'table.field')` 可以映射后端真实字段
- `Column::sortable()` 开启远程排序
- `Column::sortable('table.field')` 可以映射后端真实排序字段

## 字段能力分层

- `Fields::toggle()`、`Fields::hidden()` 只保留最小通用能力
- `Fields::text()`、`Fields::textarea()`、`Fields::password()` 才暴露文本校验快捷方法
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
