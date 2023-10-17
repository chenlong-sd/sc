<?php

namespace Sc\Util\Wechat\Pay\Type\Params;

/**
 * Class H5Info
 *
 * @author chenlong<vip_chenlong@163.com>
 * @date   2022/5/26 14:15
 */
class H5Info
{
    /**
     * 场景类型    type    string[1,32]    是    场景类型
     * 示例值：iOS, Android, Wap
     * @var string
     */
    public string $type = '';

    /**
     * 应用名称    app_name    string[1,64]    否    应用名称
     * 示例值：王者荣耀
     *
     * @var string
     */
    public string $app_name = '';

    /**
     * 网站URL    app_url    string[1,128]    否    网站URL
     * 示例值：https://pay.qq.com
     *
     * @var string
     */
    public string $app_url = '';

    /**
     * iOS平台BundleID    bundle_id    string[1,128]    否    iOS平台BundleID
     * 示例值：com.tencent.wzryiOS
     *
     * @var string
     */
    public string $bundle_id = '';

    /**
     * Android平台PackageName    package_name    string[1,128]    否    Android平台PackageName
     * 示例值：com.tencent.tmgp.sgame
     *
     * @var string
     */
    public string $package_name = '';


}
