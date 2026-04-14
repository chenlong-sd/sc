<?php

namespace Sc\Util\HtmlStructureV2\Components;

use JetBrains\PhpStorm\ExpectedValues;
use Sc\Util\HtmlStructureV2\Contracts\StructuredEventInterface;
use Sc\Util\HtmlStructureV2\Dsl\Events;
use Sc\Util\HtmlStructureV2\Enums\ActionIntent;
use Sc\Util\HtmlStructureV2\Page\AbstractPage;
use Sc\Util\HtmlStructureV2\Support\ImportColumnResolver;
use Sc\Util\HtmlStructureV2\Support\JsExpression;

final class RequestAction extends Action
{
    private const SUPPORTED_ON_EVENTS = ['click', 'before', 'success', 'fail', 'finally'];
    private const PAYLOAD_SOURCE_CUSTOM = 'custom';
    private const PAYLOAD_SOURCE_FORM = 'form';
    private const DEFAULT_MODE_QUERY_KEY = 'id';

    private ?string $requestUrl = null;
    private string $requestMethod = 'post';
    private array|JsExpression $payload = [];
    private string $payloadSource = self::PAYLOAD_SOURCE_CUSTOM;
    private ?string $payloadFormScope = null;
    private bool $validateForm = false;
    private ?string $validateFormScope = null;
    private bool $reloadTable = false;
    private bool $reloadPage = false;
    private bool $closeAfterSuccess = false;
    private JsExpression|StructuredEventInterface|null $beforeHook = null;
    private JsExpression|StructuredEventInterface|null $afterSuccessHook = null;
    private JsExpression|StructuredEventInterface|null $afterFailHook = null;
    private JsExpression|StructuredEventInterface|null $afterFinallyHook = null;
    private ?string $saveCreateUrl = null;
    private ?string $saveUpdateUrl = null;
    private ?string $saveModeQueryKey = null;
    private bool $importEnabled = false;
    private array $importColumns = [];
    private string $importRowsKey = 'rows';
    private ?string $importColumnInfoKey = 'import_column_info';
    private string $importAccept = '.xlsx,.xls,.csv';
    private int $importHeaderRow = 1;
    private ?string $importDialogTitle = null;
    private ?string $importTemplateFileName = null;
    private bool $importJsonEnabled = true;
    private bool $importAiPromptEnabled = true;
    private ?string $importAiPromptText = null;

    public function __construct(string $label)
    {
        parent::__construct($label, ActionIntent::REQUEST);
    }

    /**
     * 直接创建一个请求动作实例。
     *
     * @param string $label 按钮显示文案。
     * @return static 请求动作实例。
     *
     * 示例：
     * `RequestAction::make('同步')->post('/admin/qa-info/sync')`
     */
    public static function make(string $label): static
    {
        return new static($label);
    }

    /**
     * 配置请求地址和请求方法。
     * `url` 支持在前端运行时解析上下文 token。
     * 常用可用字段：
     * - action / row / tableKey / listKey
     * - filters / forms / dialogs / selection / query / page / mode
     * - dialog / dialogKey: 当前动作运行在目标弹窗上下文时可用
     * - vm
     * - getPageQuery() / resolvePageMode() / resolveFormMode() / loadFormData() / setFormModel() / initializeFormModel() / resetForm()
     * 其中 `page` 里除了 `query` 外，还可直接读取 `url` / `path` / `search`。
     * 例如可写 "@row.id"、"@dialogKey"、"@filters.keyword"、"@page.query.id"、"@page.url"。
     *
     * @param string $url 请求地址，支持上下文 token。
     * @param string $method 请求方法，默认值为 post。
     * @return static 当前请求动作实例。
     *
     * 示例：
     * `RequestAction::make('同步')->request('/admin/qa-info/sync', 'post')`
     */
    public function request(string $url, string $method = 'post'): static
    {
        $this->requestUrl = $url;
        $this->requestMethod = strtolower($method) ?: 'post';
        $this->saveCreateUrl = null;
        $this->saveUpdateUrl = null;
        $this->saveModeQueryKey = null;

        return $this;
    }

    /**
     * 以 GET 方式发起请求。
     *
     * @param string $url GET 请求地址。
     * @return static 当前请求动作实例。
     *
     * 示例：
     * `RequestAction::make('刷新统计')->get('/admin/qa-info/stats')`
     */
    public function get(string $url): static
    {
        return $this->request($url, 'get');
    }

    /**
     * 以 POST 方式发起请求。
     *
     * @param string $url POST 请求地址。
     * @return static 当前请求动作实例。
     *
     * 示例：
     * `RequestAction::make('同步')->post('/admin/qa-info/sync')`
     */
    public function post(string $url): static
    {
        return $this->request($url, 'post');
    }

    /**
     * 以 PUT 方式发起请求。
     *
     * @param string $url PUT 请求地址。
     * @return static 当前请求动作实例。
     *
     * 示例：
     * `RequestAction::make('更新排序')->put('/admin/qa-info/sort')`
     */
    public function put(string $url): static
    {
        return $this->request($url, 'put');
    }

    /**
     * 以 PATCH 方式发起请求。
     *
     * @param string $url PATCH 请求地址。
     * @return static 当前请求动作实例。
     *
     * 示例：
     * `RequestAction::make('局部更新')->patch('/admin/qa-info/status')`
     */
    public function patch(string $url): static
    {
        return $this->request($url, 'patch');
    }

    /**
     * 以 DELETE 方式发起请求。
     *
     * @param string $url DELETE 请求地址。
     * @return static 当前请求动作实例。
     *
     * 示例：
     * `RequestAction::make('删除缓存')->deleteRequest('/admin/cache/qa-info')`
     */
    public function deleteRequest(string $url): static
    {
        return $this->request($url, 'delete');
    }

    /**
     * 设置请求体或动态 payload。
     * 传数组时，内部以 "@" 开头的值会按当前动作 context 解析；
     * 传字符串或 JsExpression 时，会按前端表达式处理，并可直接返回最终请求体对象。
     * 调用该方法会切回“自定义 payload”模式，覆盖之前的 payloadFromForm() 设置。
     *
     * 解析时可用字段：
     * - action / row / tableKey / listKey
     * - filters / forms / dialogs / selection / query / page / mode
     * - dialog / dialogKey: 当前动作运行在弹窗上下文时可用
     * - import: 当前动作启用导入模式并完成文件解析后可用，可读取 rows / headers / fileName / sheetName
     * - vm
     * - reloadTable() / reloadList() / reloadPage() / closeDialog()
     * - resolveFormScope() / validateForm() / getFormModel() / cloneFormModel() / setFormModel() / initializeFormModel() / resetForm()
     * - getPageQuery() / resolvePageMode() / resolveFormMode() / loadFormData()
     *
     * 常见写法：
     * - "@row.id"
     * - "@filters.keyword"
     * - "@forms.profile.name"
     * - "@import.rows"
     * - "@page.query.id"
     * - "@page.url"
     * - "(ctx) => ({ rows: ctx.import?.rows ?? [], source: ctx.import?.fileName ?? '' })"
     * - "{ id: row?.id, ids: selection?.map(item => item.id) ?? [] }"
     *
     * @param array|string|JsExpression $payload 请求体配置，可传数组、JS 表达式字符串或 JsExpression。
     * @return static 当前请求动作实例。
     *
     * 示例：
     * `RequestAction::make('批量审核')->payload(['ids' => '@selection'])`
     */
    public function payload(array|string|JsExpression $payload): static
    {
        $this->payload = is_string($payload) ? JsExpression::ensure($payload) : $payload;
        $this->payloadSource = self::PAYLOAD_SOURCE_CUSTOM;
        $this->payloadFormScope = null;

        return $this;
    }

    /**
     * 请求前先校验指定表单。
     * 常用于独立表单页保存；成功时才会继续组装 payload 并发起请求。
     *
     * - 传表单 key 时，会校验该表单
     * - 不传时，仅在当前运行时能唯一定位表单时才会自动解析
     * - 自动解析优先当前 dialog 表单，其次页面内唯一的非 dialog 表单
     *
     * 推荐搭配：
     * - `payloadFromForm("profile")`
     * - `submitForm("profile")`
     *
     * @param string|null $scope 目标表单 scope；不传时仅在运行时能唯一定位表单时自动解析。
     * @return static 当前请求动作实例。
     *
     * 示例：
     * `RequestAction::make('保存')->validateForm('qa-info-form')`
     */
    public function validateForm(?string $scope = null): static
    {
        $this->validateForm = true;
        $this->validateFormScope = $this->normalizeFormScope($scope);

        return $this;
    }

    /**
     * 直接把表单当前模型作为请求 payload。
     * 会输出一个适合请求提交的深拷贝结果，避免把运行时响应式对象直接送进请求层。
     * 调用该方法会覆盖之前的 payload() 设置。
     *
     * - 传表单 key 时，会读取该表单
     * - 不传时，仅在当前运行时能唯一定位表单时才会自动解析
     * - 自动解析规则与 validateForm() 相同
     *
     * 常见用法：
     * - `payloadFromForm("profile")`
     * - `validateForm("profile")->payloadFromForm("profile")`
     * - `submitForm("profile")`
     *
     * @param string|null $scope 目标表单 scope；不传时仅在运行时能唯一定位表单时自动解析。
     * @return static 当前请求动作实例。
     *
     * 示例：
     * `RequestAction::make('保存')->payloadFromForm('qa-info-form')`
     */
    public function payloadFromForm(?string $scope = null): static
    {
        $this->payloadSource = self::PAYLOAD_SOURCE_FORM;
        $this->payloadFormScope = $this->normalizeFormScope($scope);

        return $this;
    }

    /**
     * CRUD 表单保存快捷方式，等价于 validateForm()->payloadFromForm()。
     * 适合独立表单页的“保存”按钮，避免再手写 `ctx.vm.validateSimpleForm(...)` /
     * `ctx.vm.getSimpleFormModel(...)` 这类内部运行时方法名。
     *
     * - 传表单 key 时，会同时用于校验和 payload 读取
     * - 不传时，仅在当前运行时能唯一定位表单时才会自动解析
     *
     * @param string|null $scope 目标表单 scope；不传时仅在运行时能唯一定位表单时自动解析。
     * @return static 当前请求动作实例。
     *
     * 示例：
     * `RequestAction::make('保存')->submitForm('qa-info-form')`
     */
    public function submitForm(?string $scope = null): static
    {
        return $this
            ->validateForm($scope)
            ->payloadFromForm($scope);
    }

    /**
     * CRUD 表单保存地址快捷方式。
     * 会根据当前页面模式自动在 create/update 地址间切换，避免再手写 PHP `if ($isEdit)`。
     *
     * 模式解析规则：
     * - 若当前动作已通过 submitForm()/validateForm()/payloadFromForm() 绑定表单，则优先读取该表单的 modeQueryKey()
     * - 否则会使用这里显式传入的 "$queryKey"
     * - "$updateUrl" 为空时，会回退到 "$createUrl"
     *
     * 常见用法：
     * - `saveUrls('/admin/user/create', '/admin/user/update')`
     * - `saveUrls('/admin/user/save')`
     * - `saveUrls('/admin/user/create', '/admin/user/update', 'user_id')`
     *
     * 调用该方法会切换到“按页面模式选请求地址”模式，覆盖之前固定的 request()/get()/post()/put()/patch()/deleteRequest() 地址。
     * 请求方法仍复用当前动作上的 method 配置，默认值为 post。
     *
     * @param string $createUrl 新建模式请求地址。
     * @param string|null $updateUrl 编辑模式请求地址；传 null 时回退到 $createUrl。
     * @param string $queryKey 用于识别 edit/create 模式的查询参数名，默认值为 id。
     * @return static 当前请求动作实例。
     *
     * 示例：
     * `RequestAction::make('保存')->saveUrls('/admin/qa-info/create', '/admin/qa-info/update')`
     */
    public function saveUrls(
        string $createUrl,
        ?string $updateUrl = null,
        string $queryKey = self::DEFAULT_MODE_QUERY_KEY
    ): static {
        $this->requestUrl = null;
        $this->saveCreateUrl = trim($createUrl);
        $this->saveUpdateUrl = ($updateUrl !== null && trim($updateUrl) !== '') ? trim($updateUrl) : $this->saveCreateUrl;
        $queryKey = trim($queryKey);
        $this->saveModeQueryKey = $queryKey !== '' ? $queryKey : self::DEFAULT_MODE_QUERY_KEY;

        return $this;
    }

    /**
     * 启用带导入面板的导入模式。
     * 点击动作后会打开导入面板，在浏览器中解析 `xlsx/xls/csv` 或 JSON，再把结果作为请求 payload 的一部分提交。
     * 若未显式调用 payload()，默认会附带：
     * - `"rows"`: 解析后的数据行
     * - `"import_column_info"`: 当前导入列配置
     *
     * 如需改字段名，可继续链式调用 importRowsKey() / importColumnInfoKey()；
     * 如需完全自定义请求体，可继续链式调用 payload()，并在 JS/context 里读取：
     * - import.rows
     * - import.headers
     * - import.fileName
     * - import.sheetName
     *
     * @param bool $enabled 是否启用导入模式，默认值为 true。
     * @return static 当前请求动作实例。
     *
     * 示例：
     * `RequestAction::make('导入')->post('/admin/qa-info/import')->enableImport()`
     */
    public function enableImport(bool $enabled = true): static
    {
        $this->importEnabled = $enabled;

        return $this;
    }

    /**
     * 配置导入字段定义。
     * 键名是提交给后端的字段名；值可写成标题字符串，或包含更多说明的数组。
     * 当前前端主要使用 `"title"` 做 Excel 表头映射；其余信息会原样通过 `"import_column_info"` 带给后端。
     *
     * 支持写法：
     * - `['name' => '名称']`
     * - `['sex' => ['title' => '性别', 'options' => [1 => '男', 2 => '女']]]`
     * - `['status' => ['title' => '状态', 'ai_data' => ['正常', '停用']]]`
     *
     * @param array $columns 导入字段配置。
     * @return static 当前请求动作实例。
     *
     * 示例：
     * `RequestAction::make('导入')->importColumns(['name' => '名称', 'code' => '编码'])`
     */
    public function importColumns(array $columns): static
    {
        $this->importEnabled = true;
        $this->importColumns = $columns;

        return $this;
    }

    /**
     * 从一个 V2 表单自动推导导入列配置。
     * 适合“新增表单字段基本就是导入字段”的场景，减少重复维护 `importColumns([...])`。
     *
     * 当前默认规则：
     * - 只自动收集顶层字段，不展开对象分组、数组分组、表格子列
     * - 默认收集 text / textarea / number / select / radio / date / datetime / switch
     * - `select` / `radio` 若配置了静态 `options()`，会自动转成导入列的 `options`
     * - `switch` 会优先尝试读取 active/inactive 配置生成导入选项
     * - 会跳过 hidden / password / editor / upload / picker / icon / checkbox / cascader 等不适合直接导入的字段
     * - 静态 disabled 字段默认跳过
     *
     * `overrides` 可对同名字段做整体覆盖，适合补 `ai_data` 或修正标题。
     *
     * @param Form $form 导入列来源表单。
     * @param array $overrides 按字段名覆盖自动推导结果。
     * @return static 当前请求动作实例。
     *
     * 示例：
     * `RequestAction::make('导入')->importColumnsFromForm($form, ['status' => ['title' => '状态', 'ai_data' => ['启用', '停用']]])`
     */
    public function importColumnsFromForm(Form $form, array $overrides = []): static
    {
        return $this->importColumns(
            (new ImportColumnResolver())->fromForm($form, $overrides)
        );
    }

    /**
     * 从一个 V2 页面中自动定位表单并推导导入列配置。
     * 适合 iframe 子页或独立表单页已经先声明成 Page 对象的场景。
     *
     * 规则：
     * - 若页面里只有一个表单，可省略 `formKey`
     * - 若页面里有多个表单，必须显式传 `formKey`
     * - 仅从页面主体 sections 中递归收集表单，不会去猜纯 URL iframe 的远端页面结构
     *
     * @param AbstractPage $page 导入列来源页面。
     * @param string|null $formKey 目标表单 key；页面只有一个表单时可省略。
     * @param array $overrides 按字段名覆盖自动推导结果。
     * @return static 当前请求动作实例。
     *
     * 示例：
     * `RequestAction::make('导入')->importColumnsFromPage($formPage, 'profile-form')`
     */
    public function importColumnsFromPage(
        AbstractPage $page,
        ?string $formKey = null,
        array $overrides = []
    ): static {
        return $this->importColumns(
            (new ImportColumnResolver())->fromPage($page, $formKey, $overrides)
        );
    }

    /**
     * 从一个 V2 弹窗声明中自动推导导入列配置。
     * 普通 form 弹窗会直接读取 dialog 内部表单；
     * iframe 弹窗则必须显式传入 iframe 子页对应的 Form 或 Page，避免去猜纯 URL 页面结构。
     *
     * 常见写法：
     * - form dialog：`importColumnsFromDialog($dialog)`
     * - iframe dialog + 子页 Form：`importColumnsFromDialog($dialog, $childForm)`
     * - iframe dialog + 子页 Page：`importColumnsFromDialog($dialog, $childPage, 'child-form')`
     *
     * @param Dialog $dialog 导入列来源弹窗。
     * @param Form|AbstractPage|null $iframeSource iframe 子页表单或页面；普通 form 弹窗可省略。
     * @param string|null $formKey 当 `$iframeSource` 是页面且包含多个表单时，用于显式指定表单 key。
     * @param array $overrides 按字段名覆盖自动推导结果。
     * @return static 当前请求动作实例。
     */
    public function importColumnsFromDialog(
        Dialog $dialog,
        Form|AbstractPage|null $iframeSource = null,
        ?string $formKey = null,
        array $overrides = []
    ): static {
        return $this->importColumns(
            (new ImportColumnResolver())->fromDialog($dialog, $iframeSource, $formKey, $overrides)
        );
    }

    /**
     * 设置导入数据行在请求体中的字段名。
     * 默认值为 `"rows"`。
     *
     * @param string $key 请求体中的数据行字段名。
     * @return static 当前请求动作实例。
     *
     * 示例：
     * `RequestAction::make('导入')->importRowsKey('items')`
     */
    public function importRowsKey(string $key = 'rows'): static
    {
        $normalized = trim($key);
        $this->importRowsKey = $normalized !== '' ? $normalized : 'rows';
        $this->importEnabled = true;

        return $this;
    }

    /**
     * 设置导入字段说明在请求体中的字段名。
     * 默认值为 `"import_column_info"`；传 null 可关闭该附带字段。
     *
     * @param string|null $key 请求体中的字段说明字段名；传 null 表示不自动附带列说明。
     * @return static 当前请求动作实例。
     *
     * 示例：
     * `RequestAction::make('导入')->importColumnInfoKey('columnInfo')`
     */
    public function importColumnInfoKey(?string $key = 'import_column_info'): static
    {
        $normalized = is_string($key) ? trim($key) : null;
        $this->importColumnInfoKey = $normalized !== '' ? $normalized : null;
        $this->importEnabled = true;

        return $this;
    }

    /**
     * 设置文件选择器允许的扩展名。
     * 默认值为 `".xlsx,.xls,.csv"`。
     *
     * @param string $accept 文件选择器 accept 值。
     * @return static 当前请求动作实例。
     *
     * 示例：
     * `RequestAction::make('导入')->importAccept('.xlsx,.xls')`
     */
    public function importAccept(string $accept): static
    {
        $normalized = trim($accept);
        $this->importAccept = $normalized !== '' ? $normalized : '.xlsx,.xls,.csv';
        $this->importEnabled = true;

        return $this;
    }

    /**
     * 设置 Excel 表头所在的行号，按 1 开始计数。
     * 默认值为 1，表示第一行是表头。
     *
     * @param int $row 表头行号，最小值为 1。
     * @return static 当前请求动作实例。
     *
     * 示例：
     * `RequestAction::make('导入')->importHeaderRow(2)`
     */
    public function importHeaderRow(int $row = 1): static
    {
        $this->importHeaderRow = max(1, $row);
        $this->importEnabled = true;

        return $this;
    }

    /**
     * 设置导入弹窗标题。
     * 不传时默认使用当前动作按钮文案。
     *
     * @param string|null $title 导入弹窗标题；传 null 表示回退到动作文案。
     * @return static 当前请求动作实例。
     *
     * 示例：
     * `RequestAction::make('导入')->importDialogTitle('导入用户数据')`
     */
    public function importDialogTitle(?string $title): static
    {
        $normalized = is_string($title) ? trim($title) : null;
        $this->importDialogTitle = $normalized !== '' ? $normalized : null;
        $this->importEnabled = true;

        return $this;
    }

    /**
     * 设置“下载模板”时的文件名。
     * 传入时可带或不带 `.xlsx` 后缀；不传时默认按当前页面 `document.title` 推导。
     *
     * @param string|null $fileName 模板下载文件名；传 null 表示使用默认推导值。
     * @return static 当前请求动作实例。
     *
     * 示例：
     * `RequestAction::make('导入')->importTemplateFileName('用户导入模板.xlsx')`
     */
    public function importTemplateFileName(?string $fileName): static
    {
        $normalized = is_string($fileName) ? trim($fileName) : null;
        $this->importTemplateFileName = $normalized !== '' ? $normalized : null;
        $this->importEnabled = true;

        return $this;
    }

    /**
     * 是否启用 JSON 导入页签。
     * 默认值为 true。
     *
     * @param bool $enabled 是否启用 JSON 导入。
     * @return static 当前请求动作实例。
     *
     * 示例：
     * `RequestAction::make('导入')->enableImportJson(false)`
     */
    public function enableImportJson(bool $enabled = true): static
    {
        $this->importJsonEnabled = $enabled;
        $this->importEnabled = true;

        return $this;
    }

    /**
     * 是否显示“复制 AI 测试数据提示词”按钮。
     * 默认值为 true。
     *
     * @param bool $enabled 是否显示 AI 提示词按钮。
     * @return static 当前请求动作实例。
     *
     * 示例：
     * `RequestAction::make('导入')->enableImportAiPrompt(false)`
     */
    public function enableImportAiPrompt(bool $enabled = true): static
    {
        $this->importAiPromptEnabled = $enabled;
        $this->importEnabled = true;

        return $this;
    }

    /**
     * 设置自定义 AI 测试数据提示词文本。
     * 不传时会根据 `importColumns()` 自动生成提示词；如需关闭按钮，请调用 `enableImportAiPrompt(false)`。
     *
     * @param string|null $prompt AI 提示词文本；传 null 表示使用默认自动生成内容。
     * @return static 当前请求动作实例。
     *
     * 示例：
     * `RequestAction::make('导入')->importAiPromptText('请生成 10 条测试 JSON 数据...')`
     */
    public function importAiPromptText(?string $prompt): static
    {
        $normalized = is_string($prompt) ? trim($prompt) : null;
        $this->importAiPromptText = $normalized !== '' ? $normalized : null;
        $this->importEnabled = true;

        return $this;
    }

    /**
     * 保存成功后执行“优先关闭宿主弹窗，否则跳转到指定 URL”的快捷方式。
     * 等价于 `afterSuccess(Events::returnTo($url)->hostTable($table))`。
     * 常用于独立表单页或 iframe 子表单页的保存成功返回。
     *
     * - 若当前页面由启用 `iframeHost()` 的 V2 iframe 弹窗打开，则会优先关闭宿主弹窗
     * - 若未传 `url` 且当前不在宿主 iframe 弹窗中，则会静默跳过
     * - 若传了 `table`，关闭前会显式请求宿主刷新该表格
     * - 不传 `table` 时，会优先使用当前 success context 的 `tableKey`
     *
     * @param string|JsExpression|null $url 返回目标 URL；可为空，表示仅尝试关闭宿主弹窗。
     * @param string|Table|null $table 宿主表格 key 或 Table 对象；用于返回前刷新宿主表格。
     * @return static 当前请求动作实例。
     *
     * 示例：
     * `RequestAction::make('保存')->returnTo('/admin/qa-info/lists', 'qa-info-table')`
     */
    public function returnTo(string|JsExpression|null $url = null, string|Table|null $table = null): static
    {
        return $this->afterSuccess(
            Events::returnTo($url)->hostTable($table)
        );
    }

    /**
     * 设置请求成功后的提示文案。
     *
     * @param string|null $successMessage 成功提示文案；传 null 表示不额外提示。
     * @return static 当前请求动作实例。
     *
     * 示例：
     * `RequestAction::make('保存')->successMessage('保存成功')`
     */
    public function successMessage(?string $successMessage): static
    {
        return parent::successMessage($successMessage);
    }

    /**
     * 设置请求失败后的提示文案。
     *
     * @param string|null $errorMessage 失败提示文案；传 null 表示沿用默认错误提示。
     * @return static 当前请求动作实例。
     *
     * 示例：
     * `RequestAction::make('保存')->errorMessage('保存失败，请重试')`
     */
    public function errorMessage(?string $errorMessage): static
    {
        return parent::errorMessage($errorMessage);
    }

    /**
     * 设置请求进行中的 loading 文案。
     *
     * @param string|null $loadingText loading 提示文案；传 null 表示关闭文案显示。
     * @return static 当前请求动作实例。
     *
     * 示例：
     * `RequestAction::make('保存')->loadingText('正在提交，请稍后...')`
     */
    public function loadingText(?string $loadingText = '请稍后...'): static
    {
        return parent::loadingText($loadingText);
    }

    /**
     * 请求成功后刷新当前表格。
     * 若当前动作实际绑定的是列表而未显式指定 tableKey，会优先走列表刷新。
     *
     * @param bool $reloadTable 是否开启成功后刷新，默认值为 true。
     * @return static 当前请求动作实例。
     *
     * 示例：
     * `RequestAction::make('批量审核')->reloadTable()`
     */
    public function reloadTable(bool $reloadTable = true): static
    {
        $this->reloadTable = $reloadTable;

        return $this;
    }

    /**
     * 请求成功后刷新整页。
     *
     * @param bool $reloadPage 是否开启成功后整页刷新，默认值为 true。
     * @return static 当前请求动作实例。
     *
     * 示例：
     * `RequestAction::make('重建缓存')->reloadPage()`
     */
    public function reloadPage(bool $reloadPage = true): static
    {
        $this->reloadPage = $reloadPage;

        return $this;
    }

    /**
     * 请求成功后自动关闭当前弹窗。
     * 仅当当前动作存在 dialog target 且运行时处于对应弹窗上下文时生效。
     *
     * @param bool $closeAfterSuccess 是否在成功后关闭弹窗，默认值为 true。
     * @return static 当前请求动作实例。
     *
     * 示例：
     * `RequestAction::make('保存')->dialog('qa-info-dialog')->closeAfterSuccess()`
     */
    public function closeAfterSuccess(bool $closeAfterSuccess = true): static
    {
        $this->closeAfterSuccess = $closeAfterSuccess;

        return $this;
    }

    /**
     * 设置请求开始前钩子。
     * handler 签名与 `on('before', ...)` 一致，只接收一个 context 对象。
     * 可传字符串、JsExpression 或结构化 Events::* 对象；字符串会自动包装成 JsExpression。
     * 可用字段与 before 事件一致，常用有 action / row / tableKey / listKey / filters / forms /
     * dialogs / selection / query / page / mode / dialog / dialogKey / vm，以及已组装好的 request。
     * 若当前请求动作启用了导入模式，还可读取：
     * - import.rows / import.headers / import.fileName / import.sheetName
     * 若当前请求动作使用了 validateForm()/payloadFromForm()/submitForm()，还可读取：
     * - formScope
     * - resolveFormScope() / validateForm() / getFormModel() / cloneFormModel() / setFormModel() / initializeFormModel() / resetForm()
     * - getPageQuery() / resolvePageMode() / resolveFormMode() / loadFormData()
     * - closeHostDialog() / reloadHostTable() / openHostDialog() / openHostTab()
     * - setHostDialogTitle() / setHostDialogFullscreen() / toggleHostDialogFullscreen() / refreshHostDialogIframe()
     *
     * @param string|JsExpression|StructuredEventInterface $beforeHook 请求前钩子逻辑。
     * @return static 当前请求动作实例。
     *
     * 示例：
     * `RequestAction::make('保存')->before('({ payload }) => { if (!payload.title) return false }')`
     */
    public function before(string|JsExpression|StructuredEventInterface $beforeHook): static
    {
        if (is_string($beforeHook)) {
            $beforeHook = JsExpression::ensure($beforeHook);
        }
        $this->beforeHook = $beforeHook;
        $this->on('before', $beforeHook);

        return $this;
    }

    /**
     * 设置请求成功后的钩子。
     * handler 签名与 `on('success', ...)` 一致，只接收一个 context 对象。
     * 可传字符串、JsExpression 或结构化 Events::* 对象；字符串会自动包装成 JsExpression。
     * 可用字段与 success 事件一致，常用有 request / response / payload / row / filters /
     * forms / dialogs / selection / query / page / mode / dialog / dialogKey / vm。
     * 若当前请求动作启用了导入模式，还可读取 import.rows / import.headers / import.fileName / import.sheetName。
     * 若当前请求动作使用了表单快捷能力，还可读取 formScope / getFormModel() / cloneFormModel() /
     * setFormModel() / initializeFormModel() / resetForm() / getPageQuery() / resolvePageMode() / resolveFormMode()。
     * 运行在 iframe 子页面时，也可调用 closeHostDialog() / reloadHostTable() / openHostTab() 等宿主桥方法。
     *
     * @param string|JsExpression|StructuredEventInterface $afterSuccessHook 请求成功后的钩子逻辑。
     * @return static 当前请求动作实例。
     *
     * 示例：
     * `RequestAction::make('保存')->afterSuccess(Events::returnTo('/admin/qa-info/lists'))`
     */
    public function afterSuccess(string|JsExpression|StructuredEventInterface $afterSuccessHook): static
    {
        if (is_string($afterSuccessHook)) {
            $afterSuccessHook = JsExpression::ensure($afterSuccessHook);
        }
        $this->afterSuccessHook = $afterSuccessHook;
        $this->on('success', $afterSuccessHook);

        return $this;
    }

    /**
     * 设置请求失败后的钩子。
     * handler 签名与 `on('fail', ...)` 一致，只接收一个 context 对象。
     * 可传字符串、JsExpression 或结构化 Events::* 对象；字符串会自动包装成 JsExpression。
     * 可用字段与 fail 事件一致，常用有 request / error / row / filters / forms / dialogs /
     * selection / query / page / mode / dialog / dialogKey / vm。
     * 若当前请求动作启用了导入模式，还可读取 import.rows / import.headers / import.fileName / import.sheetName。
     * 若当前请求动作使用了表单快捷能力，还可读取 formScope / getFormModel() / cloneFormModel() /
     * setFormModel() / initializeFormModel() / resetForm() / getPageQuery() / resolvePageMode() / resolveFormMode()。
     * 运行在 iframe 子页面时，也可调用 closeHostDialog() / reloadHostTable() / openHostTab() 等宿主桥方法。
     *
     * @param string|JsExpression|StructuredEventInterface $afterFailHook 请求失败后的钩子逻辑。
     * @return static 当前请求动作实例。
     *
     * 示例：
     * `RequestAction::make('保存')->afterFail('({ error, vm }) => vm.$message.error(error?.message ?? \"保存失败\")')`
     */
    public function afterFail(string|JsExpression|StructuredEventInterface $afterFailHook): static
    {
        if (is_string($afterFailHook)) {
            $afterFailHook = JsExpression::ensure($afterFailHook);
        }
        $this->afterFailHook = $afterFailHook;
        $this->on('fail', $afterFailHook);

        return $this;
    }

    /**
     * 设置请求结束后的 finally 钩子。
     * handler 签名与 `on('finally', ...)` 一致，只接收一个 context 对象。
     * 可传字符串、JsExpression 或结构化 Events::* 对象；字符串会自动包装成 JsExpression。
     * 可用字段与 finally 事件一致，常用有 request，以及可能存在的 response / payload / error，
     * 同时也可读取 row / filters / forms / dialogs / selection / query / page / mode / dialog / dialogKey / vm。
     * 若当前请求动作启用了导入模式，还可读取 import.rows / import.headers / import.fileName / import.sheetName。
     * 若当前请求动作使用了表单快捷能力，还可读取 formScope / getFormModel() / cloneFormModel() /
     * setFormModel() / initializeFormModel() / resetForm() / getPageQuery() / resolvePageMode() / resolveFormMode()。
     * 运行在 iframe 子页面时，也可调用 closeHostDialog() / reloadHostTable() / openHostTab() 等宿主桥方法。
     *
     * @param string|JsExpression|StructuredEventInterface $afterFinallyHook 请求完成后的钩子逻辑。
     * @return static 当前请求动作实例。
     *
     * 示例：
     * `RequestAction::make('保存')->afterFinally('({ vm }) => vm.finishSaving?.()')`
     */
    public function afterFinally(string|JsExpression|StructuredEventInterface $afterFinallyHook): static
    {
        if (is_string($afterFinallyHook)) {
            $afterFinallyHook = JsExpression::ensure($afterFinallyHook);
        }
        $this->afterFinallyHook = $afterFinallyHook;
        $this->on('finally', $afterFinallyHook);

        return $this;
    }

    /**
     * 绑定请求动作事件。
     * 可用事件：click / before / success / fail / finally。
     *
     * handler 签名：`(context) => mixed`
     * 推荐写法：`({ request, response, payload, error, row, tableKey, listKey, vm }) => {}`
     * 不按位置参数传值。
     *
     * 公共上下文：
     * - action / row / tableKey / listKey / filters / forms / dialogs / selection / query / page / mode / vm
     * - dialog / dialogKey: 动作目标指向弹窗且运行时存在对应弹窗时可用
     * - import: 当前动作启用导入模式并完成文件解析后可用，可读取 rows / headers / fileName / sheetName
     * - reloadTable() / reloadList() / reloadPage() / closeDialog()
     * - resolveFormScope() / validateForm() / getFormModel() / cloneFormModel() / setFormModel() / initializeFormModel() / resetForm()
     * - getPageQuery() / resolvePageMode() / resolveFormMode() / loadFormData()
     * - notifyDialogHost() / closeHostDialog() / reloadHostTable() / openHostDialog() / openHostTab()
     * - setHostDialogTitle() / setHostDialogFullscreen() / toggleHostDialogFullscreen() / refreshHostDialogIframe()
     *
     * 事件额外字段：
     * - click: 无额外字段，常用于点击前拦截
     * - before: request
     * - success: request / response / payload
     * - fail: request / error
     * - finally: request，以及可能存在的 response / payload / error
     *
     * @param string $event 事件名，可选 click / before / success / fail / finally。
     * @param string|JsExpression|StructuredEventInterface $handler 事件处理逻辑。
     * @return static 当前请求动作实例。
     *
     * 示例：
     * `RequestAction::make('保存')->on('success', '({ response }) => console.log(response)')`
     */
    public function on(
        #[ExpectedValues(self::SUPPORTED_ON_EVENTS)]
        string $event,
        string|JsExpression|StructuredEventInterface $handler
    ): static {
        return parent::on($event, $handler);
    }

    public function getRequestUrl(): ?string
    {
        return $this->requestUrl;
    }

    public function getRequestMethod(): string
    {
        return $this->requestMethod;
    }

    public function getPayload(): array|JsExpression
    {
        return $this->payload;
    }

    public function getPayloadSource(): string
    {
        return $this->payloadSource;
    }

    public function getPayloadFormScope(): ?string
    {
        return $this->payloadFormScope;
    }

    public function shouldValidateForm(): bool
    {
        return $this->validateForm;
    }

    public function getValidateFormScope(): ?string
    {
        return $this->validateFormScope;
    }

    public function getSuccessMessage(): ?string
    {
        return parent::getSuccessMessage();
    }

    public function getErrorMessage(): ?string
    {
        return parent::getErrorMessage();
    }

    public function getLoadingText(): ?string
    {
        return parent::getLoadingText();
    }

    public function shouldReloadTable(): bool
    {
        return $this->reloadTable;
    }

    public function shouldReloadPage(): bool
    {
        return $this->reloadPage;
    }

    public function shouldCloseAfterSuccess(): bool
    {
        return $this->closeAfterSuccess;
    }

    public function getBeforeHook(): JsExpression|StructuredEventInterface|null
    {
        return $this->beforeHook;
    }

    public function getAfterSuccessHook(): JsExpression|StructuredEventInterface|null
    {
        return $this->afterSuccessHook;
    }

    public function getAfterFailHook(): JsExpression|StructuredEventInterface|null
    {
        return $this->afterFailHook;
    }

    public function getAfterFinallyHook(): JsExpression|StructuredEventInterface|null
    {
        return $this->afterFinallyHook;
    }

    public function getSaveCreateUrl(): ?string
    {
        return $this->saveCreateUrl;
    }

    public function getSaveUpdateUrl(): ?string
    {
        return $this->saveUpdateUrl;
    }

    public function getSaveModeQueryKey(): ?string
    {
        return $this->saveModeQueryKey;
    }

    public function usesImport(): bool
    {
        return $this->importEnabled;
    }

    public function getImportColumns(): array
    {
        return $this->importColumns;
    }

    public function getImportRowsKey(): string
    {
        return $this->importRowsKey;
    }

    public function getImportColumnInfoKey(): ?string
    {
        return $this->importColumnInfoKey;
    }

    public function getImportAccept(): string
    {
        return $this->importAccept;
    }

    public function getImportHeaderRow(): int
    {
        return $this->importHeaderRow;
    }

    public function getImportDialogTitle(): ?string
    {
        return $this->importDialogTitle;
    }

    public function getImportTemplateFileName(): ?string
    {
        return $this->importTemplateFileName;
    }

    public function isImportJsonEnabled(): bool
    {
        return $this->importJsonEnabled;
    }

    public function isImportAiPromptEnabled(): bool
    {
        return $this->importAiPromptEnabled;
    }

    public function getImportAiPromptText(): ?string
    {
        return $this->importAiPromptText;
    }

    /**
     * @return array<string, string>
     */
    protected function defineSupportedEvents(): array
    {
        return [
            'click' => '点击请求按钮时触发，先于真正的请求流程执行；返回 false 可中断后续执行。',
            'before' => '请求发出前触发，适合二次校验、动态补充上下文或主动取消请求。',
            'success' => '请求成功且接口返回成功态后触发，可读取 response / payload。',
            'fail' => '请求失败或接口返回失败态时触发，可读取 error。',
            'finally' => '请求结束后总会触发，无论成功还是失败。',
        ];
    }

    private function normalizeFormScope(?string $scope): ?string
    {
        $scope = is_string($scope) ? trim($scope) : null;

        return $scope !== '' ? $scope : null;
    }
}
