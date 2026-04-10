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
     *
     * @param bool $showPassword 是否显示切换按钮，默认值为 true。
     * @return static 当前密码字段实例。
     *
     * 示例：
     * `Fields::password('password', '密码')->showPassword()`
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
