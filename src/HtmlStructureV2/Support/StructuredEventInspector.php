<?php

namespace Sc\Util\HtmlStructureV2\Support;

use InvalidArgumentException;
use Sc\Util\HtmlStructureV2\Components\Dialog;
use Sc\Util\HtmlStructureV2\Contracts\StructuredEventInterface;

final class StructuredEventInspector
{
    /**
     * @param array<string, array<int, mixed>> $eventMap
     * @return Dialog[]
     */
    public function collectDialogsFromEventMap(array $eventMap): array
    {
        $dialogs = [];

        foreach ($eventMap as $handlers) {
            foreach ($this->collectDialogsFromHandlers(is_array($handlers) ? $handlers : []) as $dialog) {
                $dialogs[$dialog->key()] = $dialog;
            }
        }

        return array_values($dialogs);
    }

    /**
     * @param array<int, mixed> $handlers
     * @return Dialog[]
     */
    public function collectDialogsFromHandlers(array $handlers): array
    {
        $dialogs = [];

        foreach ($handlers as $handler) {
            if (!$handler instanceof StructuredEventInterface) {
                continue;
            }

            foreach ($handler->referencedDialogs() as $dialog) {
                $dialogs[$dialog->key()] = $dialog;
            }
        }

        return array_values($dialogs);
    }

    /**
     * @param array<string, array<int, mixed>> $eventMap
     * @param string[] $knownTableKeys
     * @param string[] $knownListKeys
     * @param string[] $knownDialogKeys
     */
    public function validateEventMap(
        array $eventMap,
        array $knownTableKeys,
        array $knownListKeys,
        array $knownDialogKeys,
        string $owner
    ): void {
        foreach ($eventMap as $eventName => $handlers) {
            $this->validateHandlers(
                is_array($handlers) ? $handlers : [],
                $knownTableKeys,
                $knownListKeys,
                $knownDialogKeys,
                $owner,
                is_string($eventName) ? $eventName : 'unknown'
            );
        }
    }

    /**
     * @param array<int, mixed> $handlers
     * @param string[] $knownTableKeys
     * @param string[] $knownListKeys
     * @param string[] $knownDialogKeys
     */
    private function validateHandlers(
        array $handlers,
        array $knownTableKeys,
        array $knownListKeys,
        array $knownDialogKeys,
        string $owner,
        string $eventName
    ): void {
        foreach ($handlers as $handler) {
            if (!$handler instanceof StructuredEventInterface) {
                continue;
            }

            foreach ($handler->referencedTableKeys() as $tableKey) {
                $this->assertKnownTarget('table', $tableKey, $knownTableKeys, $owner, $eventName);
            }

            foreach ($handler->referencedListKeys() as $listKey) {
                $this->assertKnownTarget('list', $listKey, $knownListKeys, $owner, $eventName);
            }

            foreach ($handler->referencedDialogKeys() as $dialogKey) {
                $this->assertKnownTarget('dialog', $dialogKey, $knownDialogKeys, $owner, $eventName);
            }
        }
    }

    /**
     * @param string[] $knownKeys
     */
    private function assertKnownTarget(
        string $type,
        string $targetKey,
        array $knownKeys,
        string $owner,
        string $eventName
    ): void {
        if ($targetKey === '' || in_array($targetKey, $knownKeys, true)) {
            return;
        }

        throw new InvalidArgumentException(sprintf(
            'Unknown V2 %s target [%s] referenced by event [%s] in %s',
            $type,
            $targetKey,
            $eventName,
            $owner
        ));
    }
}
