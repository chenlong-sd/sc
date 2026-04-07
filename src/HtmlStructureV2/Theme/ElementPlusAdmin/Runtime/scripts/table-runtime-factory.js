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
            getByPath,
            isEventCanceled,
            isObject,
            resolveContextValue,
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
          const buildTableEventContext = (vm, tableKey, tableCfg, state, overrides = {}) => {
            const filters = getSearchModel(vm, tableKey, tableCfg) || {};

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
          const normalizeTableSettingColumn = (column = {}, defaults = {}) => {
            const source = isObject(column) ? column : {};
            const fallback = isObject(defaults) ? defaults : {};
            const key = typeof fallback.key === 'string' && fallback.key !== ''
              ? fallback.key
              : (typeof source.key === 'string' ? source.key : '');

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
            };
          };
          const normalizeTableSettingsState = (settings = {}, defaults = {}) => {
            const source = isObject(settings) ? settings : {};
            const fallback = isObject(defaults) ? defaults : {};
            const fallbackColumns = Array.isArray(fallback.columns) ? fallback.columns : [];
            const persistedColumns = Array.isArray(source.columns) ? source.columns : [];
            const persistedMap = new Map(
              persistedColumns
                .filter((item) => isObject(item) && typeof item.key === 'string' && item.key !== '')
                .map((item) => [String(item.key), item])
            );

            return {
              enabled: (fallback.enabled === true),
              stripe: typeof source.stripe === 'boolean' ? source.stripe : (fallback.stripe !== false),
              border: typeof source.border === 'boolean' ? source.border : (fallback.border !== false),
              columns: fallbackColumns
                .map((item) => normalizeTableSettingColumn(persistedMap.get(String(item?.key || '')) || item, item))
                .filter((item) => item.key !== '')
            };
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

              return state.settings;
            },
            getTableSettings(tableKey = null){
              const state = this.getTableState(tableKey);
              if (!state) {
                return null;
              }

              return this.ensureTableSettingsLoaded(tableKey) || state.settings || null;
            },
            getTableColumnSetting(tableKey = null, columnKey = ''){
              const settings = this.getTableSettings(tableKey);
              if (typeof columnKey !== 'string' || columnKey === '' || !Array.isArray(settings?.columns)) {
                return null;
              }

              return settings.columns.find((item) => item?.key === columnKey) || null;
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
            openTableSettings(tableKey = null){
              const resolvedKey = this.resolveTableKey(tableKey);
              const state = this.getTableState(resolvedKey);
              const settings = this.ensureTableSettingsLoaded(resolvedKey);
              if (!state || settings?.enabled !== true) {
                return null;
              }

              state.settingsDraft = clone(settings);
              state.settingsVisible = true;

              return state.settingsDraft;
            },
            closeTableSettings(tableKey = null){
              const resolvedKey = this.resolveTableKey(tableKey);
              const state = this.getTableState(resolvedKey);
              if (!state) {
                return null;
              }

              state.settingsVisible = false;
              state.settingsDraft = clone(state.settings || state.settingsDefault || {});

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

              return state.settingsDraft;
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

              state.settings = normalizeTableSettingsState(
                state.settingsDraft || {},
                state.settingsDefault || {}
              );
              state.settingsDraft = clone(state.settings);
              state.settingsVisible = false;
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
                  const searchModel = getSearchModel(this, resolvedKey, tableCfg) || {};
                  const baseQuery = Object.assign({}, tableCfg.dataSource?.query || {});
                  const pageQuery = resolveTablePageQuery(tableCfg.dataSource || {});
                  if (!Object.prototype.hasOwnProperty.call(baseQuery, 'query') && pageQuery !== '') {
                    baseQuery.query = pageQuery;
                  }
                  const request = Object.assign({}, tableCfg.dataSource, {
                    query: Object.assign(
                      {},
                      baseQuery,
                      buildSearchQuery(searchModel, tableCfg.searchSchema || {}, resolvedKey, tableCfg),
                      tableCfg.pagination?.enabled === false ? {} : {
                        page: state.page,
                        pageSize: state.pageSize
                      },
                      state.sort?.field ? {
                        order: {
                          field: tableCfg.sortFieldMap?.[state.sort.field] || state.sort.field,
                          order: state.sort.order
                        }
                      } : {}
                    )
                  });
                  if (request.query?.query === '') {
                    delete request.query.query;
                  }

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
                getSearchModel(this, resolvedKey, tableCfg) || {},
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
            }
          };
        };
