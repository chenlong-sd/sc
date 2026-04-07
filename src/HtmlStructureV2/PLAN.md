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

## Current Priority Order

### P1. V2-native drag sort / tree drag sort

This is the clearest real feature gap.

Evidence:

- Real usage exists in `plugins/AdminBasic/Http/Admin/View/Route/lists.sc.php`
- V1 provides `Table::setDraw()`
- V2 currently supports remote column sorting, but not drag sorting runtime behavior

Target:

- Provide a V2-native drag sorting model for flat tables
- Provide a V2-native tree drag sorting model for tree tables where applicable
- Ensure runtime integration works in ElementPlusAdmin theme
- Keep room for a later thin alias from old `setDraw()` if needed

Expected outcome:

- V2 pages can express sortable row drag behavior directly
- Route-like tree pages can migrate without losing core ordering behavior

### P2. V2-native export capability

This is the next concrete gap after drag sort.

Evidence:

- Real usage exists in `plugins/QA/Http/Admin/View/QaCase/lists.sc.back.php`
- V1 provides `Table::openExportExcel()`
- V2 columns already carry export metadata, but there is no completed table/list export feature consuming it

Target:

- Provide a V2-native export action and export pipeline
- Reuse existing column export metadata where possible
- Support visible/export-only column rules consistently
- Keep room for a later thin alias from old `openExportExcel()`

Expected outcome:

- V2 can export table/list data without depending on V1 rendering behavior
- Existing V2 column export configuration becomes actually useful

### P3. V2-native status toggle button bar

This is a real UI capability gap, even though generic filters already exist.

Evidence:

- Real usage exists in `plugins/QA/Http/Admin/View/QaCase/lists.sc.back.php`
- V1 provides `addStatusToggleButtons()` and `setStatusToggleButtonsNewLine()`
- V2 filters can cover the same query semantics, but not the same fast segmented toolbar UX

Target:

- Provide a V2-native quick toggle filter bar for common enum/status fields
- Support single-line and wrapped/new-line display modes
- Integrate with list/table filter state instead of creating a parallel search system
- Keep room for a later thin alias from old status toggle APIs

Expected outcome:

- V2 has a first-class quick filter bar for list-heavy admin pages
- QA case style pages can migrate without losing the high-frequency filter UX

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

1. Finish V2-native drag sort / tree drag sort
2. Finish V2-native export capability
3. Finish V2-native status toggle button bar
4. Re-evaluate whether thin V1 entry aliases are still needed for migration

## Notes

- `openPage(dialog)` and `openPage(tab)` have already been brought to usable V2 behavior in recent work.
- Future planning should continue to distinguish between:
  - missing V2 capability
  - existing V2 capability with no migration alias

Only the first category should drive core feature priority.
