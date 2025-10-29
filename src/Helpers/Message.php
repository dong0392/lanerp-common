<?php

namespace lanerp\common\Helpers;

use Illuminate\Support\Facades\DB;

/**
 * 消息
 */
class Message
{
    
    public static function setReadByTemplate($templateEnName, $relateId, $uid)
    {
        $templateId = DB::table('message_template')->where('name_en', $templateEnName)->value('id');
        if (!$templateId) {
            return false;
        }
        DB::table('message_record_user')->where([
            'template_id' => $templateId,
            'relate_id' => $relateId,
            'uid' => $uid,
        ])->update([
            'status' => 'read',
            'updated_at' => now(),
        ]);
        return true;
    }

}
