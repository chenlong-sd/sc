        globalThis.__SC_V2_CREATE_LIST_FILTER_METHODS__ = ({ clone }) => ({
          submitFilters(){
            this.validateForm('filter').then((valid) => {
              if (!valid) return;
              this.tablePage = 1;
              this.loadTableData();
            });
          },
          resetFilters(){
            this.withDependencyResetSuspended('filter', () => {
              this.filterModel = clone(this.filterInitial);
              this.tablePage = 1;
              this.clearFormValidate('filter');
              this.initializeFormOptions('filter', true);
              this.initializeUploadFiles('filter');
            });
            this.loadTableData();
          }
        });
