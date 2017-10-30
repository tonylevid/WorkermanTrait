<?php

namespace WorkermanTrait;

/**
 * WorkermanTrait辅助类
 *
 * @author Tony
 */
class Helper {

    /**
     * 输出并换行
     * @param string $msg 输出字符串
     */
    public static function println($msg) {
        isset($_SERVER['SERVER_PROTOCOL']) ? print "$msg<br />" : print "$msg\n";
    }
    
    /**
     * 把驼峰命名字符串转换成下划线命名字符串
     * @param string $str 待转换字符串
     * @return string
     */
    public static function camelToUnderscore($str) {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $str));
    }

    /**
     * 把下划线命名字符串转换成驼峰命名字符串
     * @param string $str 待转换字符串
     * @return string
     */
    public static function underscoreToCamel($str) {
        return lcfirst(implode('', array_map('ucfirst', explode('_', $str))));
    }
    
    /**
     * 创建目录
     * @param string $path 路径
     * @param int $mode linux权限mode，默认0777
     * @param int $umask umask，默认0002
     * @return bool 目录存在或创建成功返回true，失败返回false
     */
    public static function createPath($path, $mode = 0777, $umask = 0002) {
        if (!is_dir($path)) {
            if (false === @mkdir($path, $mode & (~$umask), true) && !is_dir($path)) {
                return false;
            }
            return true;
        }
        return true;
    }
    
    /**
     * 检查一个字符串是否存在于数组中
     * @param string $needle 搜索字符串
     * @param array $haystack 被搜索的数组
     * @param bool $insensitive 是否大小写不敏感，默认为false
     * @param bool $strict 是否采用严格比较，默认为false
     * @return bool
     */
    public static function strInArray($needle, $haystack, $insensitive = false, $strict = false) {
        if ($insensitive) {
            return in_array(strtolower($needle), array_map(function($v) {
                return is_string($v) ? strtolower($v) : $v;
            }, $haystack), $strict);
        } else {
            return in_array($needle, $haystack, $strict);
        }
    }

}
