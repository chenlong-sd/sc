        globalThis.__SC_V2_CREATE_COLUMN_DISPLAY_METHODS__ = () => {
          const {
            buildUrlWithQuery,
            formatColumnDatetime,
            isColumnDisplayBlank,
            isColumnFalsy,
            isColumnTruthy,
            openHostTab,
            resolveTitleTemplate,
            resolveColumnDisplayValue,
            resolveColumnMappingLabel,
            resolveColumnTagMeta,
          } = globalThis.__SC_V2_RUNTIME_HELPERS__;

          const openColumnPage = (
            scope,
            url,
            query = {},
            features = '',
            openType = 'dialog',
            titleTemplate = '查看详情',
            event = null
          ) => {
            if (event?.preventDefault) {
              event.preventDefault();
            }

            const row = scope?.row && typeof scope.row === 'object' ? scope.row : null;
            const context = Object.assign({}, row || {}, { row, scope });
            const resolvedUrl = buildUrlWithQuery(url || '', query || {}, context);
            if (!resolvedUrl) {
              return '';
            }

            const resolvedTitle = resolveTitleTemplate(titleTemplate || '查看详情', context) || '查看详情';

            if (String(openType || 'dialog') === 'tab') {
              openHostTab({
                title: resolvedTitle,
                route: resolvedUrl
              });
              return resolvedUrl;
            }

            const targetWindow = typeof window !== 'undefined'
              ? window
              : globalThis;
            if (!targetWindow || typeof targetWindow.open !== 'function') {
              return resolvedUrl;
            }

            targetWindow.open(
              resolvedUrl,
              '_blank',
              typeof features === 'string' && features !== '' ? features : undefined
            );

            return resolvedUrl;
          };

          return {
            formatColumnDatetime,
            isColumnDisplayBlank,
            isColumnFalsy,
            isColumnTruthy,
            openColumnPage,
            resolveColumnDisplayValue,
            resolveColumnMappingLabel,
            resolveColumnTagMeta,
          };
        };
