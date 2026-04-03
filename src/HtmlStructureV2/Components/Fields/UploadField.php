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

    public function uploadUrl(string $action): static
    {
        $this->initializeUploadConfig();
        $this->upload['action'] = $action;

        return $this;
    }

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

    public function uploadLimit(int $limit): static
    {
        $this->initializeUploadConfig();
        $this->upload['limit'] = $limit;

        return $this;
    }

    public function uploadAccept(string $accept): static
    {
        $this->initializeUploadConfig();
        $this->upload['accept'] = $accept;

        return $this;
    }

    public function uploadResponsePath(string $path): static
    {
        $this->initializeUploadConfig();
        $this->upload['responsePath'] = $path;

        return $this;
    }

    public function uploadButtonText(string $buttonText): static
    {
        $this->initializeUploadConfig();
        $this->upload['buttonText'] = $buttonText;

        return $this;
    }

    public function uploadTip(string $tip): static
    {
        $this->initializeUploadConfig();
        $this->upload['tip'] = $tip;

        return $this;
    }

    public function uploadHeaders(array $headers): static
    {
        $this->initializeUploadConfig();
        $this->upload['headers'] = $headers;

        return $this;
    }

    public function uploadData(array $data): static
    {
        $this->initializeUploadConfig();
        $this->upload['data'] = $data;

        return $this;
    }

    public function uploadName(string $name): static
    {
        $this->initializeUploadConfig();
        $this->upload['name'] = $name;

        return $this;
    }

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
