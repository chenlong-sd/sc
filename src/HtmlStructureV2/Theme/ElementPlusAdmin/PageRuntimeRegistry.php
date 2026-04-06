<?php

namespace Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin;

use InvalidArgumentException;
use Sc\Util\HtmlStructureV2\Components\Dialog;
use Sc\Util\HtmlStructureV2\Components\ListWidget;
use Sc\Util\HtmlStructureV2\Components\Table;
use Sc\Util\HtmlStructureV2\RenderContext;
use Sc\Util\HtmlStructureV2\Support\JsonExpressionEncoder;

final class PageRuntimeRegistry
{
    private const SIMPLE_STATE = 'v2.simple.state';
    private const SIMPLE_CONFIG = 'v2.simple.config';
    private const TABLE_CONFIGS = 'v2.table.configs';
    private const TABLE_KEYS = 'v2.table.keys';
    private const LIST_CONFIGS = 'v2.list.configs';
    private const LIST_KEYS = 'v2.list.keys';
    private const DIALOG_REGISTRY = 'v2.dialog.registry';
    private const REQUIRES_LIST_RUNTIME = 'v2.requires.list.runtime';
    private const PRIMARY_LIST = 'v2.primary.list';
    private const ACTION_CONFIGS = 'v2.action.configs';
    private const PAGE_EVENT_CONFIGS = 'v2.page.event.configs';

    public function __construct(
        private readonly RenderContext $context,
    ) {
    }

    public function mergeSimpleState(array $state): void
    {
        $this->context->set(self::SIMPLE_STATE, array_merge(
            $this->context->get(self::SIMPLE_STATE, []),
            $state
        ));
    }

    public function mergeSimpleConfig(array $config): void
    {
        $current = $this->context->get(self::SIMPLE_CONFIG, []);
        $this->context->set(self::SIMPLE_CONFIG, array_replace_recursive($current, $config));
    }

    public function registerFormConfig(string $scopeKey, array $config): void
    {
        $simpleConfig = $this->context->get(self::SIMPLE_CONFIG, []);

        $this->mergeSimpleConfig([
            'forms' => array_merge(
                $simpleConfig['forms'] ?? [],
                [$scopeKey => $config]
            ),
        ]);
    }

    public function registerTable(Table $table, array $config): void
    {
        $this->registerKey(self::TABLE_KEYS, $table->key(), 'table');

        $configs = $this->context->get(self::TABLE_CONFIGS, []);
        $configs[$table->key()] = $config;
        $this->context->set(self::TABLE_CONFIGS, $configs);
    }

    public function registerList(ListWidget $list, array $config): void
    {
        $this->registerKey(self::LIST_KEYS, $list->key(), 'list');

        $current = $this->context->get(self::LIST_CONFIGS, []);
        $this->context->set(self::LIST_CONFIGS, array_replace_recursive($current, [
            $list->key() => $config,
        ]));
    }

    public function registerManagedDialog(Dialog $dialog): bool
    {
        $registry = $this->context->get(self::DIALOG_REGISTRY, []);
        $objectId = spl_object_id($dialog);
        $current = $registry[$dialog->key()] ?? null;

        if ($current === $objectId) {
            return false;
        }

        if (is_int($current) && $current !== $objectId) {
            throw new InvalidArgumentException(sprintf('Duplicate V2 dialog key detected: %s', $dialog->key()));
        }

        $registry[$dialog->key()] = $objectId;
        $this->context->set(self::DIALOG_REGISTRY, $registry);

        return true;
    }

    public function registerActionConfig(string $key, array $config): string
    {
        $configs = $this->context->get(self::ACTION_CONFIGS, []);
        $configs[$key] = $config;
        $this->context->set(self::ACTION_CONFIGS, $configs);

        return $key;
    }

    public function registerPageEventHandlers(array $handlers): string
    {
        $normalizedHandlers = array_values($handlers);
        $key = 'sc_event_' . substr(sha1(JsonExpressionEncoder::encodeCompact($normalizedHandlers)), 0, 12);
        $configs = $this->context->get(self::PAGE_EVENT_CONFIGS, []);
        $configs[$key] = $normalizedHandlers;
        $this->context->set(self::PAGE_EVENT_CONFIGS, $configs);

        return $key;
    }

    public function requireListRuntime(?string $primaryListKey = null): void
    {
        $this->context->set(self::REQUIRES_LIST_RUNTIME, true);

        if (is_string($primaryListKey) && $primaryListKey !== '') {
            $this->context->set(self::PRIMARY_LIST, $primaryListKey);

            return;
        }

        if ($this->context->get(self::PRIMARY_LIST) !== null) {
            return;
        }

        $listConfigs = $this->context->get(self::LIST_CONFIGS, []);
        $firstKey = array_key_first($listConfigs);
        if (is_string($firstKey) && $firstKey !== '') {
            $this->context->set(self::PRIMARY_LIST, $firstKey);
        }
    }

    public function requiresListRuntime(): bool
    {
        return (bool) $this->context->get(self::REQUIRES_LIST_RUNTIME, false);
    }

    public function tableKeys(): array
    {
        return $this->context->get(self::TABLE_KEYS, []);
    }

    public function listKeys(): array
    {
        return $this->context->get(self::LIST_KEYS, []);
    }

    private function registerKey(string $storeKey, string $value, string $type): void
    {
        $keys = $this->context->get($storeKey, []);
        if (in_array($value, $keys, true)) {
            throw new InvalidArgumentException(sprintf('Duplicate V2 %s key detected: %s', $type, $value));
        }

        $keys[] = $value;
        $this->context->set($storeKey, $keys);
    }
}
