<?php
/**
 * datetime: 2022/2/20 15:05
 * user    : chenlong<vip_chenlong@163.com>
 **/

namespace Sc\Util\Tool;

class Tree
{
    /**
     * @var string 节点字段
     */
    private string $node = 'id';

    /**
     * @var string 父节点字段
     */
    private string $parentNode = 'pid';

    /**
     * @var string 子节点字段
     */
    private string $childrenNode = 'children';

    /**
     * @var array 要处理的数据
     */
    private array $data;

    /**
     * @var int 返回的层数，为0表示全部返回
     */
    private int $level = 0;

    /**
     * @var null|callable 数据的循环处理
     */
    private $each = null;

    /**
     * @var array 按条件匹配返回
     */
    private array $where = [];

    /**
     * @var bool|string 传承链记录
     */
    private string|bool $inheritedChain = false;

    /**
     * @var bool 初始数据是否是树形数据
     */
    private bool $initDataIsTree;

    /**
     * @var string|null 返回的数据的数据键的来自哪个节点，默认无
     */
    private ?string $returnDataKeyFromNode = null;

    /**
     * 临时数据存储位置
     *
     * @var array
     */
    private array $tmpData = [];

    /**
     * Tree constructor.
     * @param array $data
     * @param bool $currentIsTreeData 初始数据是否是属性数据
     * @author chenlong<vip_chenlong@163.com>
     * @date 2021/11/29
     */
    public function __construct(array $data, bool $currentIsTreeData = false)
    {
        $this->data = $data;
        $this->initDataIsTree = $currentIsTreeData;
    }

    /**
     * 获取树数据
     * @return array
     */
    public function getTreeData(): array
    {
        $this->dataHandle();

        return $this->data;
    }

    /**
     * 获取线性数据
     * @return array
     */
    public function getLineData(): array
    {
        $this->dataHandle();
        $this->toLineData();
        ksort($this->data);

        return $this->data;
    }

    /**
     * 设置主要节点名称， 默认 id
     * @param string $node
     * @return Tree
     */
    public function setNodeKey(string $node): Tree
    {
        $this->node = $node;
        return $this;
    }

    /**
     * 设置父节点的名称 默认 pid
     * @param string $parentNode
     * @return Tree
     */
    public function setParentNodeKey(string $parentNode): Tree
    {
        $this->parentNode = $parentNode;
        return $this;
    }

    /**
     * 设置子节点的名称 默认 children
     * @param string $childrenNode
     * @return Tree
     */
    public function setChildrenNode(string $childrenNode): Tree
    {
        $this->childrenNode = $childrenNode;
        return $this;
    }


    /**
     * 设置返回的层数, 默认全部
     * @param int $level
     * @return Tree
     */
    public function setLevel(int $level): Tree
    {
        $this->level = $level;
        return $this;
    }

    /**
     * 设置循环处理 默认无
     * @param callable $each
     * @return Tree
     */
    public function setEach(callable $each): Tree
    {
        $this->each = $each;
        return $this;
    }

    /**
     * @link self::filter
     *
     * @param array|callable $where
     * @deprecated
     * @return $this
     */
    public function setWhere(array|callable $where): Tree
    {
        return $this->filter($where);
    }

    /**
     * 设置指定条件的数据 默认 无
     *  一级条件： ['id' = 2]
     *  多级条件： [['id' = 2], ['id' = 3]]
     *
     * @param array|callable $condition
     *
     * @return $this
     */
    public function filter(array|callable $condition): static
    {
        $this->where = is_array($condition) ? $condition : [$condition];
        return $this;
    }

    /**
     * 设置记录父级链
     * @param bool|string $inheritedChain 记录传承连的字段。传 TRUE 默认为 node字段
     * @return Tree
     */
    public function setInheritedChain(bool|string $inheritedChain): Tree
    {
        $this->inheritedChain = $inheritedChain === true ? $this->node : $inheritedChain;
        return $this;
    }


    /**
     * 数据键的节点字段，默认无
     * @param string|null $returnDataKeyFromNode
     * @return Tree
     */
    public function setReturnDataKeyFromNode(?string $returnDataKeyFromNode): Tree
    {
        $this->returnDataKeyFromNode = $returnDataKeyFromNode;
        return $this;
    }


    /**
     * @author chenlong<vip_chenlong@163.com>
     * @date 2021/11/28
     */
    private function dataHandle()
    {
        // 初始数据不是树形数据的话
        if (!$this->initDataIsTree) {

            // 内容不是数组，转为数组
            is_array(current($this->data)) or $this->data = array_map(fn($v) => (array)$v, $this->data);

            $this->toTreeData();
        }

        // 数据筛选
        $this->dataFilter();

        // 指定返回树形数据的层数
        $this->level !== 0       and $this->data = $this->dataLevelHandle($this->data);

        // 传承链
        $this->inheritedChain     and $this->data = $this->recordParentHandle($this->data);

        // 自定义的循环处理
        is_callable($this->each) and $this->data = $this->dataEachHandle($this->data);
    }


    /**
     * 转成树形数据
     *
     * @return void
     */
    private function toTreeData()
    {
        // 把数组的键转为节点字段的值
        $this->data = array_column($this->data, null, $this->node);

        foreach ($this->data as $key => &$datum) {
            // 如果在当前数组里面存在此数据的父级数据，则把此数据引用到父级数据的子节点上面
            // 比如 2 -> 1 的子节点， 3 -> 2 的子节点， 结果就是 1 -> 2 -> 3
            if (isset($this->data[$datum[$this->parentNode]])) {
                $this->returnDataKeyFromNode
                    ? $this->data[$datum[$this->parentNode]][$this->childrenNode][$datum[$this->returnDataKeyFromNode]] = &$datum
                    : $this->data[$datum[$this->parentNode]][$this->childrenNode][] = &$datum;
                continue;
            }

            // 如果在数组中找不到此数据的父节点数据，则表示该数据为顶级节点
            $this->returnDataKeyFromNode
                ? $this->tmpData[$datum[$this->returnDataKeyFromNode]] = &$datum
                : $this->tmpData[] = &$datum;
        }
        $this->data = $this->tmpData;
        $this->tmpData = [];
    }


    /***
     * @param array $data
     * @param int $currentLevel
     * @return array
     * @author chenlong<vip_chenlong@163.com>
     * @date 2021/11/28
     */
    private function dataLevelHandle(array $data, int $currentLevel = 1): array
    {
        if ($this->level < $currentLevel) {
            return [];
        }

        foreach ($data as &$datum){
            if (!empty($datum[$this->childrenNode])){
                $datum[$this->childrenNode] = $this->dataLevelHandle($datum[$this->childrenNode], $currentLevel + 1);
            }
        }
        return $data;
    }

    /**
     * 对每个数据进行自定义的处理
     * @param array $data
     * @return array
     * @author chenlong<vip_chenlong@163.com>
     * @date 2021/11/28
     */
    private function dataEachHandle(array $data): array
    {
        foreach ($data as $key => &$datum){
            if (!empty($datum[$this->childrenNode])) {
                $datum[$this->childrenNode] = $this->dataEachHandle($datum[$this->childrenNode]);
            }
            $datum = call_user_func($this->each, $datum);

            if (!$datum) unset($data[$key]);
        }

        return $data;
    }

    /**
     * 数据过滤
     *
     * @return void
     */
    private function dataFilter()
    {
        if (empty($this->where)) return;

        if (is_callable($this->where) || array_key_first($this->where) !== 0) {
            $this->where = [$this->where];
        }

        foreach ($this->where as $where) {
            $this->tmpData = [];
            $this->dataFilterHandle($this->data, $where);
            $this->data = $this->tmpData;
        }

        $this->tmpData = [];
    }

    /**
     * 数据过滤处理
     *
     * @param array $data
     * @param callable|array $where
     * @return void
     */
    private function dataFilterHandle(array $data, callable|array $where)
    {
        foreach ($data as $key => $datum){
            if (is_callable($where) ? call_user_func($where, $datum) : $this->dataFilterVerify($datum, $where)){
                $this->returnDataKeyFromNode
                    ? $this->tmpData[$key] = $datum
                    : $this->tmpData[]     = $datum;
                continue;
            }

            if (!empty($datum[$this->childrenNode])) {
                $this->dataFilterHandle($datum[$this->childrenNode], $where);
            }
        }
    }

    /**
     * 数据过滤验证
     *
     * @param array $data
     * @param array $wheres
     * @return bool
     */
    private function dataFilterVerify(array $data, array $wheres): bool
    {
        foreach ($wheres as $field => $where) {
            // 对应值为空
            if (empty($data[$field])) return false;

            if (is_array($where)) {
                // 不在指定数组
                if (!in_array($data[$field], $where, true)) return false;
            }else
                // 值不对应
                if ((string)$where !== (string)$data[$field]){
                return false;
            }
        }

        return true;
    }

    /**
     * 转成线性数据
     * @param array $data
     */
    private function toLineData(array $data = [])
    {
        $handle_data = $data ?: $this->data;
        $data or $this->data = [];

        foreach ($handle_data as &$datum) {
            // 有子节点重复此操作
            if (!empty($datum[$this->childrenNode])) {
                $this->toLineData($datum[$this->childrenNode]);
            }
            // 删除子节点，记录此数据
            unset($datum[$this->childrenNode]);
            $this->data[$datum[$this->node]] = $datum;
        }
    }

    /**
     * 记录传承链的数据处理
     * @param array $data
     * @param array $parentsInheritedChain
     * @return array
     * @author chenlong<vip_chenlong@163.com>
     * @date 2021/11/28
     */
    private function recordParentHandle(array $data, array $parentsInheritedChain = []): array
    {
        foreach ($data as &$datum) {
            $datum['_inherited_chain_'] = [...$parentsInheritedChain, $datum[$this->inheritedChain]];

            if (!empty($datum[$this->childrenNode])) {
                $datum[$this->childrenNode] = $this->recordParentHandle($datum[$this->childrenNode], $datum['_inherited_chain_']);
            }
        }
        return $data;
    }

    /**
     * [1,2,3] = 1,2,3,
     *
     * @param mixed  $chain
     * @param string $separate
     *
     * @return array{"chain":"string", "parent":"mixed", "selectValue":"mixed"}|null
     */
    public static function chainToStr(mixed $chain, string $separate = ','): ?array
    {
        if (!$chain) {
            return [
                "chain"       => '',
                'parent'      => 0,
                'selectValue' => 0,
            ];
        }
        if (!is_array($chain)) return null;

        $end = end($chain);
        return [
            "chain"       => implode($separate, $chain) . $separate,
            'parent'      => $end,
            'selectValue' => $end,
        ];
    }

    /**
     *  1,2,3, = [1,2,3]
     *
     * @param string|null $str
     * @param string      $separate
     *
     * @return array{"chain":array, "parent":mixed, "selectValue":"mixed"}
     */
    public static function chainToArr(?string $str, string $separate = ','): array
    {
        if (!$str) {
            return [
                "chain"       => [],
                'parent'      => 0,
                'selectValue' => 0,
            ];
        }
        $chain = array_filter(explode($separate, $str), fn($v) => $v !== '');
        $chain = array_map(fn($v) => filter_var($v, FILTER_VALIDATE_INT) ? (int)$v : $v, $chain);
        $end   = $chain ? end($chain) : '';
        return [
            "chain"  => $chain,
            'parent' => $end,
            'selectValue' => $end,
        ];
    }
}

