<?php

namespace lanerp\common\Exceptions;

use Exception;
use Illuminate\Foundation\Configuration\Exceptions;

class ExceptionRender
{
    public static function render(Exceptions $exceptions)
    {

        $exceptions->reportable(function (Throwable $e) {
            if ($e instanceof \App\Exceptions\BusinessException) {
                return $e->shouldLog === true;
            }
            return true;
        });

        //dd(1111);
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {

            //dd(2222);
            // 1. 处理飞书通知
            // 逻辑：如果是系统错误 (Error) 或 明确要求记录的异常，则发飞书
            $isBusiness   = $e instanceof \lanerp\common\Exceptions\BusinessException;
            $isValidation = $e instanceof \Illuminate\Validation\ValidationException;
            // 只有 (非业务且非验证) 或者 (强制要求记录的业务异常) 才发飞书
            if ((!$isBusiness && !$isValidation) || ($isBusiness && $e->shouldLog)) {
                \App\Exceptions\ExceptionNotice::feiShu($e->getCode(), $e->getMessage());
            }
            // 2. 情况 A：参数验证失败
            if ($e instanceof \Illuminate\Validation\ValidationException) {
                return response()->json([
                    'code'       => 422,
                    'message'    => $e->validator->errors()->first(),
                    'data'       => null,
                    'serverTime' => now()->toDateTimeString(),
                ], 422);
            }
            // 3. 情况 B：手动抛出的业务异常
            if ($e instanceof \lanerp\common\Exceptions\BusinessException) {
                return responseErr($e->getMessage(), $e->getCode());
            }

            // 3. 情况 C：数据库异常
            if ($e instanceof \Illuminate\Database\QueryException) {
                return responseErr("服务器开小差了，请稍后再试~",-1);
            }

            // 4. 情况 D：兜底系统报错 (包含 Error 和其他 Exception)
            $msg = config('app.debug') ? $e->getMessage() : "系统错误，请联系管理员处理~";
            return responseErr($msg, -1);
        });
    }
}
