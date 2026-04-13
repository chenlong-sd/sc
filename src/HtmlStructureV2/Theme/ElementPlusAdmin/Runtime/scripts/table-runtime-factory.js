        globalThis.__SC_V2_CREATE_TABLE_RUNTIME_METHODS__ = ({
          cfg,
          applyLocalSearch = (rows) => rows,
          buildSearchQuery = () => ({}),
          clone,
          compareValues = null,
          ensureSuccess,
          extractPayload,
          getSearchModel = () => ({}),
          makeRequest,
          pickRows,
          pickTotal = null,
          resolveMessage
        }) => {
          const {
            buildTableState,
            emitConfiguredEvent,
            formatColumnDatetime,
            getByPath,
            isBlank,
            isEventCanceled,
            isColumnFalsy,
            isColumnTruthy,
            isObject,
            isSameValue,
            readPageQuery,
            resolveContextValue,
            resolveColumnDisplayValue,
            resolveColumnMappingLabel,
            resolveColumnTagMeta,
            setByPath,
          } = globalThis.__SC_V2_RUNTIME_HELPERS__;

          const compareTableValues = typeof compareValues === 'function'
            ? compareValues
            : (left, right, order) => {
              const modifier = order === 'descending' ? -1 : 1;
              if (left === right) return 0;
              if (left === null || left === undefined) return -1 * modifier;
              if (right === null || right === undefined) return 1 * modifier;
              if (typeof left === 'number' && typeof right === 'number') {
                return (left - right) * modifier;
              }
              return String(left).localeCompare(String(right), 'zh-CN') * modifier;
            };
          const resolveTotal = typeof pickTotal === 'function'
            ? pickTotal
            : (payload, depth = 0) => {
              if (depth > 4 || payload === null || typeof payload !== 'object' || Array.isArray(payload)) {
                return null;
              }

              const directKeys = ['total', 'count'];
              for (const key of directKeys) {
                if (typeof payload[key] === 'number') return payload[key];
              }

              const nestedKeys = ['data', 'result', 'payload'];
              for (const key of nestedKeys) {
                if (payload[key] !== undefined) {
                  const total = resolveTotal(payload[key], depth + 1);
                  if (typeof total === 'number') return total;
                }
              }

              return null;
            };
          const sanitizeExportFilename = (filename, fallback = 'export') => {
            const normalized = typeof filename === 'string' ? filename.trim() : '';
            const baseName = normalized.replace(/\.(xlsx|xls)$/i, '').trim() || fallback;

            return `${baseName}.xlsx`;
          };
          const stringifyExportValue = (value, separator = ', ') => {
            if (value === null || value === undefined) {
              return '';
            }

            if (Array.isArray(value)) {
              return value
                .map((item) => stringifyExportValue(item, separator))
                .filter((item) => item !== '')
                .join(separator);
            }

            if (typeof value === 'object') {
              if (typeof value.label === 'string' && value.label !== '') {
                return value.label;
              }
              if (typeof value.name === 'string' && value.name !== '') {
                return value.name;
              }
              if (typeof value.url === 'string' && value.url !== '') {
                return value.url;
              }

              try {
                return JSON.stringify(value);
              } catch (error) {
                return String(value);
              }
            }

            const resolved = resolveColumnDisplayValue(value, separator);
            return resolved === null || resolved === undefined ? '' : String(resolved);
          };
          const stripExportHtml = (value) => {
            if (value === null || value === undefined || value === '') {
              return '';
            }

            return String(value)
              .replace(/<br\s*\/?>/gi, '\n')
              .replace(/<\/(div|p|li|tr|h[1-6])>/gi, '\n')
              .replace(/<[^>]+>/g, '')
              .replace(/&nbsp;/gi, ' ')
              .replace(/\r/g, '');
          };
          const evaluateExportExpression = (expression, row = {}) => {
            const source = typeof expression === 'string' ? expression.trim() : '';
            if (source === '') {
              return '';
            }

            try {
              const executor = new Function(
                'row',
                'scope',
                'helpers',
                `
                  const window = undefined;
                  const document = undefined;
                  const vm = undefined;
                  with (helpers || {}) {
                    with (row || {}) {
                      return (${source});
                    }
                  }
                `
              );

              return executor(row || {}, { row: row || {} }, { getByPath });
            } catch (error) {
              return '';
            }
          };
          const renderExportTemplate = (template, row = {}) => {
            const rawTemplate = typeof template === 'string' ? template : '';
            if (rawTemplate.trim() === '') {
              return '';
            }

            const normalizedTemplate = rawTemplate.replace(/scope\.row/g, 'row');
            const matcher = /{{([\s\S]+?)}}/g;
            let cursor = 0;
            let output = '';
            let matched = false;
            let match = null;

            while ((match = matcher.exec(normalizedTemplate)) !== null) {
              matched = true;
              output += stripExportHtml(normalizedTemplate.slice(cursor, match.index));
              output += stringifyExportValue(evaluateExportExpression(match[1], row));
              cursor = match.index + match[0].length;
            }

            output += stripExportHtml(normalizedTemplate.slice(cursor));

            return matched ? output.trim() : stripExportHtml(normalizedTemplate).trim();
          };
          const resolveExportDisplayValue = (value, column = {}) => {
            const display = isObject(column?.display) ? column.display : {};

            switch (display.type) {
              case 'mapping':
                return stringifyExportValue(
                  resolveColumnMappingLabel(value, Array.isArray(display.options) ? display.options : [], display.separator || ', ')
                );
              case 'tag':
                return stringifyExportValue(
                  resolveColumnTagMeta(value, Array.isArray(display.options) ? display.options : [], display.defaultType || 'info')?.label ?? ''
                );
              case 'boolean':
              case 'boolean_tag':
                if (isColumnTruthy(value)) {
                  return String(display.truthyLabel ?? '是');
                }
                if (isColumnFalsy(value)) {
                  return String(display.falsyLabel ?? '否');
                }

                return '';
              case 'switch':
                return stringifyExportValue(
                  resolveColumnMappingLabel(value, Array.isArray(display.options) ? display.options : [], ', ')
                );
              case 'datetime':
                return stringifyExportValue(
                  formatColumnDatetime(value, String(display.format || 'YYYY-MM-DD HH:mm:ss'))
                );
              case 'image':
                return stringifyExportValue(value);
              case 'images':
                if (!Array.isArray(value)) {
                  return '';
                }

                return value
                  .map((item) => {
                    const srcPath = typeof display.srcPath === 'string' ? display.srcPath : 'url';
                    return srcPath === ''
                      ? stringifyExportValue(item)
                      : stringifyExportValue(getByPath(item, srcPath));
                  })
                  .filter((item) => item !== '')
                  .join(', ');
              case 'open_page':
                return stringifyExportValue(resolveColumnDisplayValue(value));
              default:
                return stringifyExportValue(resolveColumnDisplayValue(value));
            }
          };
          const resolveExportCellValue = (row, column = {}) => {
            if (typeof column?.format === 'string' && column.format.trim() !== '') {
              return renderExportTemplate(column.format, row || {});
            }

            return resolveExportDisplayValue(
              getByPath(row || {}, column?.key || ''),
              column
            );
          };
          const resolveTableExportColumns = (vm, tableKey, tableCfg = {}) => {
            const source = Array.isArray(tableCfg?.export?.columns) ? tableCfg.export.columns : [];
            const settings = typeof vm?.getTableSettings === 'function'
              ? vm.getTableSettings(tableKey)
              : null;
            const settingsMap = new Map(
              (Array.isArray(settings?.columns) ? settings.columns : [])
                .filter((item) => isObject(item) && typeof item.key === 'string' && item.key !== '')
                .map((item) => [String(item.key), item])
            );

            return source
              .map((item, index) => {
                if (!isObject(item) || typeof item.key !== 'string' || item.key === '') {
                  return null;
                }

                const key = String(item.key);
                const setting = settingsMap.get(key) || null;
                if (setting) {
                  if (setting.export === false) {
                    return null;
                  }
                } else if (item.respectVisibility === true && typeof vm?.getTableColumnVisible === 'function') {
                  if (vm.getTableColumnVisible(tableKey, key) === false) {
                    return null;
                  }
                }

                return Object.assign({}, item, {
                  key,
                  sort: normalizeTableExportSort(setting?.exportSort, normalizeTableExportSort(item.sort)),
                  _index: index,
                });
              })
              .filter(Boolean)
              .sort((left, right) => {
                const leftSort = normalizeTableExportSort(left?.sort, left?._index ?? 0);
                const rightSort = normalizeTableExportSort(right?.sort, right?._index ?? 0);

                return leftSort - rightSort || ((left?._index ?? 0) - (right?._index ?? 0));
              })
              .map((item) => {
                const nextItem = Object.assign({}, item);
                delete nextItem._index;

                return nextItem;
              });
          };
          const buildRemoteTableRequest = (
            vm,
            tableKey,
            tableCfg,
            state,
            { includePagination = true, extraQuery = {} } = {}
          ) => {
            const searchModel = resolveTableSearchModel(vm, tableKey, tableCfg, state);
            const baseQuery = Object.assign({}, tableCfg?.dataSource?.query || {});
            const pageQuery = resolveTablePageQuery(tableCfg?.dataSource || {});
            if (!Object.prototype.hasOwnProperty.call(baseQuery, 'query') && pageQuery !== '') {
              baseQuery.query = pageQuery;
            }

            const query = Object.assign(
              {},
              baseQuery,
              buildSearchQuery(searchModel, tableCfg?.searchSchema || {}, tableKey, tableCfg),
              includePagination && tableCfg?.pagination?.enabled !== false ? {
                page: state?.page || 1,
                pageSize: state?.pageSize || tableCfg?.pagination?.pageSize || 20
              } : {},
              state?.sort?.field ? {
                order: {
                  field: tableCfg?.sortFieldMap?.[state.sort.field] || state.sort.field,
                  order: state.sort.order
                }
              } : {},
              isObject(extraQuery) ? extraQuery : {}
            );

            if (query?.query === '') {
              delete query.query;
            }

            return Object.assign({}, tableCfg?.dataSource || {}, { query });
          };
          const getTableStatusToggleItems = (tableCfg = {}) => {
            return Array.isArray(tableCfg?.statusToggles?.items)
              ? tableCfg.statusToggles.items.filter((item) => typeof item?.name === 'string' && item.name !== '')
              : [];
          };
          const resolveTableFilterScope = (vm, tableCfg = {}, tableKey = null) => {
            const listKey = typeof tableCfg?.listKey === 'string' && tableCfg.listKey !== ''
              ? tableCfg.listKey
              : (typeof vm?.resolveTableListKey === 'function' ? vm.resolveTableListKey(tableKey) : null);
            if (typeof listKey !== 'string' || listKey === '') {
              return null;
            }

            if (typeof vm?.getListConfig === 'function') {
              return vm.getListConfig(listKey)?.filterScope || null;
            }

            return cfg?.lists?.[listKey]?.filterScope || null;
          };
          const syncTableStatusToggleState = (vm, tableKey, tableCfg, state) => {
            const items = getTableStatusToggleItems(tableCfg);
            if (!state || items.length <= 0) {
              return {};
            }

            if (!isObject(state.quickFilters)) {
              state.quickFilters = {};
            }

            const filterScope = resolveTableFilterScope(vm, tableCfg, tableKey);
            const filterModel = filterScope && typeof vm?.getFormModel === 'function'
              ? (vm.getFormModel(filterScope) || {})
              : null;

            items.forEach((item) => {
              const resolvedFilterValue = filterModel
                ? getByPath(filterModel, item.name)
                : undefined;
              const value = resolvedFilterValue !== undefined
                ? resolvedFilterValue
                : state.quickFilters[item.name];

              state.quickFilters[item.name] = isBlank(value) ? null : value;
            });

            return state.quickFilters;
          };
          const resolveTableSearchModel = (vm, tableKey, tableCfg, state) => {
            const baseSearchModel = getSearchModel(vm, tableKey, tableCfg);
            const model = isObject(baseSearchModel)
              ? clone(baseSearchModel)
              : {};
            const quickFilters = syncTableStatusToggleState(vm, tableKey, tableCfg, state);

            Object.keys(quickFilters || {}).forEach((name) => {
              const value = quickFilters[name];
              if (isBlank(value)) {
                delete model[name];
                return;
              }

              model[name] = value;
            });

            return model;
          };
          const buildTableEventContext = (vm, tableKey, tableCfg, state, overrides = {}) => {
            const filters = resolveTableSearchModel(vm, tableKey, tableCfg, state) || {};

            return Object.assign({
              tableKey,
              tableConfig: tableCfg || {},
              tableState: state || null,
              state: state || null,
              rows: state?.rows || [],
              allRows: state?.allRows || [],
              selection: state?.selection || [],
              filters,
              vm
            }, overrides);
          };
          const emitTableEvent = (vm, tableKey, tableCfg, state, eventName, overrides = {}) => {
            return emitConfiguredEvent(
              tableCfg || {},
              eventName,
              buildTableEventContext(vm, tableKey, tableCfg, state, overrides)
            );
          };
          const getStorage = () => {
            try {
              if (typeof window !== 'undefined' && window?.localStorage) {
                return window.localStorage;
              }
            } catch (error) {
            }

            return null;
          };
          const getCurrentPageQueryString = () => {
            try {
              if (typeof window === 'undefined' || !window?.location?.search) {
                return '';
              }

              const params = new URLSearchParams(window.location.search);
              params.delete('global_search');

              return params.toString();
            } catch (error) {
              return '';
            }
          };
          const resolveTablePageQuery = (dataSource = null) => {
            if (isObject(dataSource?.query) && Object.prototype.hasOwnProperty.call(dataSource.query, 'query')) {
              return dataSource.query.query;
            }

            try {
              if (typeof dataSource?.url === 'string' && dataSource.url !== '') {
                const parsedUrl = new URL(dataSource.url, window.location.href);
                if (parsedUrl.searchParams.has('query')) {
                  return parsedUrl.searchParams.get('query') || '';
                }
              }
            } catch (error) {
            }

            return getCurrentPageQueryString();
          };
          const resolveTableTrashQueryKey = (tableCfg = {}) => {
            const queryKey = typeof tableCfg?.trash?.queryKey === 'string' ? tableCfg.trash.queryKey.trim() : '';
            return queryKey !== '' ? queryKey : 'is_delete';
          };
          const normalizeComparableValue = (value) => {
            if (value === null || value === undefined) {
              return '';
            }

            return String(value).trim().toLowerCase();
          };
          const isTableTrashActive = (vm, tableKey = null, tableCfg = null) => {
            const resolvedKey = typeof vm?.resolveTableKey === 'function'
              ? vm.resolveTableKey(tableKey)
              : tableKey;
            const resolvedTableCfg = tableCfg || (typeof vm?.getTableConfig === 'function' ? vm.getTableConfig(resolvedKey) : null);
            if (resolvedTableCfg?.trash?.enabled !== true) {
              return false;
            }

            const queryKey = resolveTableTrashQueryKey(resolvedTableCfg);
            const query = typeof vm?.getPageQuery === 'function'
              ? vm.getPageQuery()
              : readPageQuery();
            const currentValue = query?.[queryKey];
            const expectedValue = normalizeComparableValue(resolvedTableCfg?.trash?.queryValue ?? 1);

            if (Array.isArray(currentValue)) {
              return currentValue.some((item) => normalizeComparableValue(item) === expectedValue);
            }

            return normalizeComparableValue(currentValue) === expectedValue;
          };
          const normalizeTableWidth = (value, fallback = null) => {
            if (value === '' || value === null || value === undefined) {
              return fallback ?? null;
            }
            if (typeof value === 'number' && Number.isFinite(value)) {
              return value;
            }

            const normalized = String(value).trim();
            if (normalized === '') {
              return fallback ?? null;
            }

            return /^\d+$/.test(normalized) ? Number(normalized) : normalized;
          };
          const normalizeTableFixed = (value, fallback = null) => {
            const normalized = typeof value === 'string' ? value.trim() : '';
            if (normalized === 'left' || normalized === 'right') {
              return normalized;
            }

            return fallback ?? null;
          };
          const normalizeTableAlign = (value, fallback = null) => {
            const normalized = typeof value === 'string' ? value.trim() : '';
            if (normalized === 'left' || normalized === 'center' || normalized === 'right') {
              return normalized;
            }

            return fallback ?? null;
          };
          const normalizeTableExportSort = (value, fallback = null) => {
            if (value === '' || value === null || value === undefined) {
              return fallback ?? null;
            }

            const normalized = Number(value);
            if (Number.isFinite(normalized)) {
              return normalized;
            }

            return fallback ?? null;
          };
          const normalizeTableSettingsSortMode = (value, fallback = 'display') => {
            const normalized = typeof value === 'string' ? value.trim() : '';

            return normalized === 'export' || normalized === 'display'
              ? normalized
              : (fallback === 'export' ? 'export' : 'display');
          };
          const normalizeTableSettingColumn = (column = {}, defaults = {}) => {
            const source = isObject(column) ? column : {};
            const fallback = isObject(defaults) ? defaults : {};
            const key = typeof fallback.key === 'string' && fallback.key !== ''
              ? fallback.key
              : (typeof source.key === 'string' ? source.key : '');
            const fallbackExport = fallback.export !== false;
            const hasSourceExport = typeof source.export === 'boolean';
            const exportEnabled = hasSourceExport
              ? source.export
              : (fallbackExport === false
                ? false
                : (typeof source.show === 'boolean' ? source.show : true));

            return {
              key,
              label: typeof fallback.label === 'string' && fallback.label !== ''
                ? fallback.label
                : (typeof source.label === 'string' && source.label !== '' ? source.label : key),
              show: typeof source.show === 'boolean'
                ? source.show
                : (fallback.show !== false),
              width: normalizeTableWidth(source.width, fallback.width ?? null),
              fixed: normalizeTableFixed(source.fixed, normalizeTableFixed(fallback.fixed)),
              align: normalizeTableAlign(source.align, normalizeTableAlign(fallback.align)),
              export: exportEnabled,
              exportSort: normalizeTableExportSort(source.exportSort, normalizeTableExportSort(fallback.exportSort)),
            };
          };
          const normalizeTableSettingsState = (settings = {}, defaults = {}) => {
            const source = isObject(settings) ? settings : {};
            const fallback = isObject(defaults) ? defaults : {};
            const fallbackColumns = Array.isArray(fallback.columns) ? fallback.columns : [];
            const persistedColumns = Array.isArray(source.columns) ? source.columns : [];
            const fallbackMap = new Map(
              fallbackColumns
                .filter((item) => isObject(item) && typeof item.key === 'string' && item.key !== '')
                .map((item) => [String(item.key), item])
            );
            const persistedMap = new Map(
              persistedColumns
                .filter((item) => isObject(item) && typeof item.key === 'string' && item.key !== '')
                .map((item) => [String(item.key), item])
            );
            const orderedKeys = [];
            const orderedKeySet = Object.create(null);

            persistedColumns.forEach((item) => {
              const key = typeof item?.key === 'string' ? String(item.key) : '';
              if (key !== '' && fallbackMap.has(key) && orderedKeySet[key] !== true) {
                orderedKeySet[key] = true;
                orderedKeys.push(key);
              }
            });
            fallbackColumns.forEach((item) => {
              const key = typeof item?.key === 'string' ? String(item.key) : '';
              if (key !== '' && orderedKeySet[key] !== true) {
                orderedKeySet[key] = true;
                orderedKeys.push(key);
              }
            });

            return {
              enabled: (fallback.enabled === true),
              stripe: typeof source.stripe === 'boolean' ? source.stripe : (fallback.stripe !== false),
              border: typeof source.border === 'boolean' ? source.border : (fallback.border !== false),
              columns: orderedKeys
                .map((key) => normalizeTableSettingColumn(
                  persistedMap.get(key) || fallbackMap.get(key) || {},
                  fallbackMap.get(key) || {}
                ))
                .filter((item) => item.key !== '')
            };
          };
          const orderTableSettingColumns = (columns = [], sortMode = 'display') => {
            const list = Array.isArray(columns) ? columns : [];
            const mode = normalizeTableSettingsSortMode(sortMode, 'display');
            const decorated = list.map((item, index) => ({
              item,
              index,
              exportSort: normalizeTableExportSort(item?.exportSort, index),
            }));

            if (mode === 'export') {
              decorated.sort((left, right) => (
                left.exportSort - right.exportSort
                || (left.index - right.index)
              ));
            }

            return decorated.map((entry) => entry.item);
          };
          const buildTableSettingsColumnCache = (columns = []) => {
            const list = Array.isArray(columns) ? columns : [];
            const columnsByKey = Object.create(null);
            const renderColumnKeys = [];

            list.forEach((item) => {
              const key = typeof item?.key === 'string' ? String(item.key) : '';
              if (key === '' || columnsByKey[key] !== undefined) {
                return;
              }

              columnsByKey[key] = item;
              renderColumnKeys.push(key);
            });

            return {
              columnsByKey,
              renderColumnKeys,
            };
          };
          const refreshTableSettingsCacheState = (state = null) => {
            if (!isObject(state)) {
              return state;
            }

            const settingsColumns = Array.isArray(state.settings?.columns) ? state.settings.columns : [];
            const draftColumns = Array.isArray(state.settingsDraft?.columns) ? state.settingsDraft.columns : [];
            const settingsCache = buildTableSettingsColumnCache(settingsColumns);

            state.settingsColumnsByKey = settingsCache.columnsByKey;
            state.renderColumnKeys = settingsCache.renderColumnKeys;
            state.settingsDraftDisplayColumns = draftColumns;
            state.settingsDraftExportColumns = orderTableSettingColumns(draftColumns, 'export');
            syncTableSettingsVirtualStates(state);

            return state;
          };
          const ensureTableSettingsCacheState = (state = null) => {
            if (!isObject(state)) {
              return state;
            }

            if (
              !isObject(state.settingsColumnsByKey)
              || !Array.isArray(state.renderColumnKeys)
              || !Array.isArray(state.settingsDraftDisplayColumns)
              || !Array.isArray(state.settingsDraftExportColumns)
            ) {
              return refreshTableSettingsCacheState(state);
            }

            return state;
          };
          const ensureGlobalSelectionStore = () => {
            const host = typeof globalThis !== 'undefined'
              ? globalThis
              : (typeof window !== 'undefined' ? window : null);
            if (!host || typeof host !== 'object') {
              return null;
            }

            if (!isObject(host.__scV2Selections)) {
              host.__scV2Selections = {};
            }
            if (!Array.isArray(host.__scV2Selection)) {
              host.__scV2Selection = [];
            }

            return host;
          };
          const getResolvedPrimaryTableKey = (vm) => {
            if (typeof cfg?.primaryTable === 'string' && cfg.primaryTable !== '') {
              return cfg.primaryTable;
            }

            const tableConfigs = typeof vm?.ensureTableConfigStore === 'function'
              ? vm.ensureTableConfigStore()
              : (cfg?.tables || {});

            return Object.keys(tableConfigs || {})[0] || '';
          };
          const getStoredTableSelection = (vm, tableKey = null) => {
            const resolvedKey = typeof vm?.resolveTableKey === 'function'
              ? vm.resolveTableKey(tableKey)
              : (tableKey || getResolvedPrimaryTableKey(vm));
            if (typeof resolvedKey !== 'string' || resolvedKey === '') {
              return [];
            }

            const tableStates = typeof vm?.ensureTableStateStore === 'function'
              ? vm.ensureTableStateStore()
              : {};
            const state = isObject(tableStates?.[resolvedKey]) ? tableStates[resolvedKey] : null;

            return Array.isArray(state?.selection) ? state.selection : [];
          };
          const normalizeActiveTableSelection = (state, tableCfg = {}) => {
            const selection = Array.isArray(state?.selection) ? state.selection : [];
            if (selection.length <= 0) {
              return [];
            }

            const rows = Array.isArray(state?.rows) ? state.rows : [];
            if (rows.length <= 0) {
              return [];
            }

            const compareKey = typeof tableCfg?.deleteKey === 'string' && tableCfg.deleteKey !== ''
              ? tableCfg.deleteKey
              : 'id';
            const rowKeys = new Set(
              rows
                .map((item) => item?.[compareKey])
                .filter((value) => value !== undefined && value !== null && value !== '')
            );

            if (rowKeys.size > 0) {
              return selection.filter((item) => rowKeys.has(item?.[compareKey]));
            }

            return selection.filter((item) => rows.includes(item));
          };
          const syncGlobalTableSelection = (vm, tableKey = null) => {
            const host = ensureGlobalSelectionStore();
            if (!host) {
              return [];
            }

            const resolvedKey = typeof vm?.resolveTableKey === 'function'
              ? vm.resolveTableKey(tableKey)
              : (tableKey || getResolvedPrimaryTableKey(vm));
            if (typeof resolvedKey === 'string' && resolvedKey !== '') {
              host.__scV2Selections[resolvedKey] = getStoredTableSelection(vm, resolvedKey);
            }

            const primaryTableKey = getResolvedPrimaryTableKey(vm);
            host.__scV2Selection = typeof primaryTableKey === 'string' && primaryTableKey !== ''
              ? (host.__scV2Selections[primaryTableKey] || [])
              : [];

            return host.__scV2Selection;
          };
          const normalizeTableRowKey = (value) => {
            if (value === null || value === undefined || value === '') {
              return '';
            }

            return String(value);
          };
          const getTableRowKeyField = (tableCfg = {}) => {
            return typeof tableCfg?.rowKey === 'string' && tableCfg.rowKey !== ''
              ? tableCfg.rowKey
              : 'id';
          };
          const getTableTreeChildrenKey = (tableCfg = {}) => {
            if (typeof tableCfg?.tree?.childrenKey === 'string' && tableCfg.tree.childrenKey !== '') {
              return tableCfg.tree.childrenKey;
            }

            if (typeof tableCfg?.tree?.props?.children === 'string' && tableCfg.tree.props.children !== '') {
              return tableCfg.tree.props.children;
            }

            return 'children';
          };
          const getTableRowKeyValue = (row, tableCfg = {}) => {
            return normalizeTableRowKey(getByPath(row || {}, getTableRowKeyField(tableCfg)));
          };
          const flattenTableRows = (rows, tableCfg = {}, output = []) => {
            const list = Array.isArray(rows) ? rows : [];
            const childrenKey = getTableTreeChildrenKey(tableCfg);

            list.forEach((row) => {
              output.push(row);

              if (tableCfg?.tree?.enabled === true && Array.isArray(row?.[childrenKey]) && row[childrenKey].length > 0) {
                flattenTableRows(row[childrenKey], tableCfg, output);
              }
            });

            return output;
          };
          const buildTableRowEntryMap = (
            rows,
            tableCfg = {},
            parentRow = null,
            parentKey = null,
            siblings = null,
            depth = 0,
            map = new Map()
          ) => {
            const list = Array.isArray(rows) ? rows : [];
            const childrenKey = getTableTreeChildrenKey(tableCfg);
            const currentSiblings = Array.isArray(siblings) ? siblings : list;

            list.forEach((row, index) => {
              const key = getTableRowKeyValue(row, tableCfg);
              if (key !== '') {
                map.set(key, {
                  key,
                  row,
                  index,
                  depth,
                  parentRow,
                  parentKey,
                  siblings: currentSiblings,
                });
              }

              if (tableCfg?.tree?.enabled === true && Array.isArray(row?.[childrenKey]) && row[childrenKey].length > 0) {
                buildTableRowEntryMap(row[childrenKey], tableCfg, row, key || null, row[childrenKey], depth + 1, map);
              }
            });

            return map;
          };
          const isTableEntryDescendantOf = (entry, ancestorKey, entryMap) => {
            if (!entry || !ancestorKey || !(entryMap instanceof Map)) {
              return false;
            }

            let current = entry;
            while (current) {
              if (current.parentKey === ancestorKey) {
                return true;
              }

              current = current.parentKey ? (entryMap.get(String(current.parentKey)) || null) : null;
            }

            return false;
          };
          const moveTreeTableRow = (rows, tableCfg = {}, movedKey = '', anchorKey = '', isUp = false) => {
            const entryMap = buildTableRowEntryMap(rows, tableCfg);
            const movedEntry = entryMap.get(movedKey);
            if (!movedEntry) {
              return null;
            }

            const anchorEntry = anchorKey ? (entryMap.get(anchorKey) || null) : null;
            if (anchorEntry && isTableEntryDescendantOf(anchorEntry, movedKey, entryMap)) {
              return {
                error: '不能拖动到自身子节点范围内',
              };
            }

            const sourceSiblings = Array.isArray(movedEntry.siblings) ? movedEntry.siblings : (Array.isArray(rows) ? rows : []);
            const sourceIndex = sourceSiblings.findIndex((item) => getTableRowKeyValue(item, tableCfg) === movedKey);
            if (sourceIndex < 0) {
              return null;
            }

            const removed = sourceSiblings.splice(sourceIndex, 1);
            const movedRow = removed[0] || movedEntry.row;
            let targetSiblings = anchorEntry?.siblings || (Array.isArray(rows) ? rows : []);
            let insertIndex = targetSiblings.length;

            if (anchorEntry) {
              const anchorIndex = targetSiblings.findIndex((item) => getTableRowKeyValue(item, tableCfg) === anchorKey);
              insertIndex = anchorIndex < 0 ? targetSiblings.length : (isUp ? anchorIndex : anchorIndex + 1);
            } else if (sourceSiblings === targetSiblings) {
              insertIndex = Math.min(sourceIndex, targetSiblings.length);
            }

            targetSiblings.splice(insertIndex, 0, movedRow);

            return {
              movedRow,
              oldParentRow: movedEntry.parentRow || null,
              newParentRow: anchorEntry?.parentRow || null,
              anchorRow: anchorEntry?.row || null,
              sameParent: normalizeTableRowKey(movedEntry.parentKey) === normalizeTableRowKey(anchorEntry?.parentKey || null),
            };
          };
          const ensureTableSortableStore = (vm) => {
            if (!isObject(vm.__scV2TableSortables)) {
              vm.__scV2TableSortables = {};
            }

            return vm.__scV2TableSortables;
          };
          const destroyTableSortable = (vm, tableKey = null) => {
            const resolvedKey = typeof vm?.resolveTableKey === 'function'
              ? vm.resolveTableKey(tableKey)
              : tableKey;
            if (typeof resolvedKey !== 'string' || resolvedKey === '') {
              return null;
            }

            const store = ensureTableSortableStore(vm);
            const sortable = store[resolvedKey];
            if (sortable && typeof sortable.destroy === 'function') {
              sortable.destroy();
            }

            delete store[resolvedKey];

            return null;
          };
          const getTableBodyElement = (vm, tableKey = null) => {
            const resolvedKey = typeof vm?.resolveTableKey === 'function'
              ? vm.resolveTableKey(tableKey)
              : tableKey;
            const ref = vm?.$refs?.[resolvedKey];
            const tableRef = Array.isArray(ref) ? ref[0] : ref;
            const tableEl = tableRef?.$el || tableRef;

            return tableEl?.querySelector?.('.el-table__body-wrapper tbody')
              || tableEl?.querySelector?.('table > tbody')
              || null;
          };
          const ensureTableSettingsSortableStore = (vm) => {
            if (!isObject(vm.__scV2TableSettingsSortables)) {
              vm.__scV2TableSettingsSortables = {};
            }

            return vm.__scV2TableSettingsSortables;
          };
          const destroyTableSettingsSortable = (vm, tableKey = null) => {
            const resolvedKey = typeof vm?.resolveTableKey === 'function'
              ? vm.resolveTableKey(tableKey)
              : tableKey;
            if (typeof resolvedKey !== 'string' || resolvedKey === '') {
              return null;
            }

            const store = ensureTableSettingsSortableStore(vm);
            const sortable = store[resolvedKey];
            if (sortable && typeof sortable.destroy === 'function') {
              sortable.destroy();
            }

            delete store[resolvedKey];

            return null;
          };
          const ensureTableSettingsViewportObserverStore = (vm) => {
            if (!isObject(vm.__scV2TableSettingsViewportObservers)) {
              vm.__scV2TableSettingsViewportObservers = {};
            }

            return vm.__scV2TableSettingsViewportObservers;
          };
          const destroyTableSettingsViewportObservers = (vm, tableKey = null) => {
            const resolvedKey = typeof vm?.resolveTableKey === 'function'
              ? vm.resolveTableKey(tableKey)
              : tableKey;
            if (typeof resolvedKey !== 'string' || resolvedKey === '') {
              return null;
            }

            const store = ensureTableSettingsViewportObserverStore(vm);
            const observers = isObject(store[resolvedKey]) ? store[resolvedKey] : {};

            Object.keys(observers).forEach((mode) => {
              const observer = observers?.[mode]?.observer;
              if (observer && typeof observer.disconnect === 'function') {
                observer.disconnect();
              }
            });

            delete store[resolvedKey];

            return null;
          };
          const observeTableSettingsViewport = (vm, tableKey = null, mode = null) => {
            if (typeof ResizeObserver !== 'function') {
              return null;
            }

            const resolvedKey = typeof vm?.resolveTableKey === 'function'
              ? vm.resolveTableKey(tableKey)
              : tableKey;
            if (typeof resolvedKey !== 'string' || resolvedKey === '') {
              return null;
            }

            const state = typeof vm?.getTableState === 'function'
              ? vm.getTableState(resolvedKey)
              : null;
            const resolvedMode = normalizeTableSettingsSortMode(
              mode,
              normalizeTableSettingsSortMode(state?.settingsTab, 'display')
            );
            const scrollElement = getTableSettingsScrollElement(vm, resolvedKey, resolvedMode);
            if (!scrollElement) {
              return null;
            }

            const store = ensureTableSettingsViewportObserverStore(vm);
            if (!isObject(store[resolvedKey])) {
              store[resolvedKey] = {};
            }

            const current = store[resolvedKey]?.[resolvedMode] || null;
            if (current?.element === scrollElement && current?.observer) {
              return current.observer;
            }

            if (current?.observer && typeof current.observer.disconnect === 'function') {
              current.observer.disconnect();
            }

            const observer = new ResizeObserver(() => {
              vm.updateTableSettingsVirtualViewport(resolvedKey, resolvedMode);
            });

            observer.observe(scrollElement);
            store[resolvedKey][resolvedMode] = {
              observer,
              element: scrollElement,
            };

            return observer;
          };
          const getTableSettingsRootElement = (vm, tableKey = null, mode = null) => {
            const resolvedKey = typeof vm?.resolveTableKey === 'function'
              ? vm.resolveTableKey(tableKey)
              : tableKey;
            if (typeof resolvedKey !== 'string' || resolvedKey === '' || typeof document === 'undefined') {
              return null;
            }

            const resolvedMode = normalizeTableSettingsSortMode(
              mode,
              normalizeTableSettingsSortMode(vm?.getTableState?.(resolvedKey)?.settingsTab, 'display')
            );
            const tables = Array.from(document.querySelectorAll('[data-sc-table-settings-key]'));

            return tables.find((element) => (
              String(element?.dataset?.scTableSettingsKey || '').trim() === resolvedKey
              && String(element?.dataset?.scTableSettingsMode || '').trim() === resolvedMode
            )) || null;
          };
          const getTableSettingsBodyElement = (vm, tableKey = null, mode = null) => {
            const target = getTableSettingsRootElement(vm, tableKey, mode);

            return target?.querySelector?.('[data-sc-table-settings-body]')
              || target?.querySelector?.('.el-table__body-wrapper tbody')
              || target?.querySelector?.('table > tbody')
              || null;
          };
          const getTableSettingsScrollElement = (vm, tableKey = null, mode = null) => {
            const target = getTableSettingsRootElement(vm, tableKey, mode);

            return target?.querySelector?.('[data-sc-table-settings-scroll]')
              || target?.querySelector?.('[data-sc-table-settings-body]')
              || null;
          };
          const runAfterTableSettingsLayout = (vm, callback = null) => {
            const execute = () => {
              if (typeof window !== 'undefined' && typeof window.requestAnimationFrame === 'function') {
                return new Promise((resolve) => {
                  window.requestAnimationFrame(() => {
                    resolve(typeof callback === 'function' ? callback() : null);
                  });
                });
              }

              return Promise.resolve(typeof callback === 'function' ? callback() : null);
            };

            if (typeof vm?.$nextTick === 'function') {
              return Promise.resolve(vm.$nextTick()).then(() => execute());
            }

            return execute();
          };
          const SETTINGS_VIRTUAL_ROW_HEIGHT = 50;
          const SETTINGS_VIRTUAL_OVERSCAN = 6;
          const SETTINGS_VIRTUAL_THRESHOLD = 40;
          const SETTINGS_VIRTUAL_DEFAULT_VIEWPORT = {
            display: 420,
            export: 470,
          };
          const buildTableSettingsVirtualModeState = (mode = 'display') => {
            const resolvedMode = normalizeTableSettingsSortMode(mode, 'display');

            return {
              mode: resolvedMode,
              scrollTop: 0,
              viewportHeight: SETTINGS_VIRTUAL_DEFAULT_VIEWPORT[resolvedMode] || SETTINGS_VIRTUAL_DEFAULT_VIEWPORT.display,
              rowHeight: SETTINGS_VIRTUAL_ROW_HEIGHT,
              overscan: SETTINGS_VIRTUAL_OVERSCAN,
              threshold: SETTINGS_VIRTUAL_THRESHOLD,
              enabled: false,
              start: 0,
              end: 0,
              topPadding: 0,
              bottomPadding: 0,
              totalHeight: 0,
            };
          };
          const ensureTableSettingsVirtualStateStore = (state = null) => {
            if (!isObject(state)) {
              return null;
            }
            if (!isObject(state.settingsVirtual)) {
              state.settingsVirtual = {};
            }

            const store = state.settingsVirtual;
            ['display', 'export'].forEach((mode) => {
              if (!isObject(store[mode])) {
                store[mode] = buildTableSettingsVirtualModeState(mode);
              }
            });

            return store;
          };
          const getTableSettingsVirtualColumns = (state = null, mode = 'display') => {
            if (!isObject(state)) {
              return [];
            }

            return normalizeTableSettingsSortMode(mode, 'display') === 'export'
              ? (Array.isArray(state.settingsDraftExportColumns) ? state.settingsDraftExportColumns : [])
              : (Array.isArray(state.settingsDraftDisplayColumns) ? state.settingsDraftDisplayColumns : []);
          };
          const syncTableSettingsVirtualModeState = (state = null, mode = 'display') => {
            if (!isObject(state)) {
              return null;
            }

            const resolvedMode = normalizeTableSettingsSortMode(mode, 'display');
            const store = ensureTableSettingsVirtualStateStore(state);
            const virtual = store?.[resolvedMode];
            const rows = getTableSettingsVirtualColumns(state, resolvedMode);
            if (!virtual) {
              return null;
            }

            const rowCount = Array.isArray(rows) ? rows.length : 0;
            const rowHeight = Math.max(1, Number(virtual.rowHeight) || SETTINGS_VIRTUAL_ROW_HEIGHT);
            const threshold = Math.max(0, Number(virtual.threshold) || SETTINGS_VIRTUAL_THRESHOLD);
            const overscan = Math.max(0, Number(virtual.overscan) || SETTINGS_VIRTUAL_OVERSCAN);
            const viewportHeight = Math.max(
              1,
              Number(virtual.viewportHeight) || SETTINGS_VIRTUAL_DEFAULT_VIEWPORT[resolvedMode] || SETTINGS_VIRTUAL_DEFAULT_VIEWPORT.display
            );
            const totalHeight = rowCount * rowHeight;
            const maxScrollTop = Math.max(0, totalHeight - viewportHeight);
            const scrollTop = Math.max(0, Math.min(Number(virtual.scrollTop) || 0, maxScrollTop));

            virtual.mode = resolvedMode;
            virtual.scrollTop = scrollTop;
            virtual.viewportHeight = viewportHeight;
            virtual.rowHeight = rowHeight;
            virtual.overscan = overscan;
            virtual.threshold = threshold;
            virtual.totalHeight = totalHeight;
            virtual.enabled = rowCount > threshold;

            if (virtual.enabled !== true) {
              virtual.start = 0;
              virtual.end = rowCount;
              virtual.topPadding = 0;
              virtual.bottomPadding = 0;
              return virtual;
            }

            const visibleCount = Math.max(1, Math.ceil(viewportHeight / rowHeight) + (overscan * 2));
            const maxStart = Math.max(0, rowCount - visibleCount);
            const rawStart = Math.max(0, Math.floor(scrollTop / rowHeight) - overscan);
            const start = Math.min(rawStart, maxStart);
            const end = Math.min(rowCount, start + visibleCount);

            virtual.start = start;
            virtual.end = end;
            virtual.topPadding = start * rowHeight;
            virtual.bottomPadding = Math.max(0, (rowCount - end) * rowHeight);

            return virtual;
          };
          const syncTableSettingsVirtualStates = (state = null) => {
            if (!isObject(state)) {
              return null;
            }

            syncTableSettingsVirtualModeState(state, 'display');
            syncTableSettingsVirtualModeState(state, 'export');

            return state.settingsVirtual || null;
          };
          return {
            ensureTableConfigStore(){
              if (!isObject(this.tableConfigs)) {
                this.tableConfigs = Object.assign({}, cfg?.tables || {});
              }

              return this.tableConfigs;
            },
            ensureTableStateStore(){
              if (!isObject(this.tableStates)) {
                this.tableStates = {};
              }

              return this.tableStates;
            },
            getPrimaryTableKey(){
              return cfg?.primaryTable || Object.keys(this.ensureTableConfigStore())[0] || '';
            },
            resolveTableKey(tableKey = null){
              return tableKey || this.getPrimaryTableKey();
            },
            getTableConfig(tableKey = null){
              const resolvedKey = this.resolveTableKey(tableKey);
              if (!resolvedKey) {
                return null;
              }

              return this.ensureTableConfigStore()[resolvedKey] || null;
            },
            getTableState(tableKey = null, initialize = true){
              const resolvedKey = this.resolveTableKey(tableKey);
              if (!resolvedKey) {
                return null;
              }

              const states = this.ensureTableStateStore();
              if (initialize && !isObject(states[resolvedKey])) {
                states[resolvedKey] = buildTableState(this.getTableConfig(resolvedKey) || {});
              }

              syncGlobalTableSelection(this, resolvedKey);

              return states[resolvedKey] || null;
            },
            getTableRows(tableKey = null){
              return this.getTableState(tableKey)?.rows || [];
            },
            getTableSelection(tableKey = null){
              return this.getTableState(tableKey)?.selection || [];
            },
            isTableTrashMode(tableKey = null){
              const resolvedKey = this.resolveTableKey(tableKey);
              const tableCfg = this.getTableConfig(resolvedKey);

              return isTableTrashActive(this, resolvedKey, tableCfg);
            },
            getTableStatusToggleValue(tableKey = null, name = ''){
              const resolvedKey = this.resolveTableKey(tableKey);
              const tableCfg = this.getTableConfig(resolvedKey);
              const state = this.getTableState(resolvedKey);
              if (!resolvedKey || !tableCfg || !state || typeof name !== 'string' || name === '') {
                return null;
              }

              syncTableStatusToggleState(this, resolvedKey, tableCfg, state);

              return state.quickFilters?.[name] ?? null;
            },
            isTableStatusToggleActive(tableKey = null, name = '', value = null){
              const currentValue = this.getTableStatusToggleValue(tableKey, name);

              if (value === null || value === undefined || value === '') {
                return isBlank(currentValue);
              }

              return isSameValue(currentValue, value);
            },
            setTableStatusToggle(tableKey = null, name = '', value = null){
              const resolvedKey = this.resolveTableKey(tableKey);
              const tableCfg = this.getTableConfig(resolvedKey);
              const state = this.getTableState(resolvedKey);
              if (!resolvedKey || !tableCfg || !state || typeof name !== 'string' || name === '') {
                return Promise.resolve(null);
              }

              if (!isObject(state.quickFilters)) {
                state.quickFilters = {};
              }

              const normalizedValue = isBlank(value) ? null : value;
              state.quickFilters[name] = normalizedValue;

              const filterScope = resolveTableFilterScope(this, tableCfg, resolvedKey);
              if (filterScope && typeof this.setFormPathValue === 'function') {
                this.setFormPathValue(filterScope, name, normalizedValue);
              }

              if (tableCfg.pagination?.enabled !== false) {
                state.page = 1;
              }

              return this.loadTableData(resolvedKey);
            },
            getTableSettingsStorageKey(tableKey = null){
              const resolvedKey = this.resolveTableKey(tableKey);
              const tableCfg = this.getTableConfig(resolvedKey);
              const suffix = typeof tableCfg?.settings?.storageKey === 'string' && tableCfg.settings.storageKey !== ''
                ? tableCfg.settings.storageKey
                : resolvedKey;
              const pathname = typeof window !== 'undefined' && window?.location?.pathname
                ? window.location.pathname
                : '';

              return `${pathname}@${suffix}`;
            },
            ensureTableSettingsLoaded(tableKey = null){
              const resolvedKey = this.resolveTableKey(tableKey);
              const tableCfg = this.getTableConfig(resolvedKey);
              const state = this.getTableState(resolvedKey);
              if (!resolvedKey || !tableCfg || !state) {
                return null;
              }

              if (state.settingsLoaded === true) {
                ensureTableSettingsCacheState(state);
                return state.settings || null;
              }

              const defaults = normalizeTableSettingsState(state.settingsDefault || {}, state.settingsDefault || {});
              let nextSettings = clone(defaults);

              if (tableCfg.settings?.enabled === true) {
                const storage = getStorage();
                if (storage) {
                  try {
                    const raw = storage.getItem(this.getTableSettingsStorageKey(resolvedKey));
                    if (raw) {
                      nextSettings = normalizeTableSettingsState(JSON.parse(raw), defaults);
                    }
                  } catch (error) {
                  }
                }
              }

              state.settings = clone(nextSettings);
              state.settingsDraft = clone(nextSettings);
              state.settingsLoaded = true;
              refreshTableSettingsCacheState(state);

              return state.settings;
            },
            getTableSettings(tableKey = null){
              const state = this.getTableState(tableKey);
              if (!state) {
                return null;
              }

              return this.ensureTableSettingsLoaded(tableKey) || state.settings || null;
            },
            getTableRenderColumnKeys(tableKey = null){
              this.getTableSettings(tableKey);
              const state = this.getTableState(tableKey);
              ensureTableSettingsCacheState(state);

              return Array.isArray(state?.renderColumnKeys) ? state.renderColumnKeys : [];
            },
            getTableSettingsDraftColumns(tableKey = null, sortMode = null){
              const resolvedKey = this.resolveTableKey(tableKey);
              this.getTableSettings(resolvedKey);
              const state = this.getTableState(resolvedKey);
              const currentMode = normalizeTableSettingsSortMode(
                sortMode,
                normalizeTableSettingsSortMode(state?.settingsTab, 'display')
              );
              ensureTableSettingsCacheState(state);

              return currentMode === 'export'
                ? (Array.isArray(state?.settingsDraftExportColumns) ? state.settingsDraftExportColumns : [])
                : (Array.isArray(state?.settingsDraftDisplayColumns) ? state.settingsDraftDisplayColumns : []);
            },
            getTableSettingsVirtualState(tableKey = null, mode = null){
              const resolvedKey = this.resolveTableKey(tableKey);
              this.getTableSettings(resolvedKey);
              const state = this.getTableState(resolvedKey);
              const resolvedMode = normalizeTableSettingsSortMode(
                mode,
                normalizeTableSettingsSortMode(state?.settingsTab, 'display')
              );
              ensureTableSettingsCacheState(state);
              syncTableSettingsVirtualModeState(state, resolvedMode);

              return ensureTableSettingsVirtualStateStore(state)?.[resolvedMode] || null;
            },
            getTableSettingsVirtualRows(tableKey = null, mode = null){
              const resolvedKey = this.resolveTableKey(tableKey);
              const resolvedMode = normalizeTableSettingsSortMode(mode, 'display');
              const rows = this.getTableSettingsDraftColumns(resolvedKey, resolvedMode);
              const virtual = this.getTableSettingsVirtualState(resolvedKey, resolvedMode);
              if (!Array.isArray(rows)) {
                return [];
              }
              if (virtual?.enabled !== true) {
                return rows;
              }

              return rows.slice(Number(virtual.start) || 0, Number(virtual.end) || rows.length);
            },
            getTableSettingsVirtualStart(tableKey = null, mode = null){
              return Number(this.getTableSettingsVirtualState(tableKey, mode)?.start || 0);
            },
            getTableSettingsVirtualTopPadding(tableKey = null, mode = null){
              return Number(this.getTableSettingsVirtualState(tableKey, mode)?.topPadding || 0);
            },
            getTableSettingsVirtualBottomPadding(tableKey = null, mode = null){
              return Number(this.getTableSettingsVirtualState(tableKey, mode)?.bottomPadding || 0);
            },
            updateTableSettingsVirtualViewport(tableKey = null, mode = null){
              const resolvedKey = this.resolveTableKey(tableKey);
              const state = this.getTableState(resolvedKey);
              if (!state) {
                return null;
              }

              const resolvedMode = normalizeTableSettingsSortMode(
                mode,
                normalizeTableSettingsSortMode(state?.settingsTab, 'display')
              );
              const virtual = ensureTableSettingsVirtualStateStore(state)?.[resolvedMode] || null;
              const scrollElement = getTableSettingsScrollElement(this, resolvedKey, resolvedMode);
              if (virtual && scrollElement) {
                const nextHeight = Math.max(0, Number(scrollElement.clientHeight || scrollElement.offsetHeight) || 0);
                if (nextHeight > 0) {
                  virtual.viewportHeight = nextHeight;
                }
                virtual.scrollTop = Math.max(0, Number(scrollElement.scrollTop || 0));
              }

              return syncTableSettingsVirtualModeState(state, resolvedMode);
            },
            handleTableSettingsScroll(tableKey = null, mode = null, event = null){
              const resolvedKey = this.resolveTableKey(tableKey);
              const state = this.getTableState(resolvedKey);
              if (!state) {
                return null;
              }

              const resolvedMode = normalizeTableSettingsSortMode(
                mode,
                normalizeTableSettingsSortMode(state?.settingsTab, 'display')
              );
              const virtual = ensureTableSettingsVirtualStateStore(state)?.[resolvedMode] || null;
              const target = event?.target || getTableSettingsScrollElement(this, resolvedKey, resolvedMode);
              if (!virtual || !target) {
                return syncTableSettingsVirtualModeState(state, resolvedMode);
              }

              virtual.scrollTop = Math.max(0, Number(target.scrollTop || 0));
              const nextHeight = Math.max(0, Number(target.clientHeight || target.offsetHeight) || 0);
              if (nextHeight > 0) {
                virtual.viewportHeight = nextHeight;
              }

              return syncTableSettingsVirtualModeState(state, resolvedMode);
            },
            getTableColumnSetting(tableKey = null, columnKey = ''){
              this.getTableSettings(tableKey);
              const state = this.getTableState(tableKey);
              if (typeof columnKey !== 'string' || columnKey === '') {
                return null;
              }
              ensureTableSettingsCacheState(state);

              return state?.settingsColumnsByKey?.[columnKey] || null;
            },
            getTableColumnVisible(tableKey = null, columnKey = ''){
              const setting = this.getTableColumnSetting(tableKey, columnKey);

              return setting ? setting.show !== false : true;
            },
            getTableColumnWidth(tableKey = null, columnKey = '', fallback = null){
              const setting = this.getTableColumnSetting(tableKey, columnKey);
              const width = setting?.width;

              return width === '' || width === null || width === undefined ? fallback : width;
            },
            getTableColumnAlign(tableKey = null, columnKey = '', fallback = null){
              const setting = this.getTableColumnSetting(tableKey, columnKey);
              const align = typeof setting?.align === 'string' ? setting.align : '';

              return align !== '' ? align : fallback;
            },
            getTableColumnFixed(tableKey = null, columnKey = '', fallback = null){
              const setting = this.getTableColumnSetting(tableKey, columnKey);
              const fixed = typeof setting?.fixed === 'string' ? setting.fixed : '';

              return fixed !== '' ? fixed : fallback;
            },
            getTableStripe(tableKey = null, fallback = true){
              const settings = this.getTableSettings(tableKey);

              return typeof settings?.stripe === 'boolean' ? settings.stripe : fallback;
            },
            getTableBorder(tableKey = null, fallback = true){
              const settings = this.getTableSettings(tableKey);

              return typeof settings?.border === 'boolean' ? settings.border : fallback;
            },
            moveTableSettingsColumn(tableKey = null, oldIndex = -1, newIndex = -1){
              const resolvedKey = this.resolveTableKey(tableKey);
              const state = this.getTableState(resolvedKey);
              const columns = Array.isArray(state?.settingsDraft?.columns) ? state.settingsDraft.columns : null;
              const sortMode = normalizeTableSettingsSortMode(state?.settingsTab, 'display');
              const from = Number(oldIndex);
              const to = Number(newIndex);
              if (!columns || !Number.isInteger(from) || !Number.isInteger(to) || from < 0 || to < 0 || from === to) {
                return columns || [];
              }
              if (from >= columns.length || to >= columns.length) {
                return columns;
              }

              if (sortMode === 'export') {
                const ordered = orderTableSettingColumns(columns, 'export').slice();
                const moved = ordered.splice(from, 1)[0];
                if (!moved) {
                  return columns;
                }

                ordered.splice(to, 0, moved);
                ordered.forEach((item, index) => {
                  if (isObject(item)) {
                    item.exportSort = index + 1;
                  }
                });
                refreshTableSettingsCacheState(state);

                return columns;
              }

              const moved = columns.splice(from, 1)[0];
              if (!moved) {
                return columns;
              }

              columns.splice(to, 0, moved);
              refreshTableSettingsCacheState(state);

              return columns;
            },
            refreshTableSettingsSort(tableKey = null){
              const resolvedKey = this.resolveTableKey(tableKey);
              const state = this.getTableState(resolvedKey);
              destroyTableSettingsSortable(this, resolvedKey);

              if (!state || state.settingsVisible !== true || typeof Sortable !== 'function') {
                return null;
              }

              const columns = Array.isArray(state.settingsDraft?.columns) ? state.settingsDraft.columns : [];
              const sortMode = normalizeTableSettingsSortMode(state?.settingsTab, 'display');
              if (columns.length <= 1) {
                return null;
              }

              const tbody = getTableSettingsBodyElement(this, resolvedKey, sortMode);
              if (!tbody) {
                return null;
              }

              const sortable = new Sortable(tbody, {
                animation: 150,
                handle: '.sc-v2-table-settings-drag-handle',
                onEnd: (event) => {
                  const oldIndex = Number(event?.oldIndex);
                  const newIndex = Number(event?.newIndex);
                  const indexOffset = this.getTableSettingsVirtualStart(resolvedKey, sortMode);
                  if (!Number.isInteger(oldIndex) || !Number.isInteger(newIndex) || oldIndex === newIndex) {
                    return;
                  }

                  this.moveTableSettingsColumn(resolvedKey, oldIndex + indexOffset, newIndex + indexOffset);
                },
              });

              ensureTableSettingsSortableStore(this)[resolvedKey] = sortable;

              return sortable;
            },
            syncTableSettingsSort(tableKey = null){
              const resolvedKey = this.resolveTableKey(tableKey);
              if (!resolvedKey) {
                return Promise.resolve(null);
              }

              return runAfterTableSettingsLayout(this, () => {
                this.updateTableSettingsVirtualViewport(resolvedKey);
                observeTableSettingsViewport(this, resolvedKey);

                return this.refreshTableSettingsSort(resolvedKey);
              });
            },
            openTableSettings(tableKey = null){
              const resolvedKey = this.resolveTableKey(tableKey);
              const state = this.getTableState(resolvedKey);
              const settings = this.ensureTableSettingsLoaded(resolvedKey);
              if (!state || settings?.enabled !== true) {
                return null;
              }

              state.settingsTab = 'display';
              state.settingsVisible = true;
              refreshTableSettingsCacheState(state);

              return this.syncTableSettingsSort(resolvedKey).then(() => state.settingsDraft);
            },
            closeTableSettings(tableKey = null){
              const resolvedKey = this.resolveTableKey(tableKey);
              const state = this.getTableState(resolvedKey);
              if (!state) {
                return null;
              }

              destroyTableSettingsSortable(this, resolvedKey);
              destroyTableSettingsViewportObservers(this, resolvedKey);
              state.settingsVisible = false;
              state.settingsDraft = clone(state.settings || state.settingsDefault || {});
              state.settingsTab = 'display';
              refreshTableSettingsCacheState(state);

              return state.settingsDraft;
            },
            setTableSettingsDialogVisible(tableKey = null, visible = false){
              if (visible) {
                return this.openTableSettings(tableKey);
              }

              return this.closeTableSettings(tableKey);
            },
            resetTableSettingsDraft(tableKey = null){
              const resolvedKey = this.resolveTableKey(tableKey);
              const state = this.getTableState(resolvedKey);
              if (!state) {
                return null;
              }

              state.settingsDraft = clone(state.settingsDefault || {});
              state.settingsTab = 'display';
              refreshTableSettingsCacheState(state);
              return this.syncTableSettingsSort(resolvedKey).then(() => state.settingsDraft);
            },
            persistTableSettings(tableKey = null){
              const resolvedKey = this.resolveTableKey(tableKey);
              const tableCfg = this.getTableConfig(resolvedKey);
              const state = this.getTableState(resolvedKey);
              if (!resolvedKey || !tableCfg || !state || tableCfg.settings?.enabled !== true) {
                return false;
              }

              const storage = getStorage();
              if (!storage) {
                return false;
              }

              const payload = normalizeTableSettingsState(state.settings || {}, state.settingsDefault || {});

              try {
                storage.setItem(this.getTableSettingsStorageKey(resolvedKey), JSON.stringify({
                  stripe: payload.stripe,
                  border: payload.border,
                  columns: payload.columns.map((item) => ({
                    key: item.key,
                    show: item.show,
                    width: item.width ?? null,
                    fixed: item.fixed ?? null,
                    align: item.align ?? null,
                    export: item.export !== false,
                    exportSort: normalizeTableExportSort(item.exportSort),
                  })),
                }));

                return true;
              } catch (error) {
                return false;
              }
            },
            refreshTableLayout(tableKey = null){
              const resolvedKey = this.resolveTableKey(tableKey);
              const ref = this.$refs?.[resolvedKey];
              const tableRef = Array.isArray(ref) ? ref[0] : ref;

              if (tableRef && typeof tableRef.doLayout === 'function') {
                tableRef.doLayout();
              }

              return tableRef || null;
            },
            destroyTableDragSort(tableKey = null){
              return destroyTableSortable(this, tableKey);
            },
            refreshTableDragSort(tableKey = null){
              const resolvedKey = this.resolveTableKey(tableKey);
              const tableCfg = this.getTableConfig(resolvedKey);
              const state = this.getTableState(resolvedKey);
              if (!resolvedKey || !tableCfg || !state) {
                return null;
              }

              destroyTableSortable(this, resolvedKey);

              if (tableCfg?.dragSort?.enabled !== true || typeof Sortable !== 'function') {
                return null;
              }

              const rowKeyField = getTableRowKeyField(tableCfg);
              if (typeof rowKeyField !== 'string' || rowKeyField === '') {
                return null;
              }

              const tbody = getTableBodyElement(this, resolvedKey);
              if (!tbody) {
                return null;
              }

              const rowCount = flattenTableRows(state.rows || [], tableCfg).length;
              if (rowCount <= 1) {
                return null;
              }

              const options = isObject(tableCfg?.dragSort?.options) ? tableCfg.dragSort.options : {};
              const handleClass = typeof tableCfg?.dragSort?.handleClass === 'string' && tableCfg.dragSort.handleClass !== ''
                ? tableCfg.dragSort.handleClass.trim()
                : 'sc-v2-table-drag-handle';
              const handleSelector = '.' + handleClass.split(/\s+/).filter((item) => item !== '').join('.');

              const sortable = new Sortable(tbody, Object.assign(
                {
                  animation: 150,
                },
                options,
                {
                  handle: handleSelector,
                  onEnd: (event) => this.handleTableDragSort(resolvedKey, event),
                }
              ));

              ensureTableSortableStore(this)[resolvedKey] = sortable;

              return sortable;
            },
            syncTableDragSort(tableKey = null){
              const resolvedKey = this.resolveTableKey(tableKey);
              if (!resolvedKey) {
                return Promise.resolve(null);
              }

              if (typeof this.$nextTick === 'function') {
                return this.$nextTick().then(() => this.refreshTableDragSort(resolvedKey));
              }

              return Promise.resolve(this.refreshTableDragSort(resolvedKey));
            },
            initializeTableMaxHeight(tableKey = null){
              const resolvedKey = this.resolveTableKey(tableKey);
              const tableCfg = this.getTableConfig(resolvedKey);
              const state = this.getTableState(resolvedKey);
              if (!resolvedKey || !tableCfg || !state) {
                return null;
              }

              const configured = Number(tableCfg.maxHeight || 0);
              if (!configured) {
                state.maxHeight = 0;
                return state.maxHeight;
              }

              if (configured > 0) {
                state.maxHeight = configured;
                return state.maxHeight;
              }

              const ref = this.$refs?.[resolvedKey];
              const tableRef = Array.isArray(ref) ? ref[0] : ref;
              const tableEl = tableRef?.$el || tableRef;
              const top = typeof tableEl?.getBoundingClientRect === 'function'
                ? Number(tableEl.getBoundingClientRect().top || 0)
                : 0;
              const windowHeight = typeof window !== 'undefined'
                ? Number(window.innerHeight || 0)
                : 0;

              let nextHeight = windowHeight - top + configured;
              if (nextHeight < windowHeight / 2) {
                nextHeight = windowHeight;
              }

              state.maxHeight = Math.max(Math.round(nextHeight), 0);

              return state.maxHeight;
            },
            saveTableSettings(tableKey = null){
              const resolvedKey = this.resolveTableKey(tableKey);
              const state = this.getTableState(resolvedKey);
              if (!state) {
                return null;
              }

              destroyTableSettingsSortable(this, resolvedKey);
              destroyTableSettingsViewportObservers(this, resolvedKey);
              state.settings = normalizeTableSettingsState(
                state.settingsDraft || {},
                state.settingsDefault || {}
              );
              state.settingsDraft = clone(state.settings);
              state.settingsVisible = false;
              refreshTableSettingsCacheState(state);
              this.persistTableSettings(resolvedKey);

              if (typeof this.$nextTick === 'function') {
                return this.$nextTick().then(() => {
                  this.refreshTableLayout(resolvedKey);
                  return this.syncTableDragSort(resolvedKey).then(() => state.settings);
                });
              }

              this.refreshTableLayout(resolvedKey);
              return Promise.resolve(this.refreshTableDragSort(resolvedKey)).then(() => state.settings);
            },
            handleTableDragSort(tableKey = null, event = null){
              const resolvedKey = this.resolveTableKey(tableKey);
              const tableCfg = this.getTableConfig(resolvedKey);
              const state = this.getTableState(resolvedKey);
              if (!resolvedKey || !tableCfg || !state || tableCfg?.dragSort?.enabled !== true) {
                return Promise.resolve(null);
              }

              const oldIndex = Number(event?.oldIndex ?? -1);
              const newIndex = Number(event?.newIndex ?? -1);
              if (oldIndex < 0 || newIndex < 0 || oldIndex === newIndex) {
                return this.syncTableDragSort(resolvedKey);
              }

              const flatBefore = flattenTableRows(state.rows || [], tableCfg);
              const movedRowBefore = flatBefore[oldIndex] || null;
              const movedKey = normalizeTableRowKey(
                event?.item?.getAttribute?.('data-row-key')
                || event?.item?.dataset?.rowKey
                || getTableRowKeyValue(movedRowBefore, tableCfg)
              );
              const anchorRowBefore = flatBefore[newIndex] || null;
              const anchorKeyBefore = getTableRowKeyValue(anchorRowBefore, tableCfg);
              const isMoveDown = oldIndex < newIndex;

              if (movedKey === '') {
                return this.syncTableDragSort(resolvedKey);
              }

              let moveMeta = {
                movedRow: movedRowBefore,
                oldParentRow: null,
                newParentRow: null,
                anchorRow: anchorRowBefore,
                sameParent: true,
              };

              if (tableCfg?.tree?.enabled === true) {
                const treeMove = moveTreeTableRow(state.rows, tableCfg, movedKey, anchorKeyBefore, isMoveDown);
                if (!treeMove) {
                  return this.syncTableDragSort(resolvedKey);
                }

                if (typeof treeMove.error === 'string' && treeMove.error !== '') {
                  ElementPlus.ElMessage.warning(treeMove.error);
                  return this.loadTableData(resolvedKey);
                }

                moveMeta = Object.assign(moveMeta, treeMove);
              } else {
                const rows = Array.isArray(state.rows) ? state.rows : [];
                if (oldIndex >= rows.length || newIndex >= rows.length) {
                  return this.syncTableDragSort(resolvedKey);
                }

                const moved = rows.splice(oldIndex, 1)[0] || movedRowBefore;
                rows.splice(newIndex, 0, moved);
                moveMeta.movedRow = moved;
              }

              const flatAfter = flattenTableRows(state.rows || [], tableCfg);
              const effectiveIndex = flatAfter.findIndex((row) => getTableRowKeyValue(row, tableCfg) === movedKey);
              const previousRow = effectiveIndex > 0 ? (flatAfter[effectiveIndex - 1] || null) : null;
              const nextRow = effectiveIndex >= 0 ? (flatAfter[effectiveIndex + 1] || null) : null;
              const anchorRow = isMoveDown ? previousRow : nextRow;

              state.rows = clone(state.rows || []);
              state.allRows = clone(state.rows || []);
              if (tableCfg?.pagination?.enabled === false) {
                state.total = flatAfter.length;
              }
              state.selection = normalizeActiveTableSelection(state, tableCfg);

              if (tableCfg?.dataSource?.type !== 'remote' && tableCfg?.pagination?.enabled === false) {
                tableCfg.initialRows = clone(state.rows || []);
              }

              syncGlobalTableSelection(this, resolvedKey);

              return emitTableEvent(this, resolvedKey, tableCfg, state, 'dragSort', {
                event,
                row: moveMeta.movedRow || null,
                movedRow: moveMeta.movedRow || null,
                anchorRow,
                previousRow,
                nextRow,
                visibleRows: flatAfter,
                flatRows: flatAfter,
                oldIndex,
                newIndex: effectiveIndex >= 0 ? effectiveIndex : newIndex,
                isUp: isMoveDown,
                isDown: isMoveDown,
                isMoveDown,
                isMoveUp: !isMoveDown,
                oldParentRow: moveMeta.oldParentRow || null,
                newParentRow: moveMeta.newParentRow || null,
                movedParentRow: moveMeta.oldParentRow || null,
                anchorParentRow: moveMeta.newParentRow || null,
                sameParent: moveMeta.sameParent !== false,
              }).then(() => this.syncTableDragSort(resolvedKey));
            },
            initializeTables(tableKeys = null){
              const keys = Array.isArray(tableKeys) && tableKeys.length > 0
                ? tableKeys
                : Object.keys(this.ensureTableConfigStore());

              return Promise.all(keys.map((tableKey) => {
                const tableCfg = this.getTableConfig(tableKey);
                if (!tableCfg) {
                  return null;
                }

                this.getTableState(tableKey);
                this.ensureTableSettingsLoaded(tableKey);
                syncGlobalTableSelection(this, tableKey);

                const initializeHeight = tableCfg.maxHeight
                  ? (typeof this.$nextTick === 'function'
                    ? this.$nextTick(() => this.initializeTableMaxHeight(tableKey))
                    : Promise.resolve(this.initializeTableMaxHeight(tableKey)))
                  : Promise.resolve(null);

                return Promise.resolve(initializeHeight).then(() => this.loadTableData(tableKey));
              }));
            },
            loadTableData(tableKey = null){
              const resolvedKey = this.resolveTableKey(tableKey);
              const tableCfg = this.getTableConfig(resolvedKey);
              const state = this.getTableState(resolvedKey);
              if (!resolvedKey || !tableCfg || !state) {
                return Promise.resolve([]);
              }

              return emitTableEvent(this, resolvedKey, tableCfg, state, 'loadBefore')
                .then((results) => {
                  if (isEventCanceled(results)) {
                    return [];
                  }

                  if (tableCfg.dataSource?.type !== 'remote' || !tableCfg.dataSource?.url) {
                    const rows = this.applyClientTableState(resolvedKey);

                    return emitTableEvent(this, resolvedKey, tableCfg, state, 'loadSuccess', {
                      rows,
                      payload: rows,
                    }).then(() => this.syncTableDragSort(resolvedKey).then(() => rows));
                  }

                  state.loading = true;
                  const request = buildRemoteTableRequest(this, resolvedKey, tableCfg, state);

                  return makeRequest(request)
                    .then((response) => {
                      const payload = ensureSuccess(extractPayload(response), '数据加载失败');
                      const rows = pickRows(payload);
                      state.rows = rows;
                      state.allRows = clone(rows);
                      state.total = resolveTotal(payload) ?? rows.length;
                      state.selection = normalizeActiveTableSelection(state, tableCfg);
                      syncGlobalTableSelection(this, resolvedKey);

                      return emitTableEvent(this, resolvedKey, tableCfg, state, 'loadSuccess', {
                        response,
                        payload,
                        rows,
                      }).then(() => this.syncTableDragSort(resolvedKey).then(() => rows));
                    })
                    .catch((error) => {
                      const message = error?.message || resolveMessage(error?.response?.data, '数据加载失败');
                      ElementPlus.ElMessage.error(message);

                      return emitTableEvent(this, resolvedKey, tableCfg, state, 'loadFail', {
                        error,
                      }).then(() => []);
                    })
                    .finally(() => {
                      state.loading = false;
                    });
                });
            },
            exportTableData(tableKey = null){
              const resolvedKey = this.resolveTableKey(tableKey);
              const tableCfg = this.getTableConfig(resolvedKey);
              const state = this.getTableState(resolvedKey);
              if (!resolvedKey || !tableCfg || !state || tableCfg?.export?.enabled !== true) {
                return Promise.resolve(null);
              }

              const xlsx = globalThis.XLSX || (typeof window !== 'undefined' ? window?.XLSX : null);
              if (!xlsx?.utils || typeof xlsx.writeFile !== 'function') {
                ElementPlus.ElMessage.error('导出组件未加载');
                return Promise.resolve(null);
              }

              const exportColumns = resolveTableExportColumns(this, resolvedKey, tableCfg);
              if (exportColumns.length <= 0) {
                ElementPlus.ElMessage.error('当前表格没有可导出的列');
                return Promise.resolve(null);
              }

              const selection = Array.isArray(state.selection) ? state.selection.filter(Boolean) : [];
              let loadingInstance = null;
              state.exporting = true;

              try {
                loadingInstance = ElementPlus.ElLoading.service({
                  lock: true,
                  text: '正在导出...',
                  background: 'rgba(255,255,255,0.35)',
                });
              } catch (error) {
              }

              const rowsPromise = selection.length > 0
                ? Promise.resolve(clone(selection))
                : (tableCfg.dataSource?.type === 'remote' && tableCfg.dataSource?.url
                  ? makeRequest(buildRemoteTableRequest(this, resolvedKey, tableCfg, state, {
                      includePagination: false,
                      extraQuery: tableCfg.export?.query || {},
                    }))
                    .then((response) => {
                      const payload = ensureSuccess(extractPayload(response), '导出数据加载失败');
                      return pickRows(payload);
                    })
                  : Promise.resolve(clone(state.allRows || [])));

              return rowsPromise
                .then((rows) => {
                  const exportRows = Array.isArray(rows) ? rows : [];
                  if (exportRows.length <= 0) {
                    ElementPlus.ElMessage.warning('暂无可导出的数据');
                    return null;
                  }

                  const sheetData = [
                    exportColumns.map((column) => column.label || column.key),
                    ...exportRows.map((row) => exportColumns.map((column) => resolveExportCellValue(row, column)))
                  ];
                  const worksheet = xlsx.utils.aoa_to_sheet(sheetData);
                  const workbook = xlsx.utils.book_new();

                  xlsx.utils.book_append_sheet(workbook, worksheet, 'Sheet1');
                  xlsx.writeFile(
                    workbook,
                    sanitizeExportFilename(tableCfg.export?.filename || resolvedKey),
                    { compression: true }
                  );

                  ElementPlus.ElMessage.success(selection.length > 0 ? '已导出当前选中数据' : '导出成功');

                  return exportRows;
                })
                .catch((error) => {
                  const message = error?.message || resolveMessage(error?.response?.data, '导出失败');
                  ElementPlus.ElMessage.error(message);

                  return null;
                })
                .finally(() => {
                  state.exporting = false;
                  if (loadingInstance && typeof loadingInstance.close === 'function') {
                    loadingInstance.close();
                  }
                });
            },
            applyClientTableState(tableKey = null){
              const resolvedKey = this.resolveTableKey(tableKey);
              const tableCfg = this.getTableConfig(resolvedKey);
              const state = this.getTableState(resolvedKey);
              if (!resolvedKey || !tableCfg || !state) {
                return [];
              }

              let rows = clone(tableCfg.initialRows || []);
              rows = applyLocalSearch(
                rows,
                resolveTableSearchModel(this, resolvedKey, tableCfg, state),
                tableCfg.searchSchema || {},
                tableCfg
              );

              if (state.sort?.field && state.sort?.order) {
                rows.sort((left, right) => compareTableValues(
                  left?.[state.sort.field],
                  right?.[state.sort.field],
                  state.sort.order
                ));
              }

              state.allRows = clone(rows);
              state.total = rows.length;

              if (tableCfg.pagination?.enabled === false) {
                state.rows = rows;
                state.selection = normalizeActiveTableSelection(state, tableCfg);
                syncGlobalTableSelection(this, resolvedKey);
                return rows;
              }

              const start = (state.page - 1) * state.pageSize;
              state.rows = rows.slice(start, start + state.pageSize);
              state.selection = normalizeActiveTableSelection(state, tableCfg);
              syncGlobalTableSelection(this, resolvedKey);

              return state.rows;
            },
            reloadTable(tableKey = null){
              return this.loadTableData(tableKey);
            },
            handleTablePageChange(tableKey, page){
              const state = this.getTableState(tableKey);
              const tableCfg = this.getTableConfig(tableKey);
              if (!state) {
                return;
              }

              state.page = page;
              emitTableEvent(this, tableKey, tableCfg, state, 'pageChange', { page })
                .then(() => this.loadTableData(tableKey));
            },
            handleTablePageSizeChange(tableKey, pageSize){
              const state = this.getTableState(tableKey);
              const tableCfg = this.getTableConfig(tableKey);
              if (!state) {
                return;
              }

              state.pageSize = pageSize;
              state.page = 1;
              emitTableEvent(this, tableKey, tableCfg, state, 'pageSizeChange', { pageSize })
                .then(() => this.loadTableData(tableKey));
            },
            handleTableSortChange(tableKey, payload = {}){
              const state = this.getTableState(tableKey);
              const tableCfg = this.getTableConfig(tableKey);
              if (!state) {
                return;
              }

              state.sort = {
                field: payload?.prop || '',
                order: payload?.order || null
              };
              emitTableEvent(this, tableKey, tableCfg, state, 'sortChange', { sort: state.sort, payload })
                .then(() => this.loadTableData(tableKey));
            },
            handleTableSelectionChange(tableKey, selection){
              const state = this.getTableState(tableKey);
              const tableCfg = this.getTableConfig(tableKey);
              if (!state) {
                return;
              }

              state.selection = Array.isArray(selection) ? selection : [];
              syncGlobalTableSelection(this, tableKey);
              emitTableEvent(this, tableKey, tableCfg, state, 'selectionChange', {
                selection: state.selection
              });
            },
            handleTableSwitchChange(tableKey, row, prop, value, switchConfig = {}){
              const resolvedKey = this.resolveTableKey(tableKey);
              const tableCfg = this.getTableConfig(resolvedKey);
              const state = this.getTableState(resolvedKey);
              if (!resolvedKey || !row || typeof prop !== 'string' || prop === '') {
                return Promise.resolve(null);
              }

              const activeValue = switchConfig?.activeValue;
              const inactiveValue = switchConfig?.inactiveValue;
              const rollbackValue = value === activeValue ? inactiveValue : activeValue;
              const requestUrl = resolveContextValue(
                switchConfig?.requestUrl || '',
                buildTableEventContext(this, resolvedKey, tableCfg, state, {
                  row,
                  value,
                  prop,
                })
              );
              const id = getByPath(row, 'id');

              if (typeof requestUrl !== 'string' || requestUrl === '') {
                setByPath(row, prop, rollbackValue);
                ElementPlus.ElMessage.error('开关请求地址未配置');
                return Promise.resolve(null);
              }

              if (id === null || id === undefined || id === '') {
                setByPath(row, prop, rollbackValue);
                ElementPlus.ElMessage.error('当前行缺少主键，无法更新');
                return Promise.resolve(null);
              }

              return makeRequest({
                method: 'POST',
                url: requestUrl,
                query: {
                  id,
                  [prop]: getByPath(row, prop),
                }
              })
                .then((response) => ensureSuccess(extractPayload(response), '操作失败'))
                .catch((error) => {
                  setByPath(row, prop, rollbackValue);
                  ElementPlus.ElMessage.error(
                    error?.message || resolveMessage(error?.response?.data, '操作失败')
                  );

                  return null;
                });
            },
            deleteTableSelection(tableKey, confirmText = '确认删除当前选中数据？', actionConfig = null, actionContext = null){
              const resolvedKey = this.resolveTableKey(tableKey);
              const tableCfg = this.getTableConfig(resolvedKey);
              const state = this.getTableState(resolvedKey);
              const deleteActionCfg = isObject(actionConfig?.delete) ? actionConfig.delete : {};
              const deleteKey = typeof deleteActionCfg.deleteKey === 'string' && deleteActionCfg.deleteKey !== ''
                ? deleteActionCfg.deleteKey
                : tableCfg?.deleteKey;
              const selection = Array.isArray(state?.selection) ? state.selection : [];
              const ids = selection
                .map((item) => deleteKey ? item?.[deleteKey] : undefined)
                .filter((value) => value !== undefined && value !== null && value !== '');
              const deleteUrl = resolveContextValue(
                deleteActionCfg.deleteUrl || tableCfg?.deleteUrl || '',
                Object.assign(
                  {},
                  buildTableEventContext(this, resolvedKey, tableCfg, state, {
                    selection,
                    ids,
                    action: isObject(actionConfig) ? actionConfig : null,
                  }),
                  isObject(actionContext) ? actionContext : {}
                )
              );

              if (!resolvedKey || typeof deleteUrl !== 'string' || deleteUrl === '') {
                return Promise.resolve(null);
              }

              if (ids.length <= 0) {
                ElementPlus.ElMessage.error('请选择要删除的数据');
                return Promise.resolve(null);
              }

              const performDelete = () => {
                return Promise.resolve().then(() => {
                  const payload = { ids };

                  return axios.post(deleteUrl, payload);
                })
                .then((response) => {
                  const payload = ensureSuccess(extractPayload(response), '删除失败');
                  if ((tableCfg.pagination?.enabled !== false) && (state?.rows?.length || 0) <= 1 && (state?.page || 1) > 1) {
                    state.page -= 1;
                  }
                  ElementPlus.ElMessage.success(resolveMessage(payload, '删除成功'));

                  return emitTableEvent(this, resolvedKey, tableCfg, state, 'deleteSuccess', {
                    selection,
                    ids,
                    response,
                    payload
                  }).then(() => this.loadTableData(resolvedKey));
                })
                .catch((error) => {
                  if (error === 'cancel' || error === 'close') {
                    return null;
                  }

                  const message = error?.message || resolveMessage(error?.response?.data, '删除失败');
                  ElementPlus.ElMessage.error(message);

                  return emitTableEvent(this, resolvedKey, tableCfg, state, 'deleteFail', {
                    selection,
                    ids,
                    error
                  }).then(() => null);
                });
              };

              if (!confirmText) {
                return performDelete();
              }

              return ElementPlus.ElMessageBox.confirm(confirmText, '提示', {
                type: 'warning',
                lockScroll: false
              })
                .then(() => performDelete());
            },
            recoverTableSelection(tableKey = null, confirmText = '确认恢复当前选中数据？'){
              const resolvedKey = this.resolveTableKey(tableKey);
              const tableCfg = this.getTableConfig(resolvedKey);
              const state = this.getTableState(resolvedKey);
              const deleteKey = tableCfg?.deleteKey || 'id';
              const selection = Array.isArray(state?.selection) ? state.selection : [];
              const ids = selection
                .map((item) => deleteKey ? item?.[deleteKey] : undefined)
                .filter((value) => value !== undefined && value !== null && value !== '');
              const recoverUrl = resolveContextValue(
                tableCfg?.trash?.recoverUrl || '',
                buildTableEventContext(this, resolvedKey, tableCfg, state, {
                  selection,
                  ids,
                  trashMode: isTableTrashActive(this, resolvedKey, tableCfg),
                })
              );

              if (!resolvedKey || typeof recoverUrl !== 'string' || recoverUrl === '') {
                return Promise.resolve(null);
              }

              if (ids.length <= 0) {
                ElementPlus.ElMessage.error('请选择要恢复的数据');
                return Promise.resolve(null);
              }

              const performRecover = () => {
                return Promise.resolve()
                  .then(() => axios.post(recoverUrl, { ids }))
                  .then((response) => {
                    const payload = ensureSuccess(extractPayload(response), '恢复失败');
                    if ((tableCfg.pagination?.enabled !== false) && (state?.rows?.length || 0) <= 1 && (state?.page || 1) > 1) {
                      state.page -= 1;
                    }
                    ElementPlus.ElMessage.success(resolveMessage(payload, '恢复成功'));

                    return this.loadTableData(resolvedKey);
                  })
                  .catch((error) => {
                    if (error === 'cancel' || error === 'close') {
                      return null;
                    }

                    ElementPlus.ElMessage.error(
                      error?.message || resolveMessage(error?.response?.data, '恢复失败')
                    );

                    return null;
                  });
              };

              if (!confirmText) {
                return performRecover();
              }

              return ElementPlus.ElMessageBox.confirm(confirmText, '提示', {
                type: 'warning',
                lockScroll: false
              }).then(() => performRecover());
            }
          };
        };
