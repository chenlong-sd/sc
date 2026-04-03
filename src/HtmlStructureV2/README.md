# HtmlStructureV2

`HtmlStructureV2` 是一套不改动原 `HtmlStructure` 的新骨架，目标是把后台页面开发抽到“页面 DSL + 显式渲染上下文 + 主题适配层”。

## 设计目标

- 不依赖原有全局 `Html::create()` 状态。
- 页面级 DSL 优先，先描述后台页面，再渲染底层组件。
- 去掉大量 `@xxx` 魔法字符串，改成显式对象和枚举。
- 允许旧版与 V2 并存，逐步迁移。
- 字段能力按类型收口，避免所有字段都暴露同一批不适用的方法。
- `Field` 作为抽象基类，只承接公共元数据和通用 UI 状态。
- 校验、搜索、占位提示拆成独立能力，只让需要的字段接入。
- DSL 入口按职责分组，避免所有创建方法都堆在一个 `V2` 门面里。

## 字段能力收口

- `V2::toggle()/hidden()` 返回基础字段，只保留最小通用能力。
- `V2::text()/textarea()/password()` 才暴露 `email()/phone()/pattern()/minLength()/maxLength()/lengthBetween()` 这类文本校验快捷方法。
- `V2::select()/radio()/checkbox()` 返回选项字段，才会暴露 `options()/remoteOptions()/linkageUpdate()`。
- `V2::cascader()` 返回级联字段，额外暴露 `cascaderProps()/emitPath()/checkStrictly()`。
- `V2::upload()/image()` 返回上传字段，才会暴露 `uploadUrl()/uploadLimit()/uploadResponsePath()`。
- `V2::number()` 返回数字字段，才会暴露 `minValue()/maxValue()/step()/precision()`。
- `V2::date()/datetime()/daterange()` 返回日期字段，才会暴露 `format()/valueFormat()`。
- `V2::password()` 返回密码字段，才会暴露 `showPassword()`。
- 只有具备搜索能力的字段才暴露 `searchType()/searchField()`。
- 只有具备校验能力的字段才暴露 `required()/rule()/rules()`。
- 只有具备占位提示的字段才暴露 `placeholder()`。
- `V2` 负责创建字段实例，`Field` 不再承担工厂职责。

## DSL 入口分组

- `Dsl\\Pages`: 页面入口，如 `page()/crud()`
- `Dsl\\Forms`: 表单容器入口，如 `make()`
- `Dsl\\Fields`: 表单字段入口，如 `text()/select()/upload()`
- `Dsl\\Tables`: 表格与列入口，如 `make()/column()`
- `Dsl\\Dialogs`: 弹窗入口，如 `make()`
- `Dsl\\Actions`: 动作按钮入口，如 `create()/edit()/delete()`
- `V2` 现在作为兼容层保留，内部转发到这些分组 DSL。

## 目录结构

- `Contracts/`: 渲染契约与主题契约
- `Support/`: `Document`、资源、表达式编码等基础设施
- `Components/`: 表单、字段、表格、动作、弹窗
- `Components/Fields/`: 具体字段类型实现，如 `BasicField`、`OptionField`、`UploadField`
- `Dsl/`: 推荐使用的分组 DSL 入口
- `Page/`: 页面级 DSL，目前提供 `AdminPage`、`CrudPage`
- `Theme/`: 主题适配层，目前提供 `ElementPlusAdminTheme`

## 快速示例

```php
<?php

use Sc\Util\HtmlStructureV2\Dsl\Actions;
use Sc\Util\HtmlStructureV2\Dsl\Dialogs;
use Sc\Util\HtmlStructureV2\Dsl\Fields;
use Sc\Util\HtmlStructureV2\Dsl\Forms;
use Sc\Util\HtmlStructureV2\Dsl\Pages;
use Sc\Util\HtmlStructureV2\Dsl\Tables;

$page = Pages::crud('用户管理')
    ->description('V2 示例：搜索、表格、编辑弹窗都由页面 DSL 统一组织')
    ->actions(
        Actions::create('新建用户')
    )
    ->search(
        Forms::make('user-search')->inline()->addFields(
            Fields::text('keyword', '关键词')
                ->placeholder('用户名 / 手机号')
                ->searchField('username&mobile')
                ->searchType('LIKE'),
            Fields::daterange('created_at', '创建时间'),
            Fields::select('status', '状态')->options([
                '' => '全部',
                1 => '启用',
                0 => '禁用',
            ])
        )
    )
    ->table(
        Tables::make('user-table')
            ->dataUrl('/admin/user/list')
            ->toolbar(
                Actions::refresh(),
                Actions::create('新建用户')
            )
            ->addColumns(
                Tables::column('ID', 'id')->width(80),
                Tables::column('用户名', 'username')->minWidth(160)->sortable(),
                Tables::column('手机号', 'mobile')->minWidth(160),
                Tables::column('状态', 'status')->width(100)->sortable('status')
            )
            ->rowActions(
                Actions::edit(),
                Actions::delete()
            )
    )
    ->editor(
        Dialogs::make('editor', '编辑用户')->form(
            Forms::make('user-editor')->addFields(
                Fields::hidden('id'),
                Fields::text('username', '用户名')
                    ->required()
                    ->lengthBetween(2, 20),
                Fields::password('password', '登录密码')
                    ->minLength(6),
                Fields::text('mobile', '手机号')
                    ->phone(),
                Fields::radio('gender', '性别')->options([
                    1 => '男',
                    2 => '女',
                ]),
                Fields::checkbox('tags', '用户标签')->options([
                    'vip' => 'VIP',
                    'new' => '新客',
                    'risk' => '风险关注',
                ]),
                Fields::number('sort', '排序')
                    ->minValue(0)
                    ->default(0),
                Fields::cascader('region', '地区')->options([
                    [
                        'value' => 'zhejiang',
                        'label' => '浙江省',
                        'children' => [
                            [
                                'value' => 'hangzhou',
                                'label' => '杭州市',
                            ],
                        ],
                    ],
                ]),
                Fields::date('expire_date', '到期日期'),
                Fields::image('cover', '封面')
                    ->uploadUrl('/admin/upload/image')
                    ->uploadResponsePath('data.url'),
                Fields::select('department_id', '所属部门')
                    ->remoteOptions('/admin/department/options', 'id', 'name'),
                Fields::toggle('status', '状态')->default(true)
            )
        )
    )
    ->saveUrl('/admin/user/save')
    ->deleteUrl('/admin/user/delete');

echo $page->toHtml();
```

## 兼容性

- 现有 `V2::page()/V2::text()/V2::table()` 仍然可用。
- 新代码建议优先使用 `Dsl\\Pages/Forms/Fields/Tables/Dialogs/Actions`，入口语义更清楚。

## 已对齐的老接口约定

- 远程列表查询兼容 `EasySearch`：
  - `search[search]`
  - `search[searchType]`
  - `search[searchField]`
  - `page`
  - `pageSize`
  - `order[field]`
  - `order[order]`
- 远程列表返回兼容常见结构：
  - `{ code, data: { data: [], total } }`
  - `{ code, data: { list: [], total } }`
  - `{ code, data: { rows: [], total } }`
  - `{ data: [] }`
  - `{ rows: [] }`

## 搜索和排序

- 具备搜索能力的字段可以通过 `searchType()` 覆盖默认搜索类型。
  - `text/textarea` 默认是 `LIKE`
  - `daterange` 默认是 `BETWEEN`
  - `select/radio` 默认是 `=`
  - `checkbox` 默认是 `IN`
- 具备搜索能力的字段可以通过 `searchField()` 把表单字段映射到真实查询字段。
  - 例：`->searchField('username&mobile')`
- `Column::sortable()` 会开启远程排序。
- `Column::sortable('table.field')` 或 `->sortField('table.field')` 可以指定后端真实排序字段。

## 表格列快捷展示

- `Column::mapping()` 用于枚举值转文字。
- `Column::tag()` 用于状态标签展示。
- `Column::image()` 用于单图预览。
- `Column::images()` 用于多图预览。
- `Column::boolean()` 用于布尔值文字展示。
- `Column::booleanTag()` 用于布尔值标签展示。
- `Column::date()` / `Column::datetime()` 用于日期时间格式化展示。
- `Column::placeholder()` 现在会对普通列和快捷展示列生效。

```php
V2::table('user-table')->addColumns(
    V2::column('状态', 'status')->tag([
        1 => ['label' => '启用', 'type' => 'success'],
        0 => ['label' => '禁用', 'type' => 'danger'],
    ]),
    V2::column('来源', 'source')->mapping([
        'manual' => '人工创建',
        'api' => '接口同步',
    ]),
    V2::column('已实名', 'verified')->boolean('已实名', '未实名'),
    V2::column('启用状态', 'enabled')->booleanTag('启用', '停用'),
    V2::column('创建时间', 'created_at')->datetime(),
    V2::column('到期日期', 'expire_date')->date(),
    V2::column('头像', 'avatar')->image(56, 56),
    V2::column('相册', 'albums')->images(3, 'url', 56, 56)
);
```

## 新增字段类型

- `V2::password()` 会渲染为 `el-input type=password`
- `V2::number()` 会渲染为 `el-input-number`
- `V2::radio()` 会渲染为 `el-radio-group`
- `V2::checkbox()` 会渲染为 `el-checkbox-group`
- `V2::cascader()` 会渲染为 `el-cascader`
- `V2::date()` 会渲染为 `el-date-picker type=date`
- `V2::datetime()` 会渲染为 `el-date-picker type=datetime`
- `V2::daterange()` 会渲染为 `el-date-picker type=daterange`
- `V2::upload()` 会渲染为 `el-upload`
- `V2::image()` 是图片上传快捷写法，默认 `picture-card`

```php
V2::password('password', '密码')
    ->required()
    ->minLength(6);

V2::radio('status', '状态')->options([
    1 => '启用',
    0 => '禁用',
]);

V2::checkbox('permissions', '权限')->options([
    'article.create' => '文章创建',
    'article.publish' => '文章发布',
]);

V2::cascader('region', '地区')->options([
    [
        'value' => 'zhejiang',
        'label' => '浙江省',
        'children' => [
            ['value' => 'hangzhou', 'label' => '杭州市'],
        ],
    ],
]);

V2::number('sort', '排序')->minValue(0)->maxValue(999)->step(1);

V2::date('publish_date', '发布日期');

V2::datetime('published_at', '发布时间')
    ->valueFormat('YYYY-MM-DD HH:mm:ss');

V2::daterange('created_at', '创建时间');
```

## 上传字段

- `V2::upload()` 用于附件上传。
- `V2::image()` 用于图片上传，默认会切到 `picture-card`。
- 常用链式配置：
  - `->uploadUrl('/admin/upload/file')`
  - `->uploadMultiple()`
  - `->uploadLimit(3)`
  - `->uploadAccept('image/*')`
  - `->uploadResponsePath('data.url')`
  - `->uploadButtonText('上传附件')`
  - `->uploadTip('建议上传 jpg/png')`
- 当前默认把上传成功后的值回填成字符串 URL，或字符串 URL 数组。
- 如果后端返回不是常见的 `data.url / data.data.url / url / path` 结构，需要显式指定 `uploadResponsePath()`。

```php
V2::image('cover', '封面')
    ->uploadUrl('/admin/upload/image')
    ->uploadResponsePath('data.url')
    ->uploadTip('建议上传 750x420');

V2::upload('attachments', '附件')
    ->uploadUrl('/admin/upload/file')
    ->uploadMultiple()
    ->uploadLimit(3)
    ->uploadButtonText('上传附件');
```

## 普通页面表单

- 现在 `AdminPage + Form` 场景也支持：
  - 远程下拉
  - 表单校验
  - 上传字段
- 不再局限于 `CrudPage`。

```php
$page = V2::page('系统设置')
    ->addSection(
        V2::form('setting-form')->addFields(
            V2::text('site_name', '站点名称')->required(),
            V2::select('role_id', '默认角色')
                ->remoteOptions('/admin/role/options', 'id', 'title'),
            V2::image('logo', '站点 Logo')
                ->uploadUrl('/admin/upload/image')
                ->uploadResponsePath('data.url')
        )
    );
```

## 表单校验和远程选项

- 具备校验能力的字段可以通过 `required()` 自动补充 Element Plus 校验规则。
- 还可以继续叠加：
  - `->rule([...])`
- 文本字段额外支持：
  - `->email()`
  - `->phone()`
  - `->pattern('/^SC\\d+$/')`
  - `->minLength(2)`
  - `->maxLength(30)`
  - `->lengthBetween(2, 20)`
- `OptionField::remoteOptions($url, $valueField, $labelField, $params)` 用来声明远程下拉。
  - 页面加载时会拉取搜索表单的远程选项。
  - 打开编辑弹窗时会拉取弹窗表单的远程选项。
  - 已内置选项 loading 状态。

```php
V2::select('role_id', '角色')
    ->required()
    ->remoteOptions('/admin/role/options', 'id', 'title', [
        'status' => 1,
    ]);

V2::radio('status', '状态')
    ->remoteOptions('/admin/status/options', 'value', 'label');

V2::checkbox('role_ids', '角色')
    ->remoteOptions('/admin/role/options', 'id', 'title');

V2::cascader('region', '地区')
    ->remoteOptions('/admin/region/tree', 'value', 'label');
```

## Cascader 常用配置

- `CascaderField::cascaderProps([...])` 用来透传 `el-cascader` 的 `props`。
- `CascaderField::emitPath(false)` 可以只返回最后一级值。
- `CascaderField::checkStrictly()` 可以开启父子节点不关联选择。
- 如果开启 `multiple`，V2 会自动把默认值处理成空数组。

```php
V2::cascader('region_codes', '地区')
    ->cascaderProps([
        'multiple' => true,
        'value' => 'id',
        'label' => 'name',
        'children' => 'children',
    ])
    ->emitPath(false)
    ->checkStrictly();
```

## 条件显示和字段联动

- `Field::visibleWhen()` 用来控制字段显示。
- `Field::disabledWhen()` 用来控制字段禁用。
- 表达式里的 `model` 会自动映射到当前表单模型，所以 CRUD 搜索、弹窗、普通页面表单都能复用同一套写法。
- 远程下拉参数里可以直接用 `@字段名` 读取当前表单值。
- `OptionField::linkageUpdate()` 可以把当前选中项的 `label` 或其他字段自动回填到表单里的其他字段。
- 如果只是想声明“上游字段变化后重新拉取”，也可以显式加 `->remoteOptionsDependsOn(...)`。
- 下游字段默认会在依赖变化时清空当前值；如果不想清空，可以 `->remoteOptionsClearOnChange(false)`。
- 联动回填默认在源字段清空时一并清空目标字段；如果不想清空，可以 `->linkageClearOnEmpty(false)`。

```php
V2::select('auth_type', '认证类型')->options([
    'person' => '个人',
    'company' => '企业',
]);

V2::text('company_name', '企业名称')
    ->visibleWhen("model.auth_type === 'company'")
    ->required();

V2::text('credit_code', '统一信用代码')
    ->visibleWhen("model.auth_type === 'company'")
    ->disabledWhen("model.status === 1");

V2::select('province_id', '省份')
    ->remoteOptions('/admin/region/provinces', 'id', 'name');

V2::select('city_id', '城市')
    ->remoteOptions('/admin/region/cities', 'id', 'name', [
        'province_id' => '@province_id',
    ])
    ->linkageUpdate('city_name', '@label');

V2::select('district_id', '区县')
    ->remoteOptions('/admin/region/districts', 'id', 'name', [
        'city_id' => '@city_id',
    ])
    ->remoteOptionsDependsOn('city_id');

V2::select('manager_id', '负责人')
    ->remoteOptions('/admin/user/options', 'id', 'name')
    ->linkageUpdates([
        'manager_name' => '@label',
        'manager_mobile' => '@mobile',
        'manager_desc' => '@label（@mobile）',
    ]);
```

## 当前范围

- 已完成：页面级 CRUD 骨架、显式 `RenderContext`、自维护 `Document`、Element Plus 主题、基础资源注入、搜索/排序/分页、表单校验、远程下拉、上传字段、密码/单选/多选/级联字段、保存/删除反馈、基础条件显示、下拉联动刷新、选中项自动回填、表格列快捷展示、日期/布尔列格式化。
- 暂未覆盖：旧版 `HtmlStructure` 的全部表单项、更复杂联动规则、导入导出、窗口系统、多主题完整对齐。

## 迁移建议

1. 新页面直接尝试使用 `HtmlStructureV2`。
2. 先迁移最重复的“列表页 + 搜索 + 编辑弹窗”。
3. 复杂组件保留旧版，等 V2 主题能力稳定后再逐步补齐。
