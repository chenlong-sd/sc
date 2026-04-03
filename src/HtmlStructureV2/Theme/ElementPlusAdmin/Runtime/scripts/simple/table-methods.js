        globalThis.__SC_V2_CREATE_SIMPLE_TABLE_METHODS__ = () => ({
          handleSelectionChange(selection){
            this.tableSelection = Array.isArray(selection) ? selection : [];
          },
          handleSortChange(){
          }
        });
