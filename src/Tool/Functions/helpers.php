<?php


if (! function_exists('kv_to_form_options')) {

    /**
     * 将 options[ k => v ] 转为 表单选择适配数据
     *
     * @param array $options
     * @param bool  $keyIsInt
     *
     * @return array
     */
    function kv_to_form_options(array $options, bool $keyIsInt = false): array
    {
        $result = [];
        foreach ($options as $value => $label) {
            $result[] = [
                'value' => $keyIsInt ? (int)$value : $value,
                'label' => $label
            ];
        }

        return $result;
    }
}