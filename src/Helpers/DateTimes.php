<?php

namespace lanerp\common\Helpers;


use DateTime;

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

    /**
     * Notes:传参日期距当前日期相差时间格式化
     * Date: 2025/4/30
     * @param          $dateString
     * @param int      $diffValue
     * @param string   $diffType year month day
     * @return string|null
     */
    public static function formatDateDiff($dateString, int $diffValue = 3, string $diffType = "day"): ?string
    {
        if (static::isTimestamp($dateString)) {
            $dateString = date('Y-m-d H:i:s', $dateString);
        }
        //$dateString = "2020-02-26 16:59:00";
        // 转换输入的日期字符串为 DateTime 对象
        $date = DateTime::createFromFormat('Y-m-d H:i:s', $dateString);

        if ($date === false) return $dateString;

        // 获取当前时间
        $now = new DateTime();

        // 计算时间差
        $interval      = $now->diff($date);
        $isCurrentYear = ($date->format('Y') === $now->format('Y'));
        $suffix        = $date > $now ? "后" : "前";
        // 根据时间差生成描述
        if ($interval->y === 0 && $interval->m === 0 && $interval->d === 0 && $interval->h === 0 && $interval->i < 1) {
            $dateString = "刚刚";
        } elseif ($interval->y === 0 && $interval->m === 0 && $interval->d === 0 && $interval->h === 0 && $interval->i >= 1) {
            $dateString = "{$interval->i}分钟{$suffix}";
        } elseif ($interval->y === 0 && $interval->m === 0 && $interval->d === 0 && $interval->h >= 1) {
            $dateString = "{$interval->h}小时{$suffix}";
        } elseif ($interval->y === 0 && $interval->m === 0 && $interval->d >= 1 && (($diffType === "day" && $interval->d <= $diffValue) || $diffType !== "day")) {
            $dateString = "{$interval->d}天{$suffix}";
        } elseif ($interval->y === 0 && $interval->m >= 1 && (($diffType === "month" && $interval->m <= $diffValue) || !in_array($diffType, ["month", "day"]))) {
            $dateString = "{$interval->m}月{$suffix}";
        } elseif ($interval->y >= 1 && (($diffType === "year" && $interval->y <= $diffValue) || !in_array($diffType, ["year", "month", "day"]))) {
            $dateString = "{$interval->y}年{$suffix}";
        } else {
            if ($isCurrentYear) {
                $dateString = $date->format('m-d');//$date->format('n-j');//去掉前导零，月份和日期没有零
            } else {
                $dateString = $date->format('Y-m-d');
            }
        }
        //dd($dateString, $interval);
        return $dateString;
    }


}
