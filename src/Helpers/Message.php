<?php

namespace lanerp\common\Helpers;

use Illuminate\Support\Facades\DB;

/**
 * 消息
 */
class Message
{

    public static function setReadByTemplate($templateEnName, $relateId, $uids)
    {

        if (!is_array($uids)) {
            $uids = [$uids];
        }

        $templateId = DB::table('message_template')->where('name_en', $templateEnName)->value('id');
        if (!$templateId) {
            return false;
        }
        DB::table('message_record_user')->where([
            'template_id' => $templateId,
            'relate_id' => $relateId,
        ])->whereIn('uid', $uids)->update([
            'status' => 'read',
            'updated_at' => now(),
        ]);
        return true;
    }

    /**
     * 根据模板英文名称和用户ID将所有相关消息标记为已读（不限制relateId）
     *
     * @param string $templateEnName 模板英文名称
     * @param array|int $uids 用户ID列表或单个用户ID
     * @return bool
     */
    public static function setAllReadByTemplate($templateEnName, $uids)
    {
        if (!is_array($uids)) {
            $uids = [$uids];
        }

        $templateId = DB::table('message_template')->where('name_en', $templateEnName)->value('id');
        if (!$templateId) {
            return false;
        }
        DB::table('message_record_user')->where([
            'template_id' => $templateId,
        ])->whereIn('uid', $uids)->update([
            'status' => 'read',
            'updated_at' => now(),
        ]);
        return true;
    }

}
