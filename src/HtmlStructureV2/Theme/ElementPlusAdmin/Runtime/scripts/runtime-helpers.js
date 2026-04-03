        globalThis.__SC_V2_RUNTIME_HELPERS__ = globalThis.__SC_V2_RUNTIME_HELPERS__ || (() => {
          const isObject = (value) => value && typeof value === 'object' && !Array.isArray(value);
          const clone = (value) => {
            if (Array.isArray(value)) {
              return value.map((item) => clone(item));
            }
            if (value instanceof RegExp) {
              return new RegExp(value.source, value.flags);
            }
            if (isObject(value)) {
              const output = {};
              Object.keys(value).forEach((key) => {
                output[key] = clone(value[key]);
              });
              return output;
            }
            return value;
          };
          const isBlank = (value) => value === '' || value === null || value === undefined || (Array.isArray(value) && value.length === 0);
          const isRowArray = (value) => {
            if (!Array.isArray(value)) return false;
            if (value.length === 0) return true;
            return typeof value[0] === 'object' || Array.isArray(value[0]);
          };
          const getByPath = (source, path) => {
            if (!path) return undefined;
            return String(path)
              .split('.')
              .reduce((current, segment) => current == null ? undefined : current[segment], source);
          };
          const setByPath = (source, path, value) => {
            if (!isObject(source) || !path) return;

            const segments = String(path).split('.').filter(Boolean);
            if (segments.length === 0) return;

            let current = source;
            segments.slice(0, -1).forEach((segment) => {
              if (!isObject(current[segment])) {
                current[segment] = {};
              }
              current = current[segment];
            });

            current[segments[segments.length - 1]] = value;
          };
          const extractPayload = (response) => {
            if (response && typeof response === 'object' && Object.prototype.hasOwnProperty.call(response, 'data')) {
              return response.data;
            }
            return response;
          };
          const resolveMessage = (payload, fallback = '') => {
            if (typeof payload === 'string' && payload !== '') return payload;
            if (!isObject(payload)) return fallback;
            return payload.message || payload.msg || payload.error || fallback;
          };
          const isSuccessPayload = (payload) => {
            if (!isObject(payload)) return true;
            if (typeof payload.success === 'boolean') return payload.success;
            if (payload.code !== undefined) return [0, 200, '0', '200'].includes(payload.code);
            if (payload.status !== undefined) {
              if (typeof payload.status === 'number') {
                return payload.status >= 200 && payload.status < 300;
              }
              return ['success', 'ok'].includes(String(payload.status).toLowerCase());
            }
            return true;
          };
          const ensureSuccess = (payload, fallback) => {
            if (isSuccessPayload(payload)) {
              return payload;
            }
            throw new Error(resolveMessage(payload, fallback));
          };
          const callHook = (hook, context) => {
            if (typeof hook !== 'function') {
              return Promise.resolve(undefined);
            }

            try {
              return Promise.resolve(hook(context));
            } catch (error) {
              return Promise.reject(error);
            }
          };
          const pickRows = (payload, depth = 0) => {
            if (depth > 4) return [];
            if (isRowArray(payload)) return payload;
            if (!isObject(payload)) return [];

            const directKeys = ['data', 'rows', 'list', 'items', 'records'];
            for (const key of directKeys) {
              if (isRowArray(payload[key])) return payload[key];
            }

            const nestedKeys = ['data', 'result', 'payload'];
            for (const key of nestedKeys) {
              if (payload[key] !== undefined) {
                const found = pickRows(payload[key], depth + 1);
                if (found.length > 0 || isRowArray(payload[key])) return found;
              }
            }

            return [];
          };
          const makeRequest = (request) => {
            const method = (request?.method || 'GET').toLowerCase();
            if (method === 'get') {
              return axios.get(request.url, { params: request.query || {} });
            }
            return axios({ method, url: request.url, data: request.query || {} });
          };
          const normalizeOption = (item, fieldCfg, index) => {
            if (!isObject(item)) {
              return {
                value: item,
                label: item == null ? '' : String(item),
                disabled: false
              };
            }

            const value = item.value !== undefined
              ? item.value
              : getByPath(item, fieldCfg?.valueField || 'value');
            const label = item.label !== undefined
              ? item.label
              : getByPath(item, fieldCfg?.labelField || 'label');

            return Object.assign({}, item, {
              value: value ?? index,
              label: label ?? String(value ?? ''),
              disabled: item.disabled === true
            });
          };
          const normalizeDependencies = (fieldCfg) => {
            return Array.from(new Set(
              Array.isArray(fieldCfg?.dependencies)
                ? fieldCfg.dependencies.filter((item) => typeof item === 'string' && item !== '')
                : []
            ));
          };
          const resolveDynamicParams = (params, model) => {
            const query = {};

            Object.keys(params || {}).forEach((key) => {
              const value = params[key];
              if (typeof value === 'string' && value.startsWith('@')) {
                const resolved = getByPath(model, value.slice(1));
                if (!isBlank(resolved)) {
                  query[key] = resolved;
                }
                return;
              }

              if (value !== undefined) {
                query[key] = value;
              }
            });

            return query;
          };
          const resolveContextToken = (token, context) => {
            const path = String(token || '').replace(/^@/, '');
            if (path === '') {
              return undefined;
            }

            return getByPath(context, path);
          };
          const resolveContextValue = (value, context) => {
            if (typeof value === 'function') {
              return resolveContextValue(value(context), context);
            }

            if (Array.isArray(value)) {
              return value.map((item) => resolveContextValue(item, context));
            }

            if (isObject(value)) {
              const output = {};
              Object.keys(value).forEach((key) => {
                output[key] = resolveContextValue(value[key], context);
              });
              return output;
            }

            if (typeof value === 'string') {
              if (/^@[\w.]+$/.test(value)) {
                return resolveContextToken(value, context);
              }

              if (!value.includes('@')) {
                return value;
              }

              return value.replace(/@[\w.]+/g, (token) => {
                const resolved = resolveContextToken(token, context);
                return resolved === null || resolved === undefined ? '' : String(resolved);
              });
            }

            return value;
          };
          const resolveTitleTemplate = (template, context) => {
            if (template === null || template === undefined) {
              return '';
            }

            const replaced = String(template).replace(/\{([^{}]+)\}/g, (_, path) => {
              const resolved = getByPath(context?.row || {}, String(path).trim());
              return resolved === null || resolved === undefined ? '' : String(resolved);
            });

            const value = resolveContextValue(replaced, context);
            return value === null || value === undefined ? '' : String(value);
          };
          const buildUrlWithQuery = (url, query, context) => {
            const resolvedUrl = resolveContextValue(url, context);
            if (typeof resolvedUrl !== 'string' || resolvedUrl === '') {
              return '';
            }

            const parsedUrl = new URL(resolvedUrl, window.location.href);
            const resolvedQuery = resolveContextValue(query || {}, context);

            Object.keys(resolvedQuery || {}).forEach((key) => {
              const value = resolvedQuery[key];
              if (value === null || value === undefined || value === '') {
                return;
              }

              if (Array.isArray(value)) {
                parsedUrl.searchParams.delete(key);
                value.forEach((item) => {
                  if (item !== null && item !== undefined && item !== '') {
                    parsedUrl.searchParams.append(key, String(item));
                  }
                });
                return;
              }

              parsedUrl.searchParams.set(key, String(value));
            });

            return parsedUrl.toString();
          };
          const hasReadyDependencies = (fieldCfg, model) => {
            const dependencies = normalizeDependencies(fieldCfg);
            if (dependencies.length === 0) {
              return true;
            }

            return dependencies.every((path) => !isBlank(getByPath(model, path)));
          };
          const isSameValue = (left, right) => {
            if (left === right) return true;
            if (left === null || left === undefined || right === null || right === undefined) {
              return false;
            }

            return String(left) === String(right);
          };
          const resolveLinkageToken = (token, context) => {
            const path = String(token || '').replace(/^@/, '');
            if (path === '') return '';
            if (path === 'value') return context.value;
            if (path === 'label') return context.option?.label ?? '';
            if (path.startsWith('model.')) return getByPath(context.model, path.slice(6));
            if (path.startsWith('option.')) return getByPath(context.option, path.slice(7));

            return getByPath(context.option, path);
          };
          const resolveLinkageTemplate = (template, context) => {
            if (typeof template === 'function') {
              return template(context);
            }
            if (template === null || template === undefined) {
              return '';
            }
            if (typeof template !== 'string') {
              return template;
            }
            if (/^@[\w.]+$/.test(template)) {
              return resolveLinkageToken(template, context);
            }

            return template.replace(/@[\w.]+/g, (token) => {
              const value = resolveLinkageToken(token, context);
              return value === null || value === undefined ? '' : String(value);
            });
          };
          const extractFileName = (url, fallback = 'file') => {
            if (typeof url !== 'string' || url === '') {
              return fallback;
            }

            const clean = url.split('?')[0].split('#')[0];
            const parts = clean.split('/').filter(Boolean);

            return parts[parts.length - 1] || fallback;
          };
          const resolveUploadValue = (payload, fieldCfg, depth = 0) => {
            if (depth > 4 || payload === null || payload === undefined) {
              return null;
            }
            if (typeof payload === 'string') {
              return payload;
            }
            if (!isObject(payload)) {
              return null;
            }

            if (fieldCfg?.responsePath) {
              const pathValue = getByPath(payload, fieldCfg.responsePath);
              if (!isBlank(pathValue)) {
                return pathValue;
              }
            }

            const directKeys = ['url', 'path', 'value', 'src'];
            for (const key of directKeys) {
              if (typeof payload[key] === 'string' && payload[key] !== '') {
                return payload[key];
              }
            }

            const nestedKeys = ['data', 'result', 'payload'];
            for (const key of nestedKeys) {
              if (payload[key] !== undefined) {
                const resolved = resolveUploadValue(payload[key], fieldCfg, depth + 1);
                if (!isBlank(resolved)) {
                  return resolved;
                }
              }
            }

            return null;
          };
          const normalizeUploadFile = (item, fieldCfg, index) => {
            if (typeof item === 'string') {
              return {
                uid: 'init-' + index,
                name: extractFileName(item, 'file-' + (index + 1)),
                url: item,
                responseValue: item,
                status: 'success'
              };
            }
            if (!isObject(item)) {
              return null;
            }

            const responseValue = item.responseValue
              ?? resolveUploadValue(item.response, fieldCfg)
              ?? resolveUploadValue(item, fieldCfg);
            const url = item.url || item.value || item.src || responseValue;
            if (isBlank(url)) {
              return null;
            }

            return Object.assign({}, item, {
              uid: item.uid || ('file-' + index),
              name: item.name || extractFileName(String(url), 'file-' + (index + 1)),
              url,
              responseValue: responseValue || url,
              status: item.status || 'success'
            });
          };
          const normalizeUploadFiles = (value, fieldCfg) => {
            const source = Array.isArray(value)
              ? value
              : (isBlank(value) ? [] : [value]);
            const files = source
              .map((item, index) => normalizeUploadFile(item, fieldCfg, index))
              .filter(Boolean);

            return fieldCfg?.multiple ? files : files.slice(0, 1);
          };
          const syncUploadModelValue = (model, fieldName, fieldCfg, files) => {
            const normalized = normalizeUploadFiles(files, fieldCfg);
            const values = normalized
              .map((file) => file.responseValue || file.url)
              .filter((value) => !isBlank(value));

            setByPath(model, fieldName, fieldCfg?.multiple ? values : (values[0] ?? ''));

            return normalized;
          };

          return {
            buildUrlWithQuery,
            callHook,
            clone,
            ensureSuccess,
            extractFileName,
            extractPayload,
            getByPath,
            hasReadyDependencies,
            isBlank,
            isObject,
            isRowArray,
            isSameValue,
            makeRequest,
            normalizeDependencies,
            normalizeOption,
            normalizeUploadFile,
            normalizeUploadFiles,
            pickRows,
            resolveContextToken,
            resolveContextValue,
            resolveDynamicParams,
            resolveLinkageTemplate,
            resolveMessage,
            resolveTitleTemplate,
            resolveUploadValue,
            setByPath,
            syncUploadModelValue
          };
        })();
