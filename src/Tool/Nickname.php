<?php

namespace Sc\Util\Tool;

use JetBrains\PhpStorm\ExpectedValues;

/**
 * Class Nickname
 */
class Nickname
{
    /** @var string[] 形容词 */
    const ADJECTIVE = ['快乐', '冷静', '潇洒', '积极', '冷酷', '深情', '温柔', '可爱', '愉快', '义气', '认真', '威武', '帅气', '潇洒', '漂亮', '自然', '狂野', '等待', '搞怪', '幽默', '活泼', '开心', '高兴', '超帅', '坦率', '直率', '轻松', '完美', '精明', '有魅力', '丰富', '饱满', '炙热', '碧蓝', '俊逸', '英勇', '健忘', '朴实', '兴奋', '幸福', '淡定', '不安', '阔达', '孤独', '独特', '时尚', '落后', '风趣', '大胆', '爱笑', '合适', '沉默', '斯文', '香蕉', '苹果', '鲤鱼', '鳗鱼', '任性', '细心', '甜甜', '酷酷', '健壮', '英俊', '霸气', '阳光', '默默', '大力', '善良', '重要', '欢喜', '欣慰', '满意', '跳跃', '诚心', '称心', '如意', '怡然', '无奈', '美好', '感动', '超级', '寒冷', '精明', '明理', '犹豫', '忧郁', '奋斗', '勤奋', '现代', '稳重', '热情', '含蓄', '开放', '无辜', '多情', '纯真', '热心', '从容', '体贴', '风中', '曾经', '追寻', '儒雅', '优雅', '开朗', '外向', '内向', '清爽', '文艺', '长情', '平常', '伶俐', '高大', '柔弱', '爱笑', '乐观', '耍酷', '酷炫', '神勇', '年轻', '瘦瘦', '无情', '包容', '顺心', '畅快', '舒适', '靓丽', '负责', '背后', '简单', '谦让', '彩色', '缥缈', '欢呼', '生动', '复杂', '仁爱', '魔幻', '虚幻', '淡然', '受伤', '雪白', '高高', '顺利', '闪闪', '缓慢', '迅速', '优秀', '聪明', '俏皮', '淡淡', '坚强', '平淡', '欣喜', '能干', '灵巧', '友好', '机智', '机灵', '正直', '谨慎', '俭朴', '虚心', '辛勤', '自觉', '无私', '无限', '踏实', '老实', '可靠', '务实', '拼搏', '个性', '活力', '成就', '勤劳', '单纯', '落寞', '朴素', '洁净', '清秀', '自由', '小巧', '单薄', '贪玩', '刻苦', '干净', '壮观', '和谐', '文静', '调皮', '害羞', '安详', '自信', '端庄', '坚定', '美满', '舒心', '温暖', '专注', '勤恳', '美丽', '优美', '甜美', '甜蜜', '整齐', '典雅', '尊敬', '秀丽', '喜悦', '甜美', '大方', '聪慧', '迷人', '陶醉', '悦耳', '动听', '明亮', '结实', '标致', '清脆', '敏感', '光亮', '大气', '冷傲', '呆萌', '隐形', '笑点低', '微笑', '笨笨', '难过', '沉静', '火星上', '失眠', '安静', '要减肥', '迷路', '烂漫', '哭泣', '苗条', '贪玩', '执着', '眼睛大', '高贵', '傲娇', '心灵美', '细腻', '天真', '怕黑', '飘逸', '冷艳', '爱听歌',];
    const ADJECTIVE_LENGTH = 250;

    /** @var string[] 名词 */
    const NOUN = ['凉面', '花生', '可乐', '灯泡', '哈密瓜', '背包', '眼神', '缘分', '雪碧', '人生', '牛排', '飞鸟', '斑马', '汉堡', '悟空', '绿茶', '自行车', '保温杯', '大碗', '墨镜', '魔镜', '煎饼', '月饼', '月亮', '星星', '芝麻', '啤酒', '玫瑰', '哈密瓜', '数据线', '太阳', '树叶', '芹菜', '蜜蜂', '信封', '大象', '猫咪', '路灯', '蓝天', '白云', '星月', '彩虹', '微笑', '摩托', '板栗', '高山', '大地', '大树', '蜻蜓', '红牛', '咖啡', '机器猫', '枕头', '大船', '诺言', '钢笔', '刺猬', '天空', '飞机', '冬天', '洋葱', '春天', '夏天', '秋天', '航空', '毛衣', '豌豆', '黑米', '玉米', '眼睛', '白羊', '鲜花', '白开水', '大山', '火车', '汽车', '大米', '麦片', '水杯', '水壶', '手套', '鞋子', '自行车', '鼠标', '手机', '电脑', '书本', '奇迹', '身影', '夕阳', '台灯', '未来', '钥匙', '心锁', '故事', '花瓣', '滑板', '画笔', '画板', '电源', '饼干', '宝马', '过客', '大白', '时光', '石头', '钻石', '河马', '犀牛', '抽屉', '柜子', '往事', '寒风', '路人', '橘子', '耳机', '鸵鸟', '苗条', '铅笔', '钢笔', '硬币', '热狗', '大侠', '毛巾', '期待', '盼望', '白昼', '黑夜', '钢铁侠', '哑铃', '板凳', '枫叶', '荷花', '仙人掌', '早晨', '心情', '茉莉', '流沙', '蜗牛', '冥王星', '棒球', '篮球', '乐曲', '电话', '网络', '世界', '中心', '鱼', '老虎', '雨', '羽毛', '翅膀', '外套', '火', '书包', '钢笔', '冷风', '八宝粥', '大雁', '音响', '胡萝卜', '冰棍', '帽子', '菠萝', '蛋挞', '香水', '猕猴桃', '吐司', '溪流', '樱桃', '鸽子', '蝴蝶', '爆米花', '花卷', '海豚', '日记本', '熊猫', '荔枝', '镜子', '曲奇', '金针菇', '松鼠', '紫菜', '金鱼', '柚子', '果汁', '项链', '帆布鞋', '火龙果', '奇异果', '煎蛋', '土豆', '雪糕', '铃铛', '红酒', '月光', '酸奶', '咖啡豆', '蜜蜂', '蜡烛', '棉花糖', '向日葵', '水蜜桃', '丸子', '糖豆', '薯片', '乌冬面', '冰淇淋', '棒棒糖', '长颈鹿', '豆芽', '铃铛', '馒头', '小笼包', '甜瓜', '冬瓜', '香菇', '兔子', '蘑菇', '跳跳糖', '大白菜', '草莓', '柠檬', '月饼', '纸鹤', '云朵', '芒果', '面包', '海燕', '龙猫', '羊', '黑猫', '白猫', '金毛', '山水', '音响', '皮皮虾', '皮卡丘', '马里奥',];
    const NOUN_LENGTH = 237;

    /**
     * @var string[]
     */
    private array $extra = ['爱', '和', '与'];

    /**
     * 生成随机昵称
     *
     * @return string
     */
    public function generate(): string
    {
        return mt_rand(0, 1) > 0
            ? $this->getRoundValue('ADJECTIVE') . '的' . $this->getRoundValue('NOUN')
            : $this->getRoundValue('NOUN') . $this->getRoundValue('extra') . $this->getRoundValue('NOUN');
    }

    /**
     * 批量生成随机昵称
     *
     * @param int $cnt
     *
     * @return array
     */
    public function batchGenerate(int $cnt): array
    {
        $r = [];
        while ($cnt > 0) {
            $r[] = $this->generate();
            $cnt--;
        }
        return $r;
    }

    /**
     * 获取随机值
     *
     * @param string $target
     *
     * @return string
     */
    private function getRoundValue(#[ExpectedValues(['ADJECTIVE', 'NOUN', 'extra'])]string $target): string
    {
        return match ($target) {
            'ADJECTIVE' => self::ADJECTIVE[mt_rand(0, self::ADJECTIVE_LENGTH)],
            'NOUN'      => self::NOUN[mt_rand(0, self::NOUN_LENGTH)],
            default     => $this->extra[array_rand($this->extra)]
        };
    }

    /**
     * @param array $extra
     *
     * @return $this
     */
    public function setExtra(array $extra): static
    {
        $this->extra = $extra;
        return $this;
    }
}