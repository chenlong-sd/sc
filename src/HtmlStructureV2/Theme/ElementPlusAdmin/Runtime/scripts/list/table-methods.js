        globalThis.__SC_V2_CREATE_LIST_TABLE_METHODS__ = ({
          applyLocalSearch,
          buildSearchQuery,
          clone,
          compareValues,
          cfg,
          ensureSuccess,
          extractPayload,
          getSearchModel = () => ({}),
          makeRequest,
          pickRows,
          pickTotal,
          resolveMessage
        }) => {
          const createTableRuntimeMethods = globalThis.__SC_V2_CREATE_TABLE_RUNTIME_METHODS__;

          return createTableRuntimeMethods({
            cfg,
            applyLocalSearch,
            buildSearchQuery,
            clone,
            compareValues,
            ensureSuccess,
            extractPayload,
            getSearchModel,
            makeRequest,
            pickRows,
            pickTotal,
            resolveMessage
          });
        };
