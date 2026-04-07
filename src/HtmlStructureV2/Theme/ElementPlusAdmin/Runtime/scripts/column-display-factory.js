        globalThis.__SC_V2_CREATE_COLUMN_DISPLAY_METHODS__ = () => {
          const {
            buildUrlWithQuery,
            formatColumnDatetime,
            isColumnDisplayBlank,
            isColumnFalsy,
            isColumnTruthy,
            resolveTitleTemplate,
            resolveColumnDisplayValue,
            resolveColumnMappingLabel,
            resolveColumnTagMeta,
          } = globalThis.__SC_V2_RUNTIME_HELPERS__;

          const resolveMainTabsHost = () => {
            const candidates = [];
            if (typeof globalThis !== 'undefined') {
              candidates.push(globalThis);
            }
            if (typeof window !== 'undefined') {
              candidates.push(window);
              try {
                if (window.parent) {
                  candidates.push(window.parent);
                }
              } catch (error) {}
              try {
                if (window.top) {
                  candidates.push(window.top);
                }
              } catch (error) {}
            }

            const visited = new Set();
            for (const candidate of candidates) {
              if (!candidate || (typeof candidate !== 'object' && typeof candidate !== 'function') || visited.has(candidate)) {
                continue;
              }

              visited.add(candidate);
              try {
                const mainTabs = candidate.VueApp?.$refs?.['main-tabs'];
                if (mainTabs && typeof mainTabs.add === 'function') {
                  return mainTabs;
                }
              } catch (error) {}
            }

            return null;
          };

          const buildTabIndex = (url) => {
            const source = String(url || '');
            let hash = 0;
            for (let index = 0; index < source.length; index += 1) {
              hash = ((hash << 5) - hash) + source.charCodeAt(index);
              hash |= 0;
            }

            return `sc-tab-${Math.abs(hash)}`;
          };

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
              const mainTabs = resolveMainTabsHost();
              if (mainTabs && typeof mainTabs.add === 'function') {
                mainTabs.add({
                  index: buildTabIndex(resolvedUrl),
                  title: resolvedTitle,
                  route: resolvedUrl
                });

                return resolvedUrl;
              }
            }

            const targetWindow = typeof window !== 'undefined'
              ? window
              : globalThis;
            if (!targetWindow || typeof targetWindow.open !== 'function') {
              return resolvedUrl;
            }

            if (String(openType || 'dialog') === 'tab') {
              targetWindow.open(resolvedUrl, '_blank');
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
