<?php

namespace lanerp\common\Exceptions;

use Exception;
use Illuminate\Support\Facades\Http;

class ExceptionNotice
{
    private static string $alarmUrl = "https://open.feishu.cn/open-apis/bot/v2/hook/";//29f5d769-d431-452b-80cd-893031ea1eda

    public static function feiShu($code, $message)
    {
        $webhook = self::$alarmUrl . env('FEISHU_ALARM_BOT_KEY');

        try {
            $userName = user()->name."   uid:".user()->id;
        } catch (\Exception $e) {
            $userName = "未登录";
        }
        $message = [
            "**操作人:** " . $userName,
            "\n**时间:** " . now()->toDateTimeString(),
            "\n**接口:** " . request()->url(),
            //"\n**参数:** " . json_encode(request()->all(), JSON_UNESCAPED_UNICODE),
            "\n**错误码:** " . $code,
            "\n**详情:** " . $message
        ];
        // 构造飞书消息卡片 (Markdown 格式)
        $content = [
            "msg_type" => "interactive",
            "card" => [
                "header" => [
                    "title" => [
                        "tag" => "plain_text",
                        "content" => "🚨 系统通知"
                    ],
                    "template" => "red" // 红色卡片头，适合报错
                ],
                "elements" => [
                    [
                        "tag" => "div",
                        "text" => [
                            "tag" => "lark_md",
                            "content" => implode("", $message)
                        ]
                    ]
                ]
            ]
        ];

        // 使用 Laravel 的 Http 客户端发送
        return Http::post($webhook, $content);

        //dd(request()->url());
    }
}
