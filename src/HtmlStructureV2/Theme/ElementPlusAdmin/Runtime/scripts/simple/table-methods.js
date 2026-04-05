        globalThis.__SC_V2_CREATE_SIMPLE_TABLE_METHODS__ = ({
          cfg,
          clone,
          ensureSuccess,
          extractPayload,
          makeRequest,
          pickRows,
          resolveMessage
        }) => {
          const createTableRuntimeMethods = globalThis.__SC_V2_CREATE_TABLE_RUNTIME_METHODS__;

          return createTableRuntimeMethods({
            cfg,
            clone,
            ensureSuccess,
            extractPayload,
            makeRequest,
            pickRows,
            resolveMessage
          });
        };
