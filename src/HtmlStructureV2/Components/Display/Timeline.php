<?php

namespace Sc\Util\HtmlStructureV2\Components\Display;

use Sc\Util\HtmlStructureV2\Components\Concerns\HasElementEvents;
use Sc\Util\HtmlStructureV2\Components\Concerns\HasRenderAttributes;
use Sc\Util\HtmlStructureV2\Contracts\EventAware;
use Sc\Util\HtmlStructureV2\Contracts\Renderable;
use Sc\Util\HtmlStructureV2\Support\RendersWithTheme;

/**
 * 时间进度展示（Element Plus el-timeline）
 *
 * 每个节点由一个时间和标题、若干描述行组成，适合展示维修记录、操作日志等按时间推进的内容。
 * 内容在服务端拼装，节点事件文案无需前端表达式。
 *
 * 示例：
 * - `Displays::timeline()
 *      ->item('2026-01-01 10:00:00', '发起模具修复申请', ['申请人：张三', '修复前规格：0.20'], 'warning')
 *      ->item('2026-01-02 09:30:00', '模具修复完成', ['完成人：李四', '修复后规格：0.21'], 'success');`
 */
final class Timeline implements Renderable, EventAware
{
    use HasElementEvents;
    use HasRenderAttributes;
    use RendersWithTheme;

    /** @var array<int, array{time: string, title: string, lines: array<int, string>, type: ?string, hollow: bool}> */
    private array $items = [];

    private string $placement = 'top';

    /**
     * 直接创建一个时间进度块实例。
     */
    public static function make(): self
    {
        return new self();
    }

    /**
     * 追加一个时间节点。
     *
     * @param string      $time   时间文案（如 2026-01-01 10:00:00）。
     * @param string      $title  节点标题。
     * @param array       $lines  描述行（按顺序展示）。
     * @param string|null $type   节点颜色：primary/success/warning/danger/info；null 使用默认色。
     * @param bool        $hollow 是否空心节点，默认 true。
     */
    public function item(string $time, string $title, array $lines = [], ?string $type = null, bool $hollow = true): self
    {
        $this->items[] = [
            'time' => $time,
            'title' => $title,
            'lines' => array_values(array_map(static fn (mixed $line): string => (string) $line, $lines)),
            'type' => $type,
            'hollow' => $hollow,
        ];

        return $this;
    }

    /**
     * 批量追加时间节点，数组项支持 time/title/lines/type/hollow 键。
     */
    public function items(array $items): self
    {
        foreach ($items as $item) {
            $this->item(
                (string) ($item['time'] ?? ''),
                (string) ($item['title'] ?? ''),
                (array) ($item['lines'] ?? []),
                isset($item['type']) ? (string) $item['type'] : null,
                (bool) ($item['hollow'] ?? true)
            );
        }

        return $this;
    }

    /**
     * 设置时间戳位置：top（默认，标题上方）或 bottom。
     */
    public function placement(string $placement): self
    {
        $placement = strtolower(trim($placement));
        $this->placement = in_array($placement, ['top', 'bottom'], true) ? $placement : 'top';

        return $this;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function getPlacement(): string
    {
        return $this->placement;
    }
}