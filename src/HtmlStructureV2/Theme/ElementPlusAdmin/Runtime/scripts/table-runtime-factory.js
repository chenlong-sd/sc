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

              return states[resolvedKey] || null;
            },
            getTableRows(tableKey = null){
              return this.getTableState(tableKey)?.rows || [];
            },
            getTableSelection(tableKey = null){
              return this.getTableState(tableKey)?.selection || [];
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
                return rows;
              }

              const start = (state.page - 1) * state.pageSize;
              state.rows = rows.slice(start, start + state.pageSize);

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
              emitTableEvent(this, tableKey, tableCfg, state, 'selectionChange', {
                selection: state.selection
              });
            },
            deleteTableRow(tableKey, row, confirmText = '确认删除当前记录？'){
              const resolvedKey = this.resolveTableKey(tableKey);
              const tableCfg = this.getTableConfig(resolvedKey);
              const state = this.getTableState(resolvedKey);

              if (!resolvedKey || !tableCfg?.deleteUrl || !row) {
                return Promise.resolve(null);
              }

              const performDelete = () => {
                return Promise.resolve().then(() => {
                  const payload = (tableCfg.deleteKey && row[tableCfg.deleteKey] !== undefined)
                    ? { [tableCfg.deleteKey]: row[tableCfg.deleteKey] }
                    : row;

                  return axios.post(tableCfg.deleteUrl, payload);
                })
                .then((response) => {
                  const payload = ensureSuccess(extractPayload(response), '删除失败');
                  if ((tableCfg.pagination?.enabled !== false) && (state?.rows?.length || 0) <= 1 && (state?.page || 1) > 1) {
                    state.page -= 1;
                  }
                  ElementPlus.ElMessage.success(resolveMessage(payload, '删除成功'));

                  return emitTableEvent(this, resolvedKey, tableCfg, state, 'deleteSuccess', {
                    row,
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
                    row,
                    error
                  }).then(() => null);
                });
              };

              if (!confirmText) {
                return performDelete();
              }

              return ElementPlus.ElMessageBox.confirm(confirmText, '提示', { type: 'warning' })
                .then(() => performDelete());
            }
          };
        };
