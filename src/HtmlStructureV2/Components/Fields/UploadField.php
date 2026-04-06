<?php

namespace Sc\Util\HtmlStructureV2\Components\Fields;

use Sc\Util\HtmlStructureV2\Components\Concerns\HasValidation;
use Sc\Util\HtmlStructureV2\Components\Field;
use Sc\Util\HtmlStructureV2\Contracts\Fields\ValidatableFieldInterface;
use Sc\Util\HtmlStructureV2\Enums\FieldType;

final class UploadField extends Field implements ValidatableFieldInterface
{
    use HasValidation;

    private array $upload = [];

    public function __construct(string $name, string $label)
    {
        parent::__construct($name, $label, FieldType::UPLOAD);
        $this->initializeUploadConfig();
    }

    /**
     * 设置上传接口地址。
     * 上传成功后会自动把响应解析为字段值，并触发表单 `uploadSuccess` / `uploadFail` 事件。
     */
    public function uploadUrl(string $action): static
    {
        $this->initializeUploadConfig();
        $this->upload['action'] = $action;

        return $this;
    }

    /**
     * 控制是否允许多文件上传。
     * 开启后字段默认值会切为数组；关闭时默认按单值字符串处理。
     */
    public function uploadMultiple(bool $multiple = true): static
    {
        $this->initializeUploadConfig();
        $this->upload['multiple'] = $multiple;

        if ($multiple) {
            $this->default ??= [];
        } elseif ($this->upload['limit'] === null) {
            $this->upload['limit'] = 1;
        }

        return $this;
    }

    /**
     * 切换为图片上传模式，可选是否多图。
     * 会自动把列表样式切到 `picture-card`，默认 accept 设为 `image/*`；
     * 单图模式下也会自动把数量上限收敛到 1。
     */
    public function asImage(bool $multiple = false): static
    {
        $this->initializeUploadConfig();
        $this->upload['kind'] = 'image';
        $this->upload['listType'] = 'picture-card';
        $this->upload['accept'] = $this->upload['accept'] ?: 'image/*';
        $this->upload['buttonText'] = '';
        $this->uploadMultiple($multiple);

        if (!$multiple && ($this->upload['limit'] === null || $this->upload['limit'] < 1)) {
            $this->upload['limit'] = 1;
        }

        return $this;
    }

    /**
     * 设置文件数量上限。
     * 超出时前端会直接阻止继续添加，并显示默认提示。
     */
    public function uploadLimit(int $limit): static
    {
        $this->initializeUploadConfig();
        $this->upload['limit'] = $limit;

        return $this;
    }

    /**
     * 设置 accept 过滤条件。
     */
    public function uploadAccept(string $accept): static
    {
        $this->initializeUploadConfig();
        $this->upload['accept'] = $accept;

        return $this;
    }

    /**
     * 设置上传成功响应里真正取值的路径。
     * 例如 `data.url`、`result.path`。未设置时会自动尝试 `url` / `path` / `value` / `src`
     * 以及常见的 `data` / `result` / `payload` 嵌套结构。
     */
    public function uploadResponsePath(string $path): static
    {
        $this->initializeUploadConfig();
        $this->upload['responsePath'] = $path;

        return $this;
    }

    /**
     * 设置上传按钮文案。
     */
    public function uploadButtonText(string $buttonText): static
    {
        $this->initializeUploadConfig();
        $this->upload['buttonText'] = $buttonText;

        return $this;
    }

    /**
     * 设置上传提示文案。
     */
    public function uploadTip(string $tip): static
    {
        $this->initializeUploadConfig();
        $this->upload['tip'] = $tip;

        return $this;
    }

    /**
     * 设置上传请求头。
     * 适合补 token、租户标识等静态请求头。
     */
    public function uploadHeaders(array $headers): static
    {
        $this->initializeUploadConfig();
        $this->upload['headers'] = $headers;

        return $this;
    }

    /**
     * 设置上传时额外提交的数据。
     * 这些数据会作为额外 form-data 字段一并提交给上传接口。
     */
    public function uploadData(array $data): static
    {
        $this->initializeUploadConfig();
        $this->upload['data'] = $data;

        return $this;
    }

    /**
     * 设置上传字段名，默认是 file。
     * 当后端约定字段名不是 `file` 时使用。
     */
    public function uploadName(string $name): static
    {
        $this->initializeUploadConfig();
        $this->upload['name'] = $name;

        return $this;
    }

    /**
     * 设置上传列表展示样式，例如 text / picture / picture-card。
     * 与 Element Plus `el-upload` 的 `list-type` 保持一致。
     */
    public function uploadListType(string $listType): static
    {
        $this->initializeUploadConfig();
        $this->upload['listType'] = $listType;

        return $this;
    }

    public function hasUpload(): bool
    {
        return $this->upload !== [];
    }

    public function getUpload(): array
    {
        return $this->upload;
    }

    public function getDefault(): mixed
    {
        if ($this->default !== null) {
            return $this->default;
        }

        return ($this->upload['multiple'] ?? false) ? [] : '';
    }

    protected function validationPromptPrefix(): string
    {
        return '请上传';
    }

    protected function defaultValidationTrigger(): string|array
    {
        return 'change';
    }

    private function initializeUploadConfig(): void
    {
        if ($this->upload !== []) {
            return;
        }

        $this->upload = [
            'action' => '',
            'method' => 'post',
            'name' => 'file',
            'headers' => [],
            'data' => [],
            'kind' => 'file',
            'multiple' => false,
            'limit' => 1,
            'accept' => '',
            'listType' => 'text',
            'buttonText' => '选择文件',
            'tip' => '',
            'responsePath' => '',
        ];
    }
}
