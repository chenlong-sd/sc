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
- The planning scope recorded in this file is limited to `sc/`.
- Work outside `sc/` may be used as validation input, but should not become planned delivery unless explicitly requested by the user.
- Do not break original V1 pages.
- Prefer V2-native capabilities first.
- Add compatibility aliases only when they can map cleanly onto a real V2-native feature.
- Keep page authoring direct and readable; avoid building heavy abstraction just to mimic V1.

## Recently Completed

### Done. V2-native drag sort / tree drag sort

This gap is no longer theoretical work.

Evidence:

- `Table::dragSort()` now exists in V2
- ElementPlusAdmin runtime already carries the tree drag integration required by real migration usage

Outcome:

- V2 pages can express sortable row drag behavior directly
- Tree-like list pages can migrate without falling back to V1 `setDraw()`

### Done. Route page migration blockers found during real usage

Real page migration already exposed and fixed several V2 mismatches.

Confirmed fixes:

- `Fields::cascader()` now keeps raw tree node options when the usage side passes cascader/tree data
- `cascaderProps()` continues to own the value/label field mapping instead of forcing select-style normalization
- V2 list rendering no longer relies on temporary debug output during migration verification

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

### Done. V2-native import action

This gap is also no longer pending planning work.

Confirmed landing points:

- `Actions::import()` now exists as the V2-native front-end Excel import entry
- Import action stays on the public `RequestAction` path instead of adding a separate hidden runtime channel
- V2 runtime now loads `xlsx` on demand for import actions and parses `xlsx/xls/csv` in the browser before submitting
- Import action now opens a V2-native import panel instead of only choosing a file immediately
- The import panel now includes template download, JSON import, AI 测试数据提示词复制, preview, and result/error display
- Import actions can map Excel headers through `importColumns([...])`
- Import actions can also derive columns from existing V2 `Form` / `Page` / `Dialog` declarations for reusable CRUD schemas
- Default request payload now carries parsed `"rows"` and `"import_column_info"`, while `payload()` can still fully override/extend the request body through public `ctx.import.*`
- README, docblocks, and tests now document and verify the richer import flow

Outcome:

- V2 pages can express common Excel/JSON import flows without falling back to V1 table theme helpers
- Import remains discoverable from the normal action API surface and keeps the same request/hook/reload ergonomics as other V2 actions

### Done. V2-native status toggle button bar

This gap is also no longer pending planning work.

Confirmed landing points:

- `Table::statusToggle()` now exists as the V2-native quick toggle entry
- Native `Table::statusToggle()` has been simplified back to `name + options + label`; backend field mapping stays in `search()` / `searchSchema()` / `Column::searchable()`
- Old `addStatusToggleButtons()` / `setStatusToggleButtonsNewLine()` can already map onto the same capability as thin compatibility aliases
- Toggle clicks now reuse the existing table search pipeline instead of introducing a parallel query path
- When a table lives inside `ListWidget`, toggle state syncs into the list filter model
- The labeled toggle-bar presentation has been aligned back to the original V1 visual structure during real-page migration

Outcome:

- V2 now has a first-class fast segmented filter bar for common enum/status fields
- List pages can preserve this high-frequency filter UX during migration

### Done. V2-native explicit table toolbar zones

Real migration usage also confirmed that table toolbar actions need an explicit left/right model instead of only a single implicit action group.

Confirmed landing points:

- `Table::toolbar()` is kept as the compatibility entry and now maps to the left side
- `Table::toolbarLeft()` and `Table::toolbarRight()` now expose explicit placement for usage-side code
- Toolbar rendering now places custom right-side actions together with built-in export / column settings tools
- Managed dialog collection and action target validation now cover both left and right toolbar action groups
- README and tests now document and verify the explicit toolbar zoning behavior

Outcome:

- Usage-side code can decide action placement directly from the public API surface
- Old `toolbar()` pages remain compatible without changing behavior

### Done. V2-native table trash flow

Real business pages also still rely on the original table trash / recover flow based on soft delete pages.

Confirmed landing points:

- `Table::trash()` now exists as the V2-native trash entry
- `Table::enableTrash()` remains as a compatibility alias
- Trash is still treated as table-owned behavior instead of a free-floating action because it changes toolbar visibility, row actions, and remote query mode together
- The table now auto-generates a managed iframe dialog that opens the current page with `is_delete=1`
- Trash mode now hides normal toolbar actions, export, settings, and row actions while keeping refresh available
- When `recoverUrl()` is configured, trash mode exposes a native batch recover button that posts selected ids to the recover endpoint
- README and tests now document and verify the full trash flow

Outcome:

- V2 pages can keep the original soft-delete recycle-bin workflow without falling back to V1 table rendering
- Usage-side code only needs table-level configuration and does not need to hand-build the recycle-bin dialog
- Native toggle API is now cleaner, while legacy search-field mapping remains isolated in the compatibility alias layer

### Done. Public CRUD form submission shortcuts

This gap is no longer theoretical planning work.

Confirmed landing points:

- `RequestAction::validateForm()` now exists as the public “validate before submit” entry
- `RequestAction::payloadFromForm()` now exists as the public “use current form model as request payload” entry
- `RequestAction::submitForm()` now exists as the common CRUD save shortcut
- `Actions::save()` now exists as the common standalone page save entry with default icon/type
- `Actions::back()` now exists as the common standalone page cancel/return entry
- `RequestAction::returnTo()` now exists as the common save-success return shortcut
- Request-action runtime context now exposes public form helpers such as `resolveFormScope()` / `validateForm()` / `getFormModel()` / `cloneFormModel()`
- Usage-side examples no longer need raw `ctx.vm.validateSimpleForm(...)` / `ctx.vm.getSimpleFormModel(...)` for the common save path
- README, docblocks, and tests have been aligned around these public shortcuts

Outcome:

- Standalone V2 form pages can submit through public DSL instead of runtime-internal `vm` method names
- Common CRUD save/cancel actions now require less handwritten JS and are discoverable from IDE/comments

### Done. iframe child-page structured host-return flow

The core host-return path is also no longer pending planning work.

Confirmed landing points:

- `Events::closeHostDialog()` now exists as the public close-host shortcut
- `Events::reloadHostTable()` now exists as the public reload-host-table shortcut
- `Events::returnTo($url)->hostTable()` now exists as the public “close host if possible, otherwise jump” flow
- Child-page runtime context now exposes host bridge helpers such as `closeHostDialog()` / `reloadHostTable()` / `openHostDialog()`
- `returnTo()` only treats real V2 iframe-dialog children as host-dialog context; ordinary tab iframes fall back to URL navigation instead of being misdetected as dialog children
- Real page usage has been rewritten to use the public structured return flow instead of raw `window.parent.postMessage(...)`

Outcome:

- iframe child pages can complete common cancel/save-success return flows without handwritten bridge payloads
- Public host-return behavior is now predictable in both dialog iframes and ordinary page/tab iframes

### Done. V2 iframe child-page public submit entry

The child-page submit entry is no longer purely an internal convention.

Confirmed landing points:

- V2 simple pages now automatically expose `"__SC_V2_PAGE__.submit(scope = null)"`
- Public page API also exposes `"validateForm()"` / `"getFormModel()"` / `"cloneFormModel()"`
- `"VueApp.submit"` remains available as a compatibility alias for existing host-side defaults
- README and runtime tests now document and verify this public child-page submit contract

Outcome:

- iframe dialog hosts can submit V2 child pages without requiring the usage side to hand-write a global submit bridge
- The preferred V2 child-page submit entry is now explicit and documentable instead of being hidden behind historical naming only

### Done. Standalone form page bootstrap and save-url switching

This gap is also no longer theoretical planning work.

Confirmed landing points:

- `Form::load()` now exists as the standalone page-form detail bootstrap entry
- `Form::loadPayload()` / `loadDataPath()` / `loadWhen()` are now also available on standalone forms, aligned with dialog-side semantics
- `Form::modeQueryKey()` now exposes create/edit mode recognition publicly on the form itself
- Standalone page runtime now exposes `query` / `page.query` / `mode` / `page.mode`
- Standalone page runtime now exposes public helpers such as `getPageQuery()` / `resolvePageMode()` / `resolveFormMode()` / `loadFormData()`
- `RequestAction::saveUrls()` now exists as the public create/update save-address switch for standalone CRUD pages
- Common page-form save flows no longer need PHP `if ($isEdit)` only to switch submit URL
- README, docblocks, and runtime tests now cover the standalone page-form bootstrap/save path

Outcome:

- Standalone V2 forms now have a native create/edit bootstrap model instead of relying only on manual PHP preload
- Page-form CRUD authoring is shorter and more IDE-visible
- The common “query id decides edit mode, load detail, save to create/update URL” path is now first-class

## Form-side Reassessment

After re-checking recent real usage around standalone form pages, dialog forms, iframe forms, and request actions, the remaining form-side gaps are mainly about authoring ergonomics and public API clarity, not basic renderer completeness.

Confirmed observations:

- `Forms::arrayGroup()` / `Forms::table()` / `Forms::tabs()` / `Forms::collapse()` already cover the structural side of complex forms well enough for current migration work
- `Dialog::load()` / `loadPayload()` / `loadWhen()` already cover the common edit-dialog bootstrap path
- `RequestAction::payload()` already exposes `forms` in public context, so form model reading can be a first-class API instead of forcing runtime-internal `vm` calls
- `Actions::submit()` already gives dialog-footer submission a relatively clear public shortcut, and standalone page forms now also have public `submitForm()` / `saveUrls()` / `Form::load()` flow
- Real standalone form-page usage still tends to fall back to internal runtime names such as `validateSimpleForm(...)` / `getSimpleFormModel(...)`
- iframe child pages can already coordinate with the host, but current usage still leans on raw `window.parent.postMessage(...)` and magic strings such as `"VueApp.submit"`
- Page-form CRUD flows still duplicate cancel / return / host-close / host-reload handling more often than they should
- Current comments expose context nouns such as `forms` / `vm`, but they still do not provide one obvious public recipe for “how to save a V2 form page” without reading runtime source

Conclusion:

- The next form-side work should not focus on adding more field types first
- The remaining priority is keeping common CRUD form flows short, explicit, documented, and IDE-visible across iframe child pages and follow-up migration cases
- Public form APIs should not require users to know runtime-internal `vm` method names
- High-frequency CRUD form tasks should have obvious defaults or dedicated shortcuts

## Current Priority Order

### P1. Completed: standalone form page bootstrap and submit model

This priority has been landed in the current iteration.

What changed:

- standalone forms now have public `load()` / `loadPayload()` / `loadWhen()` / `modeQueryKey()`
- request actions now have public `saveUrls()` for create/update address switching
- runtime context now exposes page query and mode helpers publicly

Next active priorities start from the iframe child-page and documentation tracks below.

### P2. iframe child-page form and host-bridge ergonomics

Core close/reload/return and child submit entry are now public, but iframe child-page CRUD still has one remaining consolidation area.

Evidence:

- Backward compatibility still keeps the host-side default handler path at `"VueApp.submit"`
- Host close / reload / return now have public helpers, and child pages now expose `"__SC_V2_PAGE__.submit()"`, but the end-to-end host/child recipe can still be tightened further
- V2-to-V2 iframe form flows still do not have one highest-level public shortcut for “host opens child page, child validates/submits, host saves/refreshes”

Target:

- Keep the newly landed public host-return helpers as the baseline
- Prefer the newly landed `"__SC_V2_PAGE__.submit()"` contract in docs/examples, while keeping `"VueApp.submit"` only as compatibility fallback
- Decide whether that contract should land as runtime-injected JS helper(s), PHP-side public DSL, or both, but make the usage-side entry obvious and documented
- Ensure V2-to-V2 iframe flows can rely on stronger defaults, including submit entry, host bridge, and selection-path behavior, instead of forcing per-page glue
- Keep same-origin assumptions and fallback behavior explicit in docs
- Make iframe form-page CRUD examples look like supported product patterns, not internal runtime tricks

Expected outcome:

- iframe form pages can participate in CRUD flows without handwritten bridge boilerplate
- Host-bridge usage becomes discoverable, documented, and safer to reuse
- V2 child pages can submit, close, and refresh host state through a stable public contract

### P3. Form-side public contract documentation and IDE visibility

This is required support work for the priorities above, not optional polish.

Evidence:

- Recent usage feedback repeatedly shows that “it exists in runtime” is not enough if users cannot see the supported contract from code completion and comments
- Form, request-action, iframe-child, and event contexts still have places where public and internal usage are too easy to mix up

Target:

- Clearly distinguish public form/request contexts from runtime-internal helpers
- Add IDE-visible method comments for any new CRUD shortcuts and bridge helpers
- In method comments, explicitly state handler signature, execution order, default behavior, and the available context fields for common form-save scenarios
- When comments mention special strings such as `"@forms.profile.name"` or `"VueApp.submit"`, keep them quoted so IDE popups can show the full text cleanly
- Expand README with focused form CRUD examples:
  - standalone page form save
  - dialog form save
  - iframe child-page save and host coordination
- Include migration-style before/after examples that replace raw `ctx.vm.*` or `window.parent.postMessage(...)` usage with public DSL
- Add tests around the new shortcuts so docs and behavior stay aligned

Expected outcome:

- Users can discover supported form-side patterns directly from the public API surface
- The framework becomes better at its stated goal: using the least code to build admin CRUD pages

### P4. Continue `sc/`-internal native capability cleanup driven by real usage

After the form-side CRUD ergonomics above are tightened, continue refining the native capability model inside `sc/` and only fill gaps that are proven by real usage.

Target:

- Prefer tightening native `sc/` APIs, renderer behavior, runtime contracts, and docs before expanding more compatibility surface
- When real usage exposes a V2 mismatch, fix the native capability inside `sc/` instead of adding ad-hoc page-specific workarounds
- Keep strengthening README and method-level usage notes alongside these fixes
- Treat work outside `sc/` as explicitly requested follow-up, not as default plan execution

Expected outcome:

- Remaining V2 gaps stay grounded in real business pages
- `sc/` stays focused on a clear native model instead of drifting into broad compatibility churn

## Form-side Completion Bar

The form-side plan for this phase should be considered complete only when all of the following are true:

- A standalone V2 form page can validate and submit through public DSL without raw `ctx.vm.*` calls in usage-side code
- A V2 iframe child page can close itself and refresh host state without handwritten `window.parent.postMessage(...)`
- Public docblocks and README are sufficient for a user to discover the supported save flow from IDE and docs alone
- Remaining handwritten JS in form CRUD pages is reserved for real business customization, not for framework-default plumbing

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

1. Reassess and tighten standalone form page bootstrap / submit ergonomics
2. Continue lifting iframe child-page submit flow onto a clearer public contract
3. Keep form-side docs, comments, and tests aligned with the public contract as new shortcuts land
4. Continue `sc/`-internal native cleanup and re-evaluate whether thin V1 entry aliases are still needed for migration

## Notes

- `openPage(dialog)` and `openPage(tab)` have already been brought to usable V2 behavior in recent work.
- Recent status-toggle cleanup also confirmed a planning rule: V2-native toggle API should only own filter-name binding; legacy backend-field mapping belongs in the compatibility alias path, not the native signature.
- Recent real-page migration also proved that cascader/tree data must not be normalized with select-style `value` / `label` wrapping.
- Recent standalone form-page usage also confirmed a second planning rule: public CRUD form flows must not rely on runtime-internal `vm` method names in usage-side code.
- For form-side APIs, “discoverable in IDE and comments” should be treated as part of capability completeness, not as optional documentation polish.
- Future planning should continue to distinguish between:
  - missing V2 capability
  - existing V2 capability with no migration alias

Only the first category should drive core feature priority.
