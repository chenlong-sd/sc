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

    /**
     * 打印
     * @param content 元素ID或HTML字符串
     * @param isHtml 是否是HTML字符串
     */
    function print(content, isHtml) {
        try {
            // 1. 处理内容源，检查元素是否存在
            let printContentHtml;
            if (isHtml) {
                printContentHtml = content;
            } else {
                const targetElement = document.getElementById(content);
                if (!targetElement) {
                    throw new Error(`未找到ID为"${content}"的元素`);
                }
                printContentHtml = targetElement.innerHTML;
            }

            // 2. 创建iframe并隐藏
            const iframe = document.createElement("iframe");
            iframe.style.cssText = "position:absolute;width:0;height:0;left:-999px;top:-999px;";
            document.body.appendChild(iframe);
            const iframeDoc = iframe.contentDocument || iframe.contentWindow.document;

            // 3. 写入内容（包含必要的样式）
            iframeDoc.open();
            // 引入父页面的样式（解决样式丢失问题）
            const styles = Array.from(document.styleSheets).map(sheet => {
                try {
                    // 处理同源样式表
                    if (sheet.href && new URL(sheet.href).origin === window.location.origin) {
                        return `<link rel="stylesheet" href="${sheet.href}">`;
                    }
                    // 内联样式
                    if (sheet.cssRules) {
                        return `<style>${Array.from(sheet.cssRules).map(rule => rule.cssText).join('')}</style>`;
                    }
                } catch (e) {
                    // 跨域样式表可能无法访问，忽略
                    return '';
                }
            }).join('');

            // 写入完整HTML结构（包含样式）
            iframeDoc.write(`
            <!DOCTYPE html>
            <html>
                <head>
                    <meta charset="UTF-8">
                    ${styles}
                </head>
                <body>${printContentHtml}</body>
            </html>
        `);
            iframeDoc.close();

            // 4. 等待iframe内容加载完成后再打印
            const iframeWindow = iframe.contentWindow;
            iframeWindow.addEventListener('load', () => {
                // 5. 打印完成（或取消）后再移除iframe
                const handlePrintComplete = () => {
                    document.body.removeChild(iframe);
                    iframeWindow.removeEventListener('afterprint', handlePrintComplete);
                };
                iframeWindow.addEventListener('afterprint', handlePrintComplete);
                // 触发打印
                iframeWindow.print();
            });

        } catch (error) {
            console.error('打印失败：', error);
        }
    }

    return {
        base64Decode,
        base64Encode,
        urlInjectSearch,
        buildQuery,
        parsedUrl,
        treeDataFind,
        getCurrentUrlSearchString,
        print
    }
})();