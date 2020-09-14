<?php
namespace bang\fasttree;

class FastTree{

    //实例
    protected static $instance = null;

    //默认配置
    protected $config = [];

    public $options = [];

    /**
     * 生成树型结构所需要的2维数组
     * @var array
     */
    public $data = [];

    /**
     * 生成树型结构所需修饰符号，可以换成图片
     * @var array
     */
    public $icon = array('│', '├', '└');
    public $nbsp = "&nbsp;";
    public $pidName = 'pid';

    public function __construct($options = [])
    {
        $this->options = array_merge($this->config, $options);
    }

    /**
     * 初始化
     * @param array $options
     * @return static|null
     */
    public static function instance($options = [])
    {
        if (is_null(self::$instance))
        {
            self::$instance = new static($options);
        }

        return self::$instance;
    }

    /**
     *
     * @param array 2维数组，例如：
     * array(
     *      1 => array('id'=>'1','pid'=>0,'name'=>'一级栏目一'),
     *      2 => array('id'=>'2','pid'=>0,'name'=>'一级栏目二'),
     *      3 => array('id'=>'3','pid'=>1,'name'=>'二级栏目一'),
     *      4 => array('id'=>'4','pid'=>1,'name'=>'二级栏目二'),
     *      5 => array('id'=>'5','pid'=>2,'name'=>'二级栏目三'),
     *      6 => array('id'=>'6','pid'=>3,'name'=>'三级栏目一'),
     *      7 => array('id'=>'7','pid'=>3,'name'=>'三级栏目二')
     *      )
     */
    /**
     * 初始化方法
     * @param array $data
     * 结构：[
     *  1 => ['id' => 1 , 'pid' => 0 , 'name' => '一级栏目一']
     * ]
     * @param null $pidName
     * @param null $nbsp
     * @return $this
     */
    public function init($data = [], $pidName = NULL, $nbsp = NULL)
    {
        $this->data = $data;
        if (!is_null($pidName))
            $this->pidName = $pidName;
        if (!is_null($nbsp))
            $this->nbsp = $nbsp;
        return $this;
    }

    /**
     * 得到子级数组
     * @param int|string $myId
     * @return array
     */
    public function getChild($myId)
    {
        $res = [];
        foreach ($this->data as $value){
            if (!isset($value['id']))
                continue;
            if ($value[$this->pidName] == $myId)
                $res[$value['id']] = $value;
        }
        return $res;
    }

    /**
     * 读取指定节点的所有孩子节点
     * @param int|string $myId 节点ID
     * @param boolean $withSelf 是否包含自身
     * @return array
     */
    public function getChildren($myId, $withSelf = false)
    {
        $res = [];
        foreach ($this->data as $value){
            if (!isset($value['id'])){
                continue;
            }
            if ( $value[$this->pidName] == $myId ){
                $res[] = $value;
                $res = array_merge($res, $this->getChildren($value['id']));
            }elseif ($withSelf && $value['id'] == $myId)
            {
                $res[] = $value;
            }
        }
        return $res;
    }

    /**
     * 读取指定节点的所有孩子节点ID
     * @param int|string $myId 节点ID
     * @param boolean $withself 是否包含自身
     * @return array
     */
    public function getChildrenIds($myId, $withSelf = false)
    {
        $childrenList = $this->getChildren($myId, $withSelf);
        $childrenIds = [];
        if(!empty($childrenList)){
            $childrenIds = array_column('id');
        }
        return $childrenIds;
    }

    /**
     * 得到当前位置父辈数组
     * @param $myId
     * @return array
     */
    public function getParent($myId)
    {
        $pid = 0;
        $res = [];
        foreach ($this->data as $value){
            if (!isset($value['id'])){
                continue;
            }
            if ($value['id'] == $myId){
                $pid = $value[$this->pidName];
                break;
            }
        }
        if ($pid){
            foreach ($this->data as $value){
                if ($value['id'] == $pid){
                    $res[] = $value;
                    break;
                }
            }
        }
        return $res;
    }

    /**
     * 得到当前位置所有父辈数组
     * @param int|string $myId
     * @param bool $withSelf
     * @return array
     */
    public function getParents($myId, $withSelf = false)
    {
        $pid = 0;
        $res = [];
        foreach ($this->data as $value){
            if (!isset($value['id'])){
                continue;
            }
            if ($value['id'] == $myId){
                if ($withSelf){
                    $res[] = $value;
                }
                $pid = $value[$this->pidName];
                break;
            }
        }
        if ($pid){
            $arr = $this->getParents($pid, true);
            $res = array_merge($arr, $res);
        }
        return $res;
    }

    /**
     * 读取指定节点所有父类节点ID
     * @param int|string $myId
     * @param boolean $withself
     * @return array
     */
    public function getParentsIds(int $myId, $withSelf = FALSE)
    {
        $parentList = $this->getParents($myId, $withSelf);
        $parentsIds = [];
        foreach ($parentList as $k => $v){
            $parentsIds[] = $v['id'];
        }
        return $parentsIds;
    }


    /**
     * 树型结构Option
     * @param int|string $myId 表示获得这个ID下的所有子级
     * @param string $itemTpl 条目模板 如："<option value=@id @selected @disabled>@spacer@name</option>"
     * @param string $selectedIds 被选中的ID，比如在做树型下拉框的时候需要用到
     * @param string $disabledIds 被禁用的ID，比如在做树型下拉框的时候需要用到
     * @param string $itemPrefix 每一项前缀
     * @param string $topTpl 顶级栏目的模板
     * @return string
     */
    public function getTree($myId, string $itemTpl = "<option value=@id @selected @disabled>@spacer@name</option>", $selectedIds = '', $disabledIds = '', string $itemPrefix = '', string  $topTpl = '')
    {
        $ret = '';
        $number = 1;
        $childrens = $this->getChild($myId);
        if ($childrens){
            $total = count($childrens);
            foreach ($childrens as $value)
            {
                $id = $value['id'];
                $j = $k = '';
                if ($number == $total){
                    $j .= $this->icon[2];
                    $k = $itemPrefix ? $this->nbsp : '';
                }else{
                    $j .= $this->icon[1];
                    $k = $itemPrefix ? $this->icon[0] : '';
                }
                $spacer = $itemPrefix ? $itemPrefix . $j : '';
                $selected = $selectedIds && in_array($id, (is_array($selectedIds) ? $selectedIds : explode(',', $selectedIds))) ? 'selected' : '';
                $disabled = $disabledIds && in_array($id, (is_array($disabledIds) ? $disabledIds : explode(',', $disabledIds))) ? 'disabled' : '';
                $value = array_merge($value, array('selected' => $selected, 'disabled' => $disabled, 'spacer' => $spacer));
                $value = array_combine(array_map(function($k) {
                    return '@' . $k;
                }, array_keys($value)), $value);
                $nstr = strtr((($value["@{$this->pidName}"] == 0 || $this->getChild($id) ) && $topTpl ? $topTpl : $itemTpl), $value);
                $ret .= $nstr;
                $ret .= $this->getTree($id, $itemTpl, $selectedIds, $disabledIds, $itemPrefix . $k . $this->nbsp, $topTpl);
                $number++;
            }
        }
        return $ret;
    }

    /**

     * 树型结构UL
     * @param int $myId 表示获得这个ID下的所有子级
     * @param string $itemTpl 条目模板 如："<li value=@id @selected @disabled>@name @childlist</li>"
     * @param string $selectedids 选中的ID
     * @param string $disabledids 禁用的ID
     * @param string $wrapTag 子列表包裹标签
     * @return string

     */
    public function getTreeUl($myId, $itemTpl, $selectedIds = '', $disabledIds = '', string $wrapTag = 'ul', string $wrapAttr = '')
    {
        $str = '';
        $children = $this->getChild($myId);
        if ($children){
            foreach ($children as $value){
                $id = $value['id'];
                unset($value['child']);
                $selected = $selectedIds && in_array($id, (is_array($selectedIds) ? $selectedIds : explode(',', $selectedIds))) ? 'selected' : '';
                $disabled = $disabledIds && in_array($id, (is_array($disabledIds) ? $disabledIds : explode(',', $disabledIds))) ? 'disabled' : '';
                $value = array_merge($value, array('selected' => $selected, 'disabled' => $disabled));
                $value = array_combine(array_map(function($k) {
                    return '@' . $k;
                }, array_keys($value)), $value);
                $nstr = strtr($itemTpl, $value);
                $childData = $this->getTreeUl($id, $itemTpl, $selectedIds, $disabledIds, $wrapTag, $wrapAttr);
                $childList = $childData ? "<{$wrapTag} {$wrapAttr}>" . $childData . "</{$wrapTag}>" : "";
                $str .= strtr($nstr, array('@childlist' => $childList));
            }
        }
        return $str;
    }

    /**
     * 菜单数据
     * @param int $myId
     * @param string $itemTpl
     * @param mixed $selectedIds
     * @param mixed $disabledIds
     * @param string $wrapTag
     * @param string $wrapAttr
     * @param int $deeplevel
     * @return string
     */
    public function getTreeMenu($myId, $itemTpl, $selectedIds = '', $disabledIds = '', $wrapTag = 'ul', $wrapAttr = '', $deeplevel = 0)
    {
        $str = '';
        $children = $this->getChild($myId);
        if ($children)
        {
            foreach ($children as $value)
            {
                $id = $value['id'];
                unset($value['child']);
                $selected = in_array($id, (is_array($selectedIds) ? $selectedIds : explode(',', $selectedIds))) ? 'selected' : '';
                $disabled = in_array($id, (is_array($disabledIds) ? $disabledIds : explode(',', $disabledIds))) ? 'disabled' : '';
                $value = array_merge($value, array('selected' => $selected, 'disabled' => $disabled));
                $value = array_combine(array_map(function($k) {
                    return '@' . $k;
                }, array_keys($value)), $value);
                $bakValue = array_intersect_key($value, array_flip(['@url', '@caret', '@class']));
                $value = array_diff_key($value, $bakValue);
                $nstr = strtr($itemTpl, $value);
                $value = array_merge($value, $bakValue);
                $childData = $this->getTreeMenu($id, $itemTpl, $selectedIds, $disabledIds, $wrapTag, $wrapAttr, $deeplevel + 1);
                $childList = $childData ? "<{$wrapTag} {$wrapAttr}>" . $childData . "</{$wrapTag}>" : "";
                $childList = strtr($childList, array('@class' => $childData ? 'last' : ''));
                $value = array(
                    '@childlist' => $childList,
                    '@url'       => $childData || !isset($value['@url']) ? "javascript:;" : $value['@url'],
                    '@addtabs'   => $childData || !isset($value['@url']) ? "" : (stripos($value['@url'], "?") !== false ? "&" : "?") . "ref=addtabs",
                    '@caret'     => ($childData && (!isset($value['@badge']) || !$value['@badge']) ? '<i class="fa fa-angle-left"></i>' : ''),
                    '@badge'     => isset($value['@badge']) ? $value['@badge'] : '',
                    '@class'     => ($selected ? ' active' : '') . ($disabled ? ' disabled' : '') . ($childData ? ' treeview' : ''),
                );
                $str .= strtr($nstr, $value);
            }
        }
        return $str;
    }

    /**
     * 特殊
     * @param int|string $myId 要查询的ID
     * @param string $itemTpl1   第一种HTML代码方式
     * @param string $itemTpl2  第二种HTML代码方式
     * @param mixed $selectedIds  默认选中
     * @param mixed $disabledIds  禁用
     * @param integer $itemPrefix 前缀
     */
    public function getTreeSpecial($myId, $itemTpl1, $itemTpl2, $selectedIds = 0, $disabledIds = 0, $itemPrefix = '')
    {
        $ret = '';
        $number = 1;
        $children = $this->getChild($myId);
        if ($children){
            $total = count($children);
            foreach ($children as $id => $value){
                $j = $k = '';
                if ($number == $total) {
                    $j .= $this->icon[2];
                }else{
                    $j .= $this->icon[1];
                    $k = $itemPrefix ? $this->icon[0] : '';
                }
                $spacer = $itemPrefix ? $itemPrefix . $j : '';
                $selected = $selectedIds && in_array($id, (is_array($selectedIds) ? $selectedIds : explode(',', $selectedIds))) ? 'selected' : '';
                $disabled = $disabledIds && in_array($id, (is_array($disabledIds) ? $disabledIds : explode(',', $disabledIds))) ? 'disabled' : '';
                $value = array_merge($value, array('selected' => $selected, 'disabled' => $disabled, 'spacer' => $spacer));
                $value = array_combine(array_map(function($k) {
                    return '@' . $k;
                }, array_keys($value)), $value);
                $nstr = strtr(!isset($value['@disabled']) || !$value['@disabled'] ? $itemTpl1 : $itemTpl2, $value);

                $ret .= $nstr;
                $ret .= $this->getTreeSpecial($id, $itemTpl1, $itemTpl2, $selectedIds, $disabledIds, $itemPrefix . $k . $this->nbsp);
                $number++;
            }
        }
        return $ret;
    }

    /**
     * 获取树状数组
     * @param string $myId 要查询的ID
     * @param string $nametpl 名称条目模板
     * @param string $itemPrefix 前缀
     * @return array
     */
    public function getTreeArray($myId, string $itemPrefix = '') : array
    {
        $children = $this->getChild($myId);
        $n = 0;
        $data = [];
        $number = 1;
        if ($children){
            $total = count($children);
            foreach ($children as $id => $value){
                $j = $k = '';
                if ($number == $total){
                    $j .= $this->icon[2];
                    $k = $itemPrefix ? $this->nbsp : '';
                }else{
                    $j .= $this->icon[1];
                    $k = $itemPrefix ? $this->icon[0] : '';
                }
                $spacer = $itemPrefix ? $itemPrefix . $j : '';
                $value['spacer'] = $spacer;
                $data[$n] = $value;
                $data[$n]['childList'] = $this->getTreeArray($id, $itemPrefix . $k . $this->nbsp);
                $n++;
                $number++;
            }
        }
        return $data;
    }

    /**
     * 将getTreeArray的结果返回为二维数组
     * @param array $data
     * @return array
     */
    public function getTreeList($data = [], string $field = 'name')
    {
        $res = [];
        foreach ($data as $k => $v){
            $childList = isset($v['childList']) ? $v['childList'] : [];
            unset($v['childList']);
            $v[$field] = $v['spacer'] . ' ' . $v[$field];
            $v['hasChild'] = $childList ? 1 : 0;
            if ($v['id']){
                $res[] = $v;
            }
            if ($childList){
                $res = array_merge($res, $this->getTreeList($childList, $field));
            }
        }
        return $res;
    }

}