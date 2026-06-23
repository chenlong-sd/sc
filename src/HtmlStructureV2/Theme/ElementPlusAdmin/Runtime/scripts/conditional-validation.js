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
  function createConditionalRules(getRules, getModel) {
    // 如果环境中有 Vue 3
    if (typeof Vue !== 'undefined' && Vue.computed) {
      return Vue.computed(() => {
        const rules = getRules();
        const model = getModel();
        return processConditionalRules(rules, model);
      });
    }

    // 降级方案：返回一个 getter
    return () => {
      const rules = getRules();
      const model = getModel();
      return processConditionalRules(rules, model);
    };
  }

  /**
   * 处理条件验证规则
   * @param {Object} rules - 原始验证规则对象
   * @param {Object|Function} model - 表单模型（响应式对象）或获取模型的函数
   * @returns {Object} - 处理后的验证规则
   */
  function processConditionalRules(rules, model) {
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
        // 如果规则没有条件，直接返回
        if (!rule.__when__) {
          return rule;
        }

        const condition = rule.__when__;
        const { __when__, ...ruleWithoutCondition } = rule;

        // 创建条件验证器
        return {
          validator: (ruleObj, value, callback) => {
            try {
              // 评估条件表达式 - 在验证时获取最新的 model
              const checkCondition = new Function('model', 'return ' + condition);
              const currentModel = getModel();
              const shouldValidate = checkCondition(currentModel);

              // 如果条件不满足，跳过验证
              if (!shouldValidate) {
                callback();
                return;
              }

              // 条件满足，执行原始验证逻辑
              validateWithOriginalRule(ruleWithoutCondition, value, callback);
            } catch (error) {
              console.error('条件验证表达式错误:', error, '条件:', condition);
              // 出错时跳过验证
              callback();
            }
          },
          trigger: rule.trigger || ['blur', 'change']
        };
      });
    }

    return processedRules;
  }

  /**
   * 使用原始规则执行验证
   * @param {Object} rule - 原始验证规则
   * @param {*} value - 字段值
   * @param {Function} callback - 回调函数
   */
  function validateWithOriginalRule(rule, value, callback) {
    // 处理 required 规则
    if (rule.required) {
      if (value === null || value === undefined || value === '' ||
          (Array.isArray(value) && value.length === 0)) {
        callback(new Error(rule.message || '该字段为必填项'));
        return;
      }
      callback();
      return;
    }

    // 处理 type 规则
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

    // 处理 pattern 规则
    if (rule.pattern) {
      if (value && !rule.pattern.test(value)) {
        callback(new Error(rule.message || '格式不正确'));
        return;
      }
      callback();
      return;
    }

    // 处理 min/max 长度规则
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

    // 处理自定义 validator
    if (typeof rule.validator === 'function') {
      rule.validator(rule, value, callback);
      return;
    }

    // 默认通过验证
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
