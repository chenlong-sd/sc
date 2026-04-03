        globalThis.__SC_V2_CREATE_LIST_TABLE_METHODS__ = ({
          applyLocalSearch,
          buildSearchQuery,
          clone,
          compareValues,
          cfg,
          ensureSuccess,
          extractPayload,
          makeRequest,
          pickRows,
          pickTotal,
          resolveMessage
        }) => ({
          loadTableData(){
            if (!cfg.list || !cfg.list.url) {
              this.applyClientTableState();
              return;
            }
            if (cfg.list.type !== 'remote') {
              this.applyClientTableState();
              return;
            }

            this.tableLoading = true;
            const request = Object.assign({}, cfg.list, {
              query: Object.assign(
                {},
                cfg.list.query || {},
                buildSearchQuery(this.filterModel, cfg.searchSchema),
                cfg.pagination?.enabled === false ? {} : {
                  page: this.tablePage,
                  pageSize: this.tablePageSize
                },
                this.tableSort.field ? {
                  order: {
                    field: cfg.sortFieldMap?.[this.tableSort.field] || this.tableSort.field,
                    order: this.tableSort.order
                  }
                } : {}
              )
            });

            makeRequest(request)
              .then((response) => {
                const payload = ensureSuccess(extractPayload(response), '数据加载失败');
                this.tableRows = pickRows(payload);
                this.tableAllRows = clone(this.tableRows);
                this.tableTotal = pickTotal(payload) ?? this.tableRows.length;
              })
              .catch((error) => {
                const message = error?.message || resolveMessage(error?.response?.data, '数据加载失败');
                ElementPlus.ElMessage.error(message);
              })
              .finally(() => {
                this.tableLoading = false;
              });
          },
          applyClientTableState(){
            let rows = clone(cfg.initialRows);
            rows = applyLocalSearch(rows, this.filterModel, cfg.searchSchema);
            if (this.tableSort.field && this.tableSort.order) {
              rows.sort((left, right) => compareValues(left[this.tableSort.field], right[this.tableSort.field], this.tableSort.order));
            }
            this.tableAllRows = clone(rows);
            this.tableTotal = rows.length;
            if (cfg.pagination?.enabled === false) {
              this.tableRows = rows;
              return;
            }
            const start = (this.tablePage - 1) * this.tablePageSize;
            this.tableRows = rows.slice(start, start + this.tablePageSize);
          },
          handlePageChange(page){
            this.tablePage = page;
            this.loadTableData();
          },
          handlePageSizeChange(pageSize){
            this.tablePageSize = pageSize;
            this.tablePage = 1;
            this.loadTableData();
          },
          handleSortChange({ prop, order }){
            this.tableSort = {
              field: prop || '',
              order: order || null
            };
            this.loadTableData();
          },
          handleSelectionChange(selection){
            this.tableSelection = Array.isArray(selection) ? selection : [];
          }
        });
