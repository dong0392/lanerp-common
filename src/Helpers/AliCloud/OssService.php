<?php

namespace lanerp\common\Helpers\AliCloud;

use OSS\OssClient;
use OSS\Core\OssException;

class OssService
{
    protected $client;
    protected $bucket;

    public function __construct()
    {
        // 初始化阿里云客户端
        $this->bucket = env('ALIYUN_OSS_BUCKET');
        $this->client = new OssClient(env('ALIYUN_ACCESS_KEY_ID'), env('ALIYUN_ACCESS_KEY_SECRET'), env('ALIYUN_OSS_ENDPOINT'));
    }

    /**
     * 删除文件
     *
     * @param string $filePath 文件路径
     * @return array|bool|\Illuminate\Http\Response
     */
    public function deleteFile($filePath)
    {
        try {
            // 如果传入的是单个文件路径，将其转化为数组
            $objects = is_array($filePath) ? $filePath : [$filePath];
            return $this->client->deleteObjects($this->bucket, $objects);
        } catch (\Exception $e) {
            return responseErr($e->getMessage(), $e->getCode());
        }
    }
}
