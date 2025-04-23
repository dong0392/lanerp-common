<?php

namespace Lanerp\Common\Helpers;


/**
 * 字符串类库
 */
class Strs
{

    /**
     * Notes:将字符串分割并转成整型
     * Date: 2024/11/15
     * @param $value
     * @return array|mixed
     */
    public static function explodeToInt($value, string $separator = ","): mixed
    {
        if (is_array($value)) {
            return $value;
        }
        if ($value === "") {
            return [];
        }
        return array_map('intval', explode($separator, $value));
    }

}
