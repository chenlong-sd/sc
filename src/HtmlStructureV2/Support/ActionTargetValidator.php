<?php

namespace Sc\Util\HtmlStructureV2\Support;

use InvalidArgumentException;
use Sc\Util\HtmlStructureV2\Components\Action;
use Sc\Util\HtmlStructureV2\Components\RequestAction;

final class ActionTargetValidator
{
    public function __construct(
        private readonly StructuredEventInspector $structuredEventInspector = new StructuredEventInspector(),
    ) {
    }

    /**
     * @param Action[] $actions
     * @param string[] $knownTableKeys
     * @param string[] $knownListKeys
     * @param string[] $knownDialogKeys
     */
    public function validate(
        array $actions,
        array $knownTableKeys,
        array $knownListKeys,
        array $knownDialogKeys,
        string $owner
    ): void {
        foreach ($actions as $action) {
            $tableTarget = $action->tableTarget();
            if (is_string($tableTarget) && $tableTarget !== '') {
                $this->assertKnownTarget('table', $tableTarget, $knownTableKeys, $action, $owner);
            }

            $listTarget = $action->listTarget();
            if (is_string($listTarget) && $listTarget !== '') {
                $this->assertKnownTarget('list', $listTarget, $knownListKeys, $action, $owner);
            }

            $dialogTarget = $action->targetName();
            if ($this->shouldValidateDialogTarget($action) && is_string($dialogTarget) && $dialogTarget !== '') {
                $this->assertKnownTarget('dialog', $dialogTarget, $knownDialogKeys, $action, $owner);
            }

            if ($action->hasEventHandlers()) {
                $this->structuredEventInspector->validateEventMap(
                    $action->getEventHandlers(),
                    $knownTableKeys,
                    $knownListKeys,
                    $knownDialogKeys,
                    sprintf('%s action [%s]', $owner, $action->label())
                );
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
        Action $action,
        string $owner
    ): void {
        if (in_array($targetKey, $knownKeys, true)) {
            return;
        }

        throw new InvalidArgumentException(sprintf(
            'Unknown V2 %s target [%s] referenced by action [%s] in %s',
            $type,
            $targetKey,
            $action->label(),
            $owner
        ));
    }

    private function shouldValidateDialogTarget(Action $action): bool
    {
        if ($action instanceof RequestAction) {
            return true;
        }

        return in_array($action->intent()->value, ['create', 'edit', 'submit', 'close'], true);
    }
}
