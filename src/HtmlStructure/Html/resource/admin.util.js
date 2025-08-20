/**
 * 后台工具类
 */
const AdminUtil = (function (){
    function base64Decode(str) {
        const binary = atob(str);
        const bytes = new Uint8Array(binary.length);
        for (let i = 0; i < binary.length; i++) {
            bytes[i] = binary.charCodeAt(i);
        }
        return new TextDecoder().decode(bytes);
    }

    function base64Encode(str) {
        const bytes = new TextEncoder().encode(str);
        let binary = '';
        for (let i = 0; i < bytes.length; i++) {
            binary += String.fromCharCode(bytes[i]);
        }
        return btoa(binary);
    }

    /**
     * 构建查询参数
     * @param obj
     * @param parentPrefix
     * @returns {string}
     */
    function buildQuery(obj, parentPrefix = null) {
        let parts = [];
        for (let key in obj) {
            if (obj.hasOwnProperty(key)) {
                let propName = parentPrefix ? `${parentPrefix}[${encodeURIComponent(key)}]` : encodeURIComponent(key);
                let value = obj[key];

                if (!value) continue;

                if (typeof value === 'object' && !(value instanceof Date) && !(value instanceof File)) {
                    parts.push(buildQuery(value, propName));
                } else {
                    parts.push(propName + '=' + encodeURIComponent(value));
                }
            }
        }
        return parts.join('&');
    }

    /**
     * url注入搜索参数
     * @param url
     * @param tableId
     * @param VueApp
     * @returns {string}
     */
    function urlInjectSearch(url, tableId, VueApp)  {
        let urlObj = new URL(url);
        let search = buildQuery({
            search: {
                search: VueApp[`${tableId}Search`],
                searchType: VueApp[`${tableId}SearchType`],
                searchField: VueApp[`${tableId}SearchField`]
            }
        });
        return urlObj.origin + urlObj.pathname + '?' +search + urlObj.hash;
    }

    /**
     * 解析url
     * @param url
     * @param query 确定的查询参数
     * @param row 可以给格式化url提供的参数集，一般是表格的行数据
     * @returns {string}
     */
    function parsedUrl(url, query, row){
        let parsedUrl = new URL(url);

        parsedUrl.searchParams.forEach((v, k, p) => {
            if (/^@/.test(v) && row.hasOwnProperty(v.substring(1))){
                p.set(k, row[v.substring(1)]);
            }
        })

        for(const key in query){
            let value = query[key];
            if (!parsedUrl.searchParams.get(key)){
                parsedUrl.searchParams.set(key, value);
            }
        }

        return parsedUrl.href
    }

    /**
     * 树形数据查找
     * @param data
     * @param callback
     * @returns {*}
     */
    function treeDataFind(data, callback) {
        for (let i = 0; i < data.length; i++) {
            let item = data[i];
            if (callback(item)) {
                return item;
            }
            if (item.children && item.children.length > 0) {
                let result = treeDataFind(item.children, callback);
                if (result) {
                    return result;
                }
            }
        }
        return null;
    }

    /**
     * 获取当前url的查询参数字符串
     * @returns {string}
     */
    function getCurrentUrlSearchString() {
        return window.location.search.substring(1).replace(/global_search=.*&?/, '');
    }

    return {
        base64Decode,
        base64Encode,
        urlInjectSearch,
        buildQuery,
        parsedUrl,
        treeDataFind,
        getCurrentUrlSearchString,
    }
})();