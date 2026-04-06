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
            isEventCanceled,
            isObject,
            resolveContextValue,
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
                  return state.settings;
                });
              }

              this.refreshTableLayout(resolvedKey);

              return state.settings;
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

                return this.loadTableData(tableKey);
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
                    }).then(() => rows);
                  }

                  state.loading = true;
                  const searchModel = getSearchModel(this, resolvedKey, tableCfg) || {};
                  const request = Object.assign({}, tableCfg.dataSource, {
                    query: Object.assign(
                      {},
                      tableCfg.dataSource?.query || {},
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
                      }).then(() => rows);
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
