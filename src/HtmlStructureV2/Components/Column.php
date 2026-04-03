<?php

namespace Sc\Util\HtmlStructureV2\Components;

final class Column
{
    private const DISPLAY_TYPE_MAPPING = 'mapping';
    private const DISPLAY_TYPE_TAG = 'tag';
    private const DISPLAY_TYPE_IMAGE = 'image';
    private const DISPLAY_TYPE_IMAGES = 'images';
    private const DISPLAY_TYPE_BOOLEAN = 'boolean';
    private const DISPLAY_TYPE_BOOLEAN_TAG = 'boolean_tag';
    private const DISPLAY_TYPE_DATETIME = 'datetime';

    private ?int $width = null;
    private ?int $minWidth = null;
    private ?string $align = null;
    private bool $sortable = false;
    private ?string $sortField = null;
    private ?string $format = null;
    private ?array $display = null;
    private ?array $search = null;
    private string $placeholder = '-';

    public function __construct(
        private readonly string $label,
        private readonly string $prop
    ) {
    }

    public static function make(string $label, string $prop): self
    {
        return new self($label, $prop);
    }

    public function width(int $width): self
    {
        $this->width = $width;

        return $this;
    }

    public function minWidth(int $minWidth): self
    {
        $this->minWidth = $minWidth;

        return $this;
    }

    public function align(string $align): self
    {
        $this->align = $align;

        return $this;
    }

    public function sortable(string|bool $sortable = true): self
    {
        if (is_string($sortable)) {
            $this->sortable = true;
            $this->sortField = $sortable;

            return $this;
        }

        $this->sortable = $sortable;

        return $this;
    }

    public function sortField(string $sortField): self
    {
        $this->sortField = $sortField;
        $this->sortable = true;

        return $this;
    }

    public function searchable(string|bool $searchable = true, ?string $field = null): self
    {
        if ($searchable === false) {
            $this->search = null;

            return $this;
        }

        $this->search ??= [];
        $this->search['type'] = is_string($searchable) ? strtoupper($searchable) : '=';
        $this->search['field'] = $field ?: $this->prop;

        return $this;
    }

    public function searchType(string $type): self
    {
        $this->search ??= [];
        $this->search['type'] = strtoupper($type);

        return $this;
    }

    public function searchField(string $field): self
    {
        $this->search ??= [];
        $this->search['field'] = $field;

        return $this;
    }

    public function format(string $format): self
    {
        $this->format = $format;

        return $this;
    }

    public function mapping(array $options, string $separator = ', '): self
    {
        $this->display = [
            'type' => self::DISPLAY_TYPE_MAPPING,
            'options' => $this->normalizeDisplayOptions($options),
            'separator' => $separator,
        ];

        return $this;
    }

    public function tag(array $options, string $defaultType = 'info'): self
    {
        $normalized = [];
        foreach ($options as $value => $option) {
            if (is_array($option)) {
                $normalized[] = [
                    'value' => $option['value'] ?? $value,
                    'label' => (string)($option['label'] ?? $value),
                    'type' => (string)($option['type'] ?? $defaultType),
                ];

                continue;
            }

            $normalized[] = [
                'value' => $value,
                'label' => (string)$option,
                'type' => $defaultType,
            ];
        }

        $this->display = [
            'type' => self::DISPLAY_TYPE_TAG,
            'options' => $normalized,
            'defaultType' => $defaultType,
        ];

        return $this;
    }

    public function image(
        int $width = 60,
        int $height = 60,
        string $fit = 'cover'
    ): self {
        $this->display = [
            'type' => self::DISPLAY_TYPE_IMAGE,
            'width' => $width,
            'height' => $height,
            'fit' => $fit,
        ];

        return $this;
    }

    public function boolean(string $truthyLabel = '是', string $falsyLabel = '否'): self
    {
        $this->display = [
            'type' => self::DISPLAY_TYPE_BOOLEAN,
            'truthyLabel' => $truthyLabel,
            'falsyLabel' => $falsyLabel,
        ];

        return $this;
    }

    public function booleanTag(
        string $truthyLabel = '是',
        string $falsyLabel = '否',
        string $truthyType = 'success',
        string $falsyType = 'info'
    ): self {
        $this->display = [
            'type' => self::DISPLAY_TYPE_BOOLEAN_TAG,
            'truthyLabel' => $truthyLabel,
            'falsyLabel' => $falsyLabel,
            'truthyType' => $truthyType,
            'falsyType' => $falsyType,
        ];

        return $this;
    }

    public function date(string $format = 'YYYY-MM-DD'): self
    {
        $this->display = [
            'type' => self::DISPLAY_TYPE_DATETIME,
            'format' => $format,
        ];

        return $this;
    }

    public function datetime(string $format = 'YYYY-MM-DD HH:mm:ss'): self
    {
        return $this->date($format);
    }

    public function images(
        int $previewNumber = 3,
        string $srcPath = 'url',
        int $width = 60,
        int $height = 60,
        string $fit = 'cover'
    ): self {
        $this->display = [
            'type' => self::DISPLAY_TYPE_IMAGES,
            'previewNumber' => $previewNumber,
            'srcPath' => $srcPath,
            'width' => $width,
            'height' => $height,
            'fit' => $fit,
        ];

        return $this;
    }

    public function placeholder(string $placeholder): self
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function prop(): string
    {
        return $this->prop;
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function getMinWidth(): ?int
    {
        return $this->minWidth;
    }

    public function getAlign(): ?string
    {
        return $this->align;
    }

    public function isSortable(): bool
    {
        return $this->sortable;
    }

    public function getFormat(): ?string
    {
        return $this->format;
    }

    public function getPlaceholder(): string
    {
        return $this->placeholder;
    }

    public function getDisplay(): ?array
    {
        return $this->display;
    }

    public function isSearchable(): bool
    {
        return $this->search !== null;
    }

    public function getSearchConfig(): ?array
    {
        if ($this->search === null) {
            return null;
        }

        return [
            'type' => strtoupper((string)($this->search['type'] ?? '=')),
            'field' => $this->search['field'] ?? $this->prop,
        ];
    }

    public function getSortField(): ?string
    {
        return $this->sortField ?: $this->prop;
    }

    private function normalizeDisplayOptions(array $options): array
    {
        $normalized = [];
        foreach ($options as $value => $label) {
            if (is_array($label) && array_key_exists('value', $label) && array_key_exists('label', $label)) {
                $normalized[] = [
                    'value' => $label['value'],
                    'label' => (string)$label['label'],
                ];

                continue;
            }

            $normalized[] = [
                'value' => $value,
                'label' => (string)$label,
            ];
        }

        return $normalized;
    }
}
