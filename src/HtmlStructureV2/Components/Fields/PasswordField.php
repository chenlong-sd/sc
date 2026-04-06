<?php

namespace Sc\Util\HtmlStructureV2\Components\Fields;

use Sc\Util\HtmlStructureV2\Enums\FieldType;

final class PasswordField extends TextField
{
    public function __construct(string $name, string $label)
    {
        parent::__construct($name, $label, FieldType::PASSWORD);
    }

    /**
     * 控制是否显示密码可见性切换按钮。
     */
    public function showPassword(bool $showPassword = true): static
    {
        if ($showPassword) {
            $this->props['show-password'] = '';

            return $this;
        }

        unset($this->props['show-password']);

        return $this;
    }
}
