/**
 * 条件验证支持
 * 处理带有 __when__ 属性的验证规则
 */
(function() {
  'use strict';

  /**
   * 处理条件验证规则（计算属性版本）
   * @param {Function} getRules - 获取原始规则的函数
   * @param {Function} getModel - 获取表单模型的函数
   * @returns {Function} - 返回一个 computed 函数
   */
  function createConditionalRules(getRules, getModel, getContext = null) {
    // 如果环境中有 Vue 3
    if (typeof Vue !== 'undefined' && Vue.computed) {
      return Vue.computed(() => {
        const rules = getRules();
        const model = getModel();
        const context = typeof getContext === 'function' ? getContext() : null;
        return processConditionalRules(rules, model, context);
      });
    }

    // 降级方案：返回一个 getter
    return () => {
      const rules = getRules();
      const model = getModel();
      const context = typeof getContext === 'function' ? getContext() : null;
      return processConditionalRules(rules, model, context);
    };
  }

  /**
   * 处理条件验证规则
   * @param {Object} rules - 原始验证规则对象
   * @param {Object|Function} model - 表单模型（响应式对象）或获取模型的函数
   * @returns {Object} - 处理后的验证规则
   */
  function processConditionalRules(rules, model, context = null) {
    if (!rules || typeof rules !== 'object') {
      return rules;
    }

    const getModel = typeof model === 'function' ? model : () => model;
    const processedRules = {};

    for (const [field, fieldRules] of Object.entries(rules)) {
      if (!Array.isArray(fieldRules)) {
        processedRules[field] = fieldRules;
        continue;
      }

      processedRules[field] = fieldRules.map(rule => {
        const baseContext = normalizeConditionalContext(field, getModel, context);
        const useCustomRequiredValidation = shouldUseCustomRequiredValidation(rule, baseContext);

        // 如果规则没有条件，也不需要自定义 required 空值判断，直接返回
        if (!rule.__when__ && !useCustomRequiredValidation) {
          return rule;
        }

        const condition = rule.__when__ || null;
        const { __when__, ...ruleWithoutCondition } = rule;

        return {
          // 保留 required 键，让 el-form-item 的 :required 走“同步”分支，
          // 避免 Element Plus 找不到必填规则时另外补一条无 message 的英文必填规则（失焦时报 "%s is required"）
          required: !!rule.required,
          validator: (ruleObj, value, callback) => {
            try {
              const currentContext = normalizeConditionalContext(field, getModel, context);

              if (condition) {
                const currentModel = currentContext.model;
                const checkCondition = new Function(
                  'model',
                  'ctx',
                  `
                    const form = ctx.form;
                    const state = ctx.state;
                    const pageState = ctx.pageState;
                    const scope = ctx.scope;
                    const fieldName = ctx.fieldName;
                    const vm = ctx.vm;
                    const dialogRow = ctx.dialogRow;
                    const options = ctx.options;
                    const fieldConfig = ctx.fieldConfig;
                    const optionLoading = ctx.optionLoading;
                    const optionLoaded = ctx.optionLoaded;
                    const field = ctx.field;
                    const props = ctx.props;
                    return (${condition});
                  `
                );
                const shouldValidate = checkCondition(currentModel, currentContext);

                if (!shouldValidate) {
                  callback();
                  return;
                }
              }

              validateWithOriginalRule(ruleWithoutCondition, value, callback, currentContext);
            } catch (error) {
              console.error('条件验证表达式错误:', error, '条件:', condition);
              callback();
            }
          },
          trigger: rule.trigger || ['blur', 'change'],
          __scContext: baseContext
        };
      });
    }

    return processedRules;
  }

  function shouldUseCustomRequiredValidation(rule, context = null) {
    return !!rule?.required && resolveConfiguredEmptyValues(context).length > 0;
  }

  function normalizeConditionalContext(field, getModel, context) {
    const source = typeof context === 'function'
      ? context(field)
      : (context && typeof context === 'object'
        ? (typeof context[field] === 'function' ? context[field]() : (context[field] || context.default || context))
        : null);
    const resolved = source && typeof source === 'object' ? source : {};
    const model = typeof getModel === 'function' ? (getModel() || {}) : {};
    const form = resolved.form && typeof resolved.form === 'object' ? resolved.form : model;
    const state = resolved.state && typeof resolved.state === 'object' ? resolved.state : (resolved.pageState && typeof resolved.pageState === 'object' ? resolved.pageState : {});
    const fieldMeta = resolved.field && typeof resolved.field === 'object' ? resolved.field : {};
    const props = fieldMeta.props && typeof fieldMeta.props === 'object' ? fieldMeta.props : {};

    return {
      model,
      form,
      state,
      pageState: resolved.pageState && typeof resolved.pageState === 'object' ? resolved.pageState : state,
      scope: resolved.scope ?? null,
      fieldName: resolved.fieldName ?? field,
      vm: resolved.vm ?? null,
      dialogRow: resolved.dialogRow ?? null,
      options: Array.isArray(resolved.options) ? resolved.options : [],
      fieldConfig: resolved.fieldConfig && typeof resolved.fieldConfig === 'object' ? resolved.fieldConfig : {},
      optionLoading: resolved.optionLoading === true,
      optionLoaded: resolved.optionLoaded === true,
      field: fieldMeta,
      props,
    };
  }

  function resolveConfiguredEmptyValues(context = null) {
    const props = context?.props && typeof context.props === 'object' ? context.props : {};
    const fieldConfig = context?.fieldConfig && typeof context.fieldConfig === 'object' ? context.fieldConfig : {};
    const candidates = [
      props['empty-values'],
      props.emptyValues,
      fieldConfig['empty-values'],
      fieldConfig.emptyValues,
    ];

    return candidates.reduce((values, candidate) => {
      return values.concat(parseConfiguredEmptyValues(candidate));
    }, []);
  }

  function parseConfiguredEmptyValues(value) {
    if (Array.isArray(value)) {
      return value;
    }

    if (value === null || value === undefined || value === '') {
      return [];
    }

    if (typeof value === 'string') {
      const trimmed = value.trim();
      if (trimmed === '') {
        return [];
      }

      try {
        const parsed = JSON.parse(trimmed);
        return Array.isArray(parsed) ? parsed : [parsed];
      } catch (_) {
      }

      try {
        const evaluated = new Function(`return (${trimmed});`)();
        return Array.isArray(evaluated) ? evaluated : [evaluated];
      } catch (_) {
      }

      return [trimmed];
    }

    return [value];
  }

  function isScalarComparableValue(value) {
    const valueType = typeof value;
    return valueType === 'string'
      || valueType === 'number'
      || valueType === 'boolean'
      || valueType === 'bigint';
  }

  function isConfiguredEmptyValue(expected, actual) {
    if (expected === actual) {
      return true;
    }

    if ((expected === null || expected === undefined) && (actual === null || actual === undefined)) {
      return true;
    }

    if (typeof expected === 'number' && typeof actual === 'number' && Number.isNaN(expected) && Number.isNaN(actual)) {
      return true;
    }

    if (isScalarComparableValue(expected) && isScalarComparableValue(actual)) {
      return String(expected) === String(actual);
    }

    return false;
  }

  function isFieldValueEmpty(value, context = null) {
    if (value === null || value === undefined || value === '' ||
        (Array.isArray(value) && value.length === 0)) {
      return true;
    }

    const emptyValues = resolveConfiguredEmptyValues(context);
    if (emptyValues.length === 0) {
      return false;
    }

    return emptyValues.some((emptyValue) => isConfiguredEmptyValue(emptyValue, value));
  }

  /**
   * 使用原始规则执行验证
   * @param {Object} rule - 原始验证规则
   * @param {*} value - 字段值
   * @param {Function} callback - 回调函数
   */
  function validateWithOriginalRule(rule, value, callback, context = null) {
    if (rule.required) {
      if (isFieldValueEmpty(value, context)) {
        callback(new Error(rule.message || '该字段为必填项'));
        return;
      }
    }

    if (rule.type) {
      if (rule.type === 'email' && value) {
        const emailReg = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        if (!emailReg.test(value)) {
          callback(new Error(rule.message || '请输入正确的邮箱地址'));
          return;
        }
      }
      callback();
      return;
    }

    if (rule.pattern) {
      if (value && !rule.pattern.test(value)) {
        callback(new Error(rule.message || '格式不正确'));
        return;
      }
      callback();
      return;
    }

    if (rule.min !== undefined || rule.max !== undefined) {
      const len = value ? value.length : 0;
      if (rule.min !== undefined && rule.max !== undefined) {
        if (len < rule.min || len > rule.max) {
          callback(new Error(rule.message || `长度需在 ${rule.min} 到 ${rule.max} 之间`));
          return;
        }
      } else if (rule.min !== undefined) {
        if (len < rule.min) {
          callback(new Error(rule.message || `长度不能少于 ${rule.min}`));
          return;
        }
      } else if (rule.max !== undefined) {
        if (len > rule.max) {
          callback(new Error(rule.message || `长度不能超过 ${rule.max}`));
          return;
        }
      }
      callback();
      return;
    }

    if (typeof rule.validator === 'function') {
      rule.validator(rule, value, callback);
      return;
    }

    callback();
  }

  // 导出到全局
  if (typeof globalThis !== 'undefined') {
    globalThis.__SC_V2_CONDITIONAL_VALIDATION__ = {
      createConditionalRules,
      processConditionalRules,
      validateWithOriginalRule
    };
  }
})();
