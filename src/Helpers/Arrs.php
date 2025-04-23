<?php

namespace Lanerp\Common\Helpers;


/**
 * 数组类库
 */
class Arrs
{

    /**
     * Notes:把返回的数据集转换成Tree
     * Date: 2024/10/23
     * @param $list
     * @param $pk
     * @param $pid
     * @param $child
     * @param $root
     * @param $not_root
     * @param $is_child_ids //可以去除
     * @return array
     */
    public static function listToTree($list, $pk = 'id', $pid = 'pid', $child = '_child', $root = 0, $not_root = false, $is_child_ids = false)
    {

        if (empty($list)) {
            return [];
        }
        // 创建Tree
        $tree = array();
        if (is_array($list)) {
            // 创建基于主键的数组引用
            $refer = array();
            foreach ($list as $key => $data) {
                $refer[$data[$pk]] =& $list[$key];
            }
            foreach ($list as $key => $data) {
                // 判断是否存在parent
                $parentId = $data[$pid];
                if ($root == $parentId) {
                    $tree[] =& $list[$key];
                } else {
                    if (isset($refer[$parentId])) {
                        $parent =& $refer[$parentId];
                        if ($is_child_ids) {
                            $parent['child_ids'][] = $list[$key][$pk];
                        }
                        $parent[$child][] =& $list[$key];
                        //p($parent,$list,$refer);
                    } else {
                        //判断是否要非根节点(最上级非 $root)，true为要，默认false
                        if ($not_root) {
                            $tree[] =& $list[$key];
                        }
                    }
                }
            }
        }
        return $tree;
    }

    /**
     * Notes:tree转二维数组
     * Date: 2023/8/8
     * @param $tree
     * @return array|mixed
     */
    public static function treeToList($tree, $child = '_child')
    {
        $list = array();
        foreach ($tree as $node) {
            $copy = $node; // Create a copy of the node
            unset($copy[$child]); // Remove child nodes from the copy
            $list[] = $copy;
            if (isset($node[$child])) {
                $children = $node[$child];
                unset($node[$child]); // Optional: Remove child nodes if not needed in the flat list
                $list = array_merge($list, self::treeToList($children));
            }
        }

        return $list;
    }

    /**
     * Notes:tree转二维数组并排序
     * Date: 2024/5/9
     * @param $tree
     * @param $child
     * @param $sort
     * @return array
     */
    public static function treeToListSort($tree, $child = '_child', $sort = 'sort')
    {
        $list = static::treeToList($tree, $child);
        foreach ($list as $k => &$v) {
            $v[$sort] = $k + 1;
        }
        unset($v);
        return $list;
    }

    /**
     * Notes:
     * Date: 2024/10/23
     * @param array  $array
     * @param string $prefix   数组值前缀
     * @param string $operator 数组值与前缀连接符
     * @return array
     */
    public static function unshiftPrefix(array $array, string $prefix = "", string $operator = "."): array
    {
        if (!$array || !$prefix) {
            return $array;
        }
        return array_map(static function ($item) use ($prefix, $operator) {
            return $prefix . $operator . $item;
        }, $array);
    }

    /**
     * Notes:将数组按某个key分组
     * Date: 2024/12/11
     * @param $arr
     * @param $key
     * @param $key1
     * @return array
     */
    public static function groupBy($arr, $key, $key1 = null): array
    {
        $arrGroupBy = [];
        if (!empty($arr) && is_array($arr)) {
            foreach ($arr as $v) {
                $keys = $v[$key];
                if ($key1 !== null) {
                    $keys .= '-' . $v[$key1];
                }
                $arrGroupBy[$keys][] = $v;
            }
        }
        return $arrGroupBy;
    }

    public static function httpBuildQuery($params, $separator = ","): string
    {
        return urldecode(http_build_query(array_map(static fn($value) => is_array($value) ? implode($separator, $value) : $value, $params)));
    }
}
