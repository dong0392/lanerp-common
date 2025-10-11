<?php

namespace lanerp\common\Helpers;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;
use Predis\Client;

/**
 * 工具类库
 */
class Utils
{
    /**
     * Notes:判断是否手机号
     * Date: 2022/10/11
     * @param $phoneNum
     * @return false|int
     */
    public static function isMobileNum($phoneNum)
    {
        return preg_match('/^1[3456789]\d{9}$/', $phoneNum);
    }

    /**
     * Notes:生成通用编号
     * Date: 2025/1/6
     * @param string $prefix
     * @param string $incrKeyPrefix
     * @return string
     */
    public static function genSN(string $prefix = "", string $incrKeyPrefix = ""): string
    {
        /* @var Client $redis */
        $redis    = Redis::connection();
        $date     = date('ymd');
        $time     = date('H');
        $redisKey = $incrKeyPrefix . '_' . $date;
        if (!$redis->exists($redisKey)) $redis->setex($redisKey, 86400, rand(999, 19999));
        return $prefix . $date . $time . sprintf('%05d', $redis->incr($redisKey));
    }

    /**
     * Notes:通过id生成编号
     * Date: 2025/1/6
     * @param $prefix
     * @param $pkId
     * @return string
     */
    public static function genIdSN($prefix = "", $pkId = "")
    {
        $prefix = strtoupper(trim($prefix));
        $sn     = $prefix . '-' . date("ymd");
        if (strlen($pkId) < 6) $pkId = sprintf('%05d', $pkId);
        return $sn . $pkId;
    }

    /**
     * Notes: 获取父id下的所有子id
     * Date: 2022/11/23
     * @param $list
     * @param $pk
     * @param $pid
     * @param $root
     * @param $isFirstTime
     * @return array
     */
    public static function getChildId($list, $pk = 'id', $pid = 'pid', $root = 0, $isFirstTime = true)
    {
        static $arr = [];
        if ($isFirstTime) {
            $arr = [];
        }
        foreach ($list as $val) {
            if ($val[$pid] == $root) {
                $arr[] = $val[$pk];
                static::getChildId($list, $pk, $pid, $val[$pk], false);
            }
        }
        return $arr;
    }

    /**
     * Notes:获取子id下所有父id
     * Date: 2022/11/30
     * @param $list
     * @param $pk
     * @param $cid
     * @param $root
     * @param $isFirstTime
     * @return array
     */
    public static function getParentId($list, $pk = 'id', $pid = 'pid', $root = 0, $isFirstTime = true)
    {
        static $arr = [];
        if ($isFirstTime) {
            $arr = [];
        }
        foreach ($list as $val) {
            if ($val[$pk] == $root) {
                $arr[] = $val[$pk];
                static::getParentId($list, $pk, $pid, $val[$pid], false);
            }
        }
        return $arr;
    }

    /**
     * Notes:批量更新
     * Date: 2024/10/24
     * @param string $table
     * @param array  $data
     * @param string $index 索引字段
     * @param string $where
     * @return false|mixed
     */
    public static function dbBatchUpdate(string $table = '', array $data = array(), string $index = '', string $where = ''): mixed
    {
        //使用例子
        //$data = [
        //    [
        //        "id"     => "2",
        //        "name"   => "XXX",
        //        "gender" => 0
        //    ],
        //    [
        //        "id"     => "11",
        //        "gender" => 2
        //    ]
        //];

        try {
            // 检查输入参数
            if (!$table || empty($data) || !$index) return false;

            $table = DB::getTablePrefix() . $table; // 表名
            $sqls  = [];
            // 遍历数据并构建 SQL 语句
            foreach ($data as $row) {
                $updateField = [];
                $bindings    = []; // 用于绑定参数
                // 遍历每一列，构建 SET 子句
                foreach ($row as $k => $v) {
                    if ($k !== $index) {
                        $updateField[] = "`{$k}` = ?"; // 使用 ? 作为占位符
                        $bindings[]    = $v; // 添加到绑定参数
                    }
                }
                // 确保包含主键字段
                if (isset($row[$index])) {
                    $updateFieldStr = implode(", ", $updateField);
                    $sql            = "UPDATE `{$table}` SET {$updateFieldStr} WHERE `{$index}` = ? {$where}";
                    // 将主键值添加到绑定参数
                    $bindings[] = $row[$index];
                    $sqls[]     = ['sql' => $sql, 'bindings' => $bindings];
                }
            }
            // 使用事务执行所有的更新操作
            DB::transaction(static function () use ($sqls) {
                foreach ($sqls as $item) DB::update($item['sql'], $item['bindings']); // 使用绑定参数执行更新
            });
            return true;
        } catch (\RuntimeException $e) {
            return false;
        }

    }

    /**
     * Notes:对数据库排序数据进行过滤处理
     * Date: 2024/12/4
     * @param array  $arr
     * @param string $pkId
     * @param string $sort
     * @return array
     */
    public static function getDbSortData(array $arr, string $pkId = "id", string $sort = "sort"): array
    {
        $res = [];
        foreach ($arr as $v) {
            $v[$pkId] > 0 && $res[] = ["id" => $v[$pkId], "sort" => $v[$sort]];
        }
        return $res;
    }

    /**
     * Notes:获取节假日 日期
     * Date: 2023/9/18
     * @param string $startDate
     * @param string $endDate
     * @param array  $type //类型 0 工作日 1 假日 2 节假日
     * @return array
     * @throws \Exception
     */
    public static function getHolidayDate(string $startDate, string $endDate, array $type)
    {
        $dates = self::getHoliday($startDate, $endDate);

        return array_values(array_filter(array_map(function ($item) use ($type) {
            //类型 0 工作日 1 假日 2 节假日
            return in_array($item['type'], $type) ? $item['date'] : null;
        }, $dates)));
    }

    /**
     * Notes:获取节假日
     * Date: 2023/9/18
     * @param string $startDate
     * @param string $endDate
     * @return array
     * @throws \Exception
     */
    public static function getHoliday(string $startDate, string $endDate)
    {
        $startDate = strtotime($startDate);
        $endDate   = strtotime($endDate);
        $startYear = (int)date('Y', $startDate);
        $endYear   = date('Y', $endDate);

        /* @var Client $redis */
        $redis = Redis::connection();
        $redis->select(10);
        $isSleep = false;
        //将查询年份日期数据插入到redis
        for ($year = $startYear; $year <= $endYear; $year++) {
            $score     = strtotime($year . "-01-01");
            $yearValue = $redis->zRangeByScore('holiday', $score, $score);//查询验证是否有当前年份数据
            if (empty($yearValue)) {
                if ($isSleep) sleep(1);

                $res = Http::get(
                    "https://www.mxnzp.com/api/holiday/list/year/{$year}",
                    ["ignoreHoliday" => false, "app_id" => config('common.holiday.appId'), "app_secret" => config('common.holiday.appSecret')]
                )->collect();
                //dd($res);
                $msg = "第三方日期接口返回状态失败，请稍后再试。";
                $res->get("code") == 10010 && $msg = $res['msg'];
                if ($res->get("code") !== 1) _throwException($msg);

                $yearValue = array_merge(...(array_column($res->get("data"), 'days')));
                $redis->zRemRangeByScore('holiday', strtotime($year . "-01-01"), strtotime($year . "-12-31"));//删除当前年份数据
                $zAddData = [];
                foreach ($yearValue as $k => $dayValue) {
                    //这种或下面那种都可以
                    //$redis->zAdd(
                    //    'holiday',
                    //    strtotime($dayValue['date']),
                    //    json_encode(Arr::only($dayValue, ['date', 'type', 'typeDes', 'lunarCalendar', 'weekDay']), JSON_UNESCAPED_UNICODE)
                    //);
                    $zAddData[] = strtotime($dayValue['date']);
                    $zAddData[] = json_encode(Arr::only($dayValue, ['date', 'type', 'typeDes', 'lunarCalendar', 'weekDay']), JSON_UNESCAPED_UNICODE);
                }
                $redis->zAdd('holiday', ...$zAddData);
                $isSleep = true;
            }
        }
        $holidays = $redis->zRevRangeByScore('holiday', $endDate, $startDate);

        return array_map(function ($holiday) {
            return json_decode($holiday, true);
        }, $holidays);

    }

    /**
     * Notes:计算百分比
     * Date: 2025/2/6
     * @param $firstNum
     * @param $secondNum
     * @return float|int
     */
    public static function calculatePercentage($firstNum, $secondNum)
    {
        return $secondNum != 0 ? floor($firstNum / $secondNum * 10000) / 100 : 0;
    }

    /**
     * Notes:金额进行汇率转换
     * Date: 2025/2/20
     * @param $amount
     * @param $originalAmount
     * @param $convertedAmount
     * @return float|int
     */
    public static function amountCurrencyConverted($amount, $originalAmount, $convertedAmount)
    {
        return $originalAmount != 0 ? floor($convertedAmount / $originalAmount * $amount * 100) / 100 : $amount;
    }

    public static function requestMergeExportParams()
    {
        /* @var Client $redis */
        $redis = Redis::connection();
        $redis->select(10);
        $redisKey = "export:" . request()->route('key');
        if (!$data = $redis->get($redisKey)) {
            _throwException("密匙已失效，请重新导出。");
        }
        $redis->del($redisKey);//连续导出的话可以注掉
        $data = json_decode($data, true) ?? [];
        user($data["uid"]);
        app('request')->merge($data["params"] ?? []);
        return;
    }

    /**
     * 添加待办
     * @param $uid
     * @param $type
     * @param $bizId
     * @return int
     */
    public static function addTodo($companyId, $uid, $type, $bizId)
    {
        DB::table('todos')->insert([
            'company_id' => $companyId,
            'uid' => $uid,
            'type' => $type,
            'biz_id' => $bizId,
            'status' => Todo::STATUS_PENDING,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        return true;
    }

    /**
     * 待办完成
     * @param $uid
     * @param $type
     * @param $bizId
     * @return int
     */
    public static function todoDone($uid, $type, $bizId)
    {
        DB::table('todos')->where([
            'uid' => $uid,
            'type' => $type,
            'biz_id' => $bizId,
        ])->update([
            'status' => Todo::STATUS_DONE,
            'completed_at' => now(),
            'updated_at' => now(),
        ]);
        return true;
    }

    /**
     * 待办取消
     * @param $uid
     * @param $type
     * @param $bizId
     * @return int
     */
    public static function todoCancel($uid, $type, $bizId)
    {
        DB::table('todos')->where([
            'uid' => $uid,
            'type' => $type,
            'biz_id' => $bizId,
        ])->update([
            'status' => Todo::STATUS_CANCEL,
            'updated_at' => now(),
        ]);
        return true;
    }


}
