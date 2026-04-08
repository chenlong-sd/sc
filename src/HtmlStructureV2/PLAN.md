# HtmlStructureV2 Plan

## Purpose

This file records the currently confirmed next-stage plan for HtmlStructureV2.

The guiding requirement is:

- Original V1 capabilities that are meaningful in real business pages must exist in V2.
- V2 should not stop at compatibility; it should provide a clearer and stronger native model.
- Thin migration aliases are acceptable later, but they are not the primary target.

## Reconfirmed Scope

After re-checking real usage and current V2 code, the following are **not** the current priority gaps:

- Header actions
- Row actions
- Search and filter capability

Reason:

- V2 already has `Table::toolbar()`
- V2 already has `Table::rowActions()`
- V2 already has `ListWidget::filters()`
- V2 already has `Table::search()` / `Table::searchSchema()`
- V2 page-level actions already exist via `Page::actions()`

So the next phase should focus on missing feature bodies, not on re-creating old API names.

## Confirmed Principles

- Do not modify controllers for this phase unless a later task proves it is strictly necessary.
- Do not break original V1 pages.
- Prefer V2-native capabilities first.
- Add compatibility aliases only when they can map cleanly onto a real V2-native feature.
- Keep page authoring direct and readable; avoid building heavy abstraction just to mimic V1.

## Recently Completed

### Done. V2-native drag sort / tree drag sort

This gap is no longer theoretical work.

Evidence:

- `Table::dragSort()` now exists in V2
- Route-like tree usage has been migrated in `plugins/AdminBasic/Http/Admin/View/Route/lists.sc.php`
- ElementPlusAdmin runtime already carries the drag sort integration required by the migrated page

Outcome:

- V2 pages can express sortable row drag behavior directly
- Route-like tree pages can migrate without falling back to V1 `setDraw()`

### Done. Route page migration blockers found during real usage

Real page migration already exposed and fixed several V2 mismatches.

Confirmed fixes:

- `Fields::cascader()` now keeps raw tree node options when the usage side passes cascader/tree data
- `cascaderProps()` continues to own the value/label field mapping instead of forcing select-style normalization
- The V2 route list page no longer relies on debug output during rendering

### Done. V2-native export capability

This gap is no longer pending planning work.

Confirmed landing points:

- `Table::export()` now exists as the V2-native export entry
- Old `openExportExcel()` can already map onto the same export pipeline as a thin compatibility alias
- Table/list runtime now consumes column export metadata and can export current selection or filtered full data
- Export respects `onlyExportExcel()` and current column visibility settings consistently

Outcome:

- V2 tables can export without falling back to V1 rendering behavior
- Existing V2 column export metadata is now connected to a real runtime feature

### Done. V2-native status toggle button bar

This gap is also no longer pending planning work.

Confirmed landing points:

- `Table::statusToggle()` now exists as the V2-native quick toggle entry
- Old `addStatusToggleButtons()` / `setStatusToggleButtonsNewLine()` can already map onto the same capability as thin compatibility aliases
- Toggle clicks now reuse the existing table search pipeline instead of introducing a parallel query path
- When a table lives inside `ListWidget`, toggle state syncs into the list filter model

Outcome:

- V2 now has a first-class fast segmented filter bar for common enum/status fields
- QA case style pages can preserve this high-frequency filter UX during migration

## Current Priority Order

### P1. Continue migration-driven gap fixing on representative real pages

After drag sort, export, and status toggle bar landed, the next useful validation step is continuing to migrate representative pages and only filling gaps that are proven by real usage.

Target:

- Prefer migrating pages that still depend on V1-only capability or ergonomics
- When migration exposes a V2 mismatch, fix the native capability instead of adding ad-hoc page-specific workarounds
- Keep strengthening README and method-level usage notes alongside these fixes

Expected outcome:

- Remaining V2 gaps stay grounded in real business pages
- Migration cost drops without turning V2 into a thin V1 alias layer

## Deferred For Now

These items should not be treated as the current next-step priority:

- Broad compatibility work for `setHeaderEvent()`
- Broad compatibility work for `setRowEvent()`
- Broad compatibility work for `addSearch()`

Reason:

- The underlying V2 feature model for these areas already exists
- The remaining need here is migration ergonomics, not missing capability

## Delivery Strategy

The recommended execution order is:

1. Continue representative real-page migration and close newly exposed native gaps
2. Re-evaluate whether thin V1 entry aliases are still needed for migration

## Notes

- `openPage(dialog)` and `openPage(tab)` have already been brought to usable V2 behavior in recent work.
- The route list page is now a concrete proof point for V2 tree list migration.
- `plugins/QA/Http/Admin/View/QaCase/lists.sc.php` is now a concrete proof point for a managed `ListWidget` page using V2 `export()`, `statusToggle()`, and cascader/tree confirm-flow fields together.
- Recent real-page migration also proved that cascader/tree data must not be normalized with select-style `value` / `label` wrapping.
- Future planning should continue to distinguish between:
  - missing V2 capability
  - existing V2 capability with no migration alias

Only the first category should drive core feature priority.
