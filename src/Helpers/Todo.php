<?php

namespace lanerp\common\Helpers;

use Illuminate\Support\Facades\DB;

/**
 * 待办
 */
class Todo
{
    /**
     * 添加待办
     * @param $uid
     * @param $type
     * @param $bizId
     * @return int
     */
    public static function add($companyId, $uid, $type, $subType, $bizId)
    {
        $exists = DB::table('todos')->where([
            'uid' => $uid,
            'type' => $type,
            'sub_type' => $subType,
            'biz_id' => $bizId,
        ])->whereIn('status', [1, 2])->exists();
        if ($exists) {
            return false;
        }

        DB::table('todos')->insert([
            'company_id' => $companyId,
            'uid' => $uid,
            'type' => $type,
            'sub_type' => $subType,
            'biz_id' => $bizId,
            'status' => 1,
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
    public static function done($uid, $type, $subType, $bizId, $operatorId = 0)
    {
        DB::table('todos')->where([
            'uid' => $uid,
            'type' => $type,
            'sub_type' => $subType,
            'biz_id' => $bizId,
            'status' => 1,
        ])->update([
            'operator_id' => $operatorId,
            'status' => 2,
            'completed_at' => now(),
            'updated_at' => now(),
        ]);
        return true;
    }

    public static function doneMultiUser($uids, $type, $subType, $bizId, $operatorId = 0)
    {
        DB::table('todos')->where([
            'type' => $type,
            'sub_type' => $subType,
            'biz_id' => $bizId,
            'status' => 1,
        ])->whereIn('uid', $uids)->update([
            'operator_id' => $operatorId,
            'status' => 2,
            'completed_at' => now(),
            'updated_at' => now(),
        ]);
        return true;
    }

    public static function doneByBizId($type, $subType, $bizId, $operatorId = 0)
    {
        DB::table('todos')->where([
            'type' => $type,
            'sub_type' => $subType,
            'biz_id' => $bizId,
            'status' => 1,
        ])->update([
            'operator_id' => $operatorId,
            'status' => 2,
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
    public static function cancel($uid, $type, $subType, $bizId, $operatorId = 0)
    {
        DB::table('todos')->where([
            'uid' => $uid,
            'type' => $type,
            'sub_type' => $subType,
            'biz_id' => $bizId,
            'status' => 1,
        ])->update([
            'operator_id' => $operatorId,
            'status' => 3,
            'updated_at' => now(),
        ]);
        return true;
    }


    public static function cancelByBizId($type, $subType, $bizId, $operatorId = 0)
    {
        DB::table('todos')->where([
            'type' => $type,
            'sub_type' => $subType,
            'biz_id' => $bizId,
            'status' => 1,
        ])->update([
            'operator_id' => $operatorId,
            'status' => 3,
            'updated_at' => now(),
        ]);
        return true;
    }


}
