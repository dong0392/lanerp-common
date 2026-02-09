<?php

namespace lanerp\common\Exceptions;

use Exception;

class BusinessException extends \RuntimeException
{
    // 增加一个是否需要记录日志的属性
    public bool $shouldLog = false;

    public function __construct(string $message, int $code = -1, bool $shouldLog = false)
    {
        parent::__construct($message, $code);
        $this->shouldLog = $shouldLog;
    }
}
