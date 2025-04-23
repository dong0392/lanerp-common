<?php

namespace Lanerp\Helpers;


/**
 * 日期时间类库
 */
class DateTimes
{

    /**
     * Notes:时间差
     * Date: 2024/12/20
     * @param $startTime
     * @param $endTime
     * @param $isTimestamp
     * @return string
     */
    public static function timeDiffStr($startTime, $endTime, $isTimestamp = true)
    {
        if ($isTimestamp) {
            $diff = $endTime - $startTime;
        } else {
            $diff = strtotime($endTime) - strtotime($startTime);
        }
        $day    = floor($diff / 86400);
        $hour   = floor($diff % 86400 / 3600);
        $minute = floor($diff % 3600 / 60);
        $second = $diff % 60;
        return ($day > 0 ? $day . '天' : '')
            . ($hour > 0 ? $hour . '小时' : '')
            . ($minute > 0 ? $minute . '分' : '')
            . ($second > 0 ? $second . '秒' : '');
    }

    /**
     * Notes:验证是否是时间戳
     * Date: 2025/3/21
     * @param $value
     * @return bool
     */
    public static function isTimestamp($value): bool
    {
        return is_int($value) || (is_string($value) && ctype_digit($value));
    }

    /**
     * Notes:时间差（天）
     * Date: 2025/3/21
     * @param $startTime
     * @param $endTime
     * @return float
     */
    public static function timeDiffDay($startTime = null, $endTime = null)
    {
        $startTime = $startTime ?? time();
        $endTime   = $endTime ?? time();
        $startTime = static::isTimestamp($startTime) ? $startTime : strtotime($startTime);
        $endTime   = static::isTimestamp($endTime) ? $endTime : strtotime($endTime);
        return floor(($endTime - $startTime) / 86400);
    }

    /**
     * Notes:秒转成天
     * Date: 2024/12/20
     * @param $second
     * @return float
     */
    public static function secondToDay($second)
    {
        return floor($second / 86400);
    }

    /**
     * 获取周几
     * @param $date
     * @return string
     */
    public static function getChineseDayOfWeek($date)
    {
        $weekMap   = [
            1 => '周一',
            2 => '周二',
            3 => '周三',
            4 => '周四',
            5 => '周五',
            6 => '周六',
            7 => '周日',
        ];
        $dayOfWeek = date('w', strtotime($date));
        return $weekMap[$dayOfWeek] ?? '-';
    }

}
