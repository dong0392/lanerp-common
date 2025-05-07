<?php

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use JetBrains\PhpStorm\ArrayShape;
use Overtrue\Pinyin\Pinyin;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Arr;


// 加密
if (!function_exists('_openssl_private_encrypt')) {
    function _openssl_private_encrypt($data)
    {
        // 已有的私钥和公钥
        $authorize_license_private_key = env('AUTHORIZE_LICENSE_PRIVATE');

        $os = PHP_OS;
        if (strpos(strtolower($os), 'win') === 0) {
            // Windows 操作系统
            $privateKey = file_get_contents('.' . $authorize_license_private_key);
        } else {
            $privateKey = file_get_contents($authorize_license_private_key);
        }

        if (is_array($data)) {
            $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        }

        // 获取 RSA 密钥的长度（单位：字节）
        $keyLength = openssl_pkey_get_private($privateKey);
        $keyInfo   = openssl_pkey_get_details($keyLength);
        $keyLength = $keyInfo['bits'] / 8;

        // 计算分块大小
        $blockSize = $keyLength - 11;

        // 分块加密
        $encrypted = '';
        while ($data) {
            $chunk  = substr($data, 0, $blockSize);
            $data   = substr($data, $blockSize);
            $result = '';
            if (openssl_private_encrypt($chunk, $result, $privateKey, OPENSSL_PKCS1_PADDING)) {
                $encrypted .= $result;
            } else {
                responseErr('Encryption failed: ' . openssl_error_string());
            }
        }

        // 输出加密结果
        $encryptedData = base64_encode($encrypted);
        return $encryptedData;
    }
}

// 解密-未测
if (!function_exists('_openssl_private_decrypt')) {
    function _openssl_private_decrypt($encryptedData)
    {

        // 已有的私钥和公钥
        $authorize_license_private_key = env('AUTHORIZE_LICENSE_PRIVATE');

        $os = PHP_OS;
        if (strpos(strtolower($os), 'win') === 0) {
            // Windows 操作系统
            $privateKey = file_get_contents('.' . $authorize_license_private_key);
        } else {
            $privateKey = file_get_contents($authorize_license_private_key);
        }

        // 将加密后的数据进行 base64 解码
        $encrypted = base64_decode($encryptedData);

        // 获取 RSA 密钥的长度（单位：字节）
        $keyLength = openssl_pkey_get_private($privateKey);
        $keyInfo   = openssl_pkey_get_details($keyLength);
        $keyLength = $keyInfo['bits'] / 8;

        // 计算分块大小
        $blockSize = $keyLength;

        // 分块解密
        $decrypted = '';
        while ($encrypted) {
            $chunk     = substr($encrypted, 0, $blockSize);
            $encrypted = substr($encrypted, $blockSize);
            $result    = '';
            if (openssl_private_decrypt($chunk, $result, $privateKey, OPENSSL_PKCS1_PADDING)) {
                $decrypted .= $result;
            } else {
                responseErr('Decryption failed: ' . openssl_error_string());
            }
        }

        // 返回解密后的数据
        return $decrypted;
    }
}

if (!function_exists('check_require')) {
    function check_require($request, $params)
    {
        $request = $request->all();
        if (is_array($request) && !empty($params)) {
            //效验json参数
            $params = explode(',', $params);
            foreach ($params as $key => $value) {
                $value = trim($value);
                if (!isset($request[$value]) || $request[$value] === '') {
                    exit(responseErr("缺少必传参数: {$value}"));
                }
            }
            return $request;
        }
    }
}

if (!function_exists('p')) {
    function p(...$args)
    {
        header('Content-Type: application/json; charset=utf-8');

        foreach ($args as $arg) {
            if ($arg !== '') {
                ob_start();
                var_dump($arg);
                $output = ob_get_clean();
                $output = preg_replace('/\]\=\>\n(\s+)/m', '] => ', $output);
                $output = htmlspecialchars_decode($output, ENT_QUOTES); // 解码 HTML 实体
                echo $output;
            }
        }
        die;
    }
}

if (!function_exists('get_token')) {
    function get_token()
    {
        return md5(uniqid(mt_rand(), true));
    }
}

if (!function_exists('responseData')) {
    /**
     * Notes:接口返回
     * Date: 2022/10/10
     * @param array  $data
     * @param string $msg
     * @param int    $code
     * @return \Illuminate\Http\JsonResponse|Response
     */
    function responseData($data = "", string $msg = 'success', int $code = 200)
    {
        header('Content-Type:application/json; charset=utf-8');

        return response()->json([
            'code'       => $code,
            'message'    => $msg,
            'data'       => $data,
            'serverTime' => date('Y-m-d H:i:s'),
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
}

if (!function_exists('responseSucc')) {
    /**
     * Notes:接口返回
     * Date: 2022/10/10
     * @param array  $data
     * @param string $msg
     * @param int    $code
     * @return Application|ResponseFactory|\Illuminate\Http\JsonResponse|Response
     */
    function responseSucc(string $msg = 'success', $data = "", int $code = 200)
    {
        header('Content-Type:application/json; charset=utf-8');

        return response()->json([
            'code'       => $code,
            'message'    => $msg,
            'data'       => $data,
            'serverTime' => date('Y-m-d H:i:s'),
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
}

if (!function_exists('responseErr')) {
    /**
     * Notes:接口返回
     * Date: 2022/10/10
     * @param string     $msg
     * @param int|string $code
     * @param string     $data
     * @return \Illuminate\Http\Response
     */
    function responseErr(string $msg = 'error', int|string $code = -1, $data = "")
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *'); // 允许所有域名访问
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS'); // 允许的HTTP方法
        header('Access-Control-Allow-Headers: Content-Type, Authorization'); // 允许的请求头
        echo response()->json([
            'code'       => $code,
            'message'    => $msg,
            'data'       => $data,
            'serverTime' => date('Y-m-d H:i:s'),
        ], 200, [], JSON_UNESCAPED_UNICODE)->getContent();
        die;
    }
}

if (!function_exists('array_keys_search')) {
    /**
     * 获取键对应的值
     * @param array  $array 源数组
     * @param array  $keys  要提取的键数组
     * @param string $index 二维组中指定提取的字段（唯一）
     * @return array
     */
    function array_keys_search($array, $keys, $index = ''): array
    {
        if (empty($array)) {
            return $array;
        }
        if (empty($keys)) {
            return [];
        }
        if (!empty($index) && count($array) != count($array, COUNT_RECURSIVE)) {
            $array = array_column($array, null, $index);
        }
        $list = [];
        foreach ($keys as $key) {
            if (isset($array[$key])) {
                $list[$key] = $array[$key];
            }

        }
        return $list;
    }
}

if (!function_exists('db_batch_update')) {
    /**
     * Notes:数据批量更新
     * Date: 2023/1/4
     * @param $table_name
     * @param $data
     * @param $field
     * @param $where
     * @return bool|int
     */
    function db_batch_update($table_name = '', $data = [], $field = '', $where = '')
    {
        if (!$table_name || !$data || !$field) {
            return false;
        } else {
            $sql = 'UPDATE ' . (DB::getTablePrefix()) . $table_name;
        }
        $con     = [];
        $con_sql = [];
        $fields  = [];
        foreach ($data as $key => $value) {
            $x = 0;
            foreach ($value as $k => $v) {
                if ($k != $field && !isset($con[$x]) && $x == 0) {
                    $con[$x] = " set {$k} = (CASE {$field} ";
                } elseif ($k != $field && !isset($con[$x]) && $x > 0) {
                    $con[$x] = " {$k} = (CASE {$field} ";
                }
                if ($k != $field) {
                    $temp        = $value[$field];
                    $con_sql[$x] = $con_sql[$x] ?? '';
                    $con_sql[$x] .= " WHEN '{$temp}' THEN '{$v}' ";
                    $x++;
                }
            }
            $temp = $value[$field];
            if (!in_array($temp, $fields)) {
                $fields[] = $temp;
            }
        }
        $num = count($con) - 1;
        foreach ($con as $key => $value) {
            foreach ($con_sql as $k => $v) {
                if ($k == $key && $key < $num) {
                    $sql .= $value . $v . ' end),';
                } elseif ($k == $key && $key == $num) {
                    $sql .= $value . $v . ' end)';
                }
            }
        }
        $str = implode(',', $fields);
        $sql .= " where {$field} in({$str})";
        if (!empty($where) && is_string($where)) {
            $sql = $sql . ' ' . $where;
        }
        return DB::update($sql);
    }

    /**
     * 发送短信(阿里云)
     */
    function send_msg($phone, $tplCode, $code = '')
    {
        //引入文件
        vendor('aliyun-dysms-php-sdk-lite.demo.sendSms');

        // 国内 或者 国际
        if (strlen($phone) == 11 || strlen($phone) == 10) {

            if (strlen($phone) == 11) {
                $sendResult = sendSms($phone, $tplCode, array('code' => $code));
            } else {
                $sendResult = sendEnSms($phone, $tplCode, array('code' => $code));
            }

            if ($sendResult->Code == 'OK') {
                return true;
            } else {
                //错误信息
                $tip = $sendResult->Message;
                return false;
            }
        } else {
            return false;
        }

    }


// 发送聚合云短信
// send_sms_juhe($data['phone'], "【{$wxapp_name}】您的验证码为{$code}，该验证码可用于[{$wxapp_name}]会员服务，请勿泄漏。");
    function send_sms_juhe($phone, $content)
    {

        //$url = 'http://39.107.242.113:7862/sms';  //必填--发送连接地址URL
        // $url='https://api.juhedx.com/sms';  //必填--发送连接地址URL
        $url = config('sms.juhe.url');
        $ch  = curl_init($url);

        // 记录响应报文的文件绝对路劲
        //$fp = fopen("d:\\example_homepage.txt", "w");

        // 请求参数 &key1=value1&key2=value2格式的字符串
        $post_data = array(
            'account'  => config('sms.juhe.account'),       //必填--用户帐号
            'password' => config('sms.juhe.password'),         //必填--用户密码
            // 'content' => '【壹号仓AVL】 您好，您提交的订单已被确认，请前往小程序个人中心查看并处理。',     //短信内容
            'content'  => '【北京澜景科技有限公司】' . $content,     //短信内容
            'mobile'   => $phone,  //必填--发信发送的目的号码.多个号码之间用半角逗号隔开,最多500个号码
            'extno'    => '10690565865',           //必填--接入号，即SP服务号（106XXXXXX）账号摘要里查看:接入码只是10690的话可以不必填,否则都要填写
            'rt'       => 'json'  //非必填--响应数据类型  用户可根据需要自行选择,比如json,xml,不填默认xml
        );
        $action    = 'send';   //必填--action固定send
        $str       = 'action=' . $action;
        foreach ($post_data as $k => $v) {
            $str .= '&' . $k . '=' . urlencode($v);
        }
        //$str = substr($str, 0, -1);
        //可以将相应输出到磁盘文件中
        //curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $str);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/x-www-form-urlencode; charset=utf-8',
                'Content-Length: ' . strlen($str))
        );

        $data = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($data, true);
        if ($data['status'] == '0') {
            return true;
        } else {
            /*
             * 2.0暂时注释
            $add_data = [
                'warning_title' => C('app_env') . '-聚合短信发送失败，mobile:' . $phone,
                'warning_desc'  => 'request:' . json_encode($post_data, JSON_UNESCAPED_UNICODE) . 'result:' . json_encode($data, JSON_UNESCAPED_UNICODE),
            ];
            M('system_warning')->add($add_data);
            */
            return false;
        }


        //fclose($fp);  // 文件流关闭
        // echo $return.'<br>';    // 打印响应报文

        // echo $url.'<br>';   // 打印提交地址

        // echo $str;          // 打印提交参数

    }
}

if (!function_exists('checkVerifyCode')) {
    function checkVerifyCode($phone_email, $code, $type)
    {
        if (config('app.env') === "local" && $code === "111111") {
            return true;
        }
        $sendVerifyCode = DB::table('verify_code')->where(['phone_email' => $phone_email, 'type' => $type])->orderBy('id', 'desc')->first();
        if ($sendVerifyCode) {
            $send_time = strtotime($sendVerifyCode->create_date);
            if (($send_time + 24 * 3600) < time()) {
                return [
                    'msg'  => '验证码已过期，请重新获取',
                    'code' => -1,
                ];
            }
            if ($code != $sendVerifyCode->code) {
                return [
                    'msg'  => '验证码不正确，请重新输入',
                    'code' => -1,
                ];
            }
        } else {
            return [
                'msg'  => '验证码不正确，请重新输入',
                'code' => -1,
            ];
        }
        return true;
    }
}

if (!function_exists('authUser')) {
    function authUser(): ?\App\Models\User
    {
        return \App\Models\User::auth();
        //$user               = JWTAuth::user();
        //$payload            = JWTAuth::payload();
        //$user->company_name = $payload['company_name'];
        //$user->company_id   = $payload['company_id'];
        //return $user;
    }
}

if (!function_exists('array_only')) {
    /**
     * Get a subset of the items from the given array.
     *
     * @param array        $array
     * @param array|string $keys
     * @return array
     */
    function array_only($array, $keys)
    {
        return Arr::only($array, $keys);
    }
}

if (!function_exists('user')) {
    /**
     * Notes:
     * Date: 2025/2/11
     * @param $uid
     * @return \App\Models\User|null
     */
    function user($uid = 0, $companyId = null, ?\App\Models\User $user = null)
    {
        return \App\Models\User::business($uid, $companyId, $user);
    }
}


if (!function_exists('_throwException')) {
    function _throwException($message = "", $code = -1)
    {
        throw new \RuntimeException($message, $code);
    }
}

if (!function_exists('dateTime')) {
    #[ArrayShape(['deleted_at' => "string"])] function delDateTime(): array
    {
        return ['deleted_at' => date("Y-m-d H:i:s")];
    }
}

if (!function_exists('toCamelCase')) {
    function toCamelCase($string)
    {
        // 将下划线替换为空格
        $string = str_replace('_', ' ', strtolower($string));

        // 将字符串转换为小驼峰形式
        $string = lcfirst(str_replace(' ', '', ucwords($string)));

        return $string;
    }
}

if (!function_exists('toPascalCase')) {
    function toPascalCase($string)
    {
        // 将下划线替换为空格
        $string = str_replace('_', ' ', strtolower($string));

        // 将字符串转换为大驼峰形式
        $string = str_replace(' ', '', ucwords($string));

        return $string;
    }
}

//多维数据排序
if (!function_exists('arrayDynamicSort')) {
    function arrayDynamicSort(&$data, $sortFields)
    {
        usort($data, function ($a, $b) use ($sortFields) {
            foreach ($sortFields as $field => $direction) {
                // 比较字段的值
                if ($a[$field] != $b[$field]) {
                    // 如果是降序排序
                    if ($direction == 'desc') {
                        return $b[$field] <=> $a[$field];
                    }
                    // 如果是升序排序
                    return $a[$field] <=> $b[$field];
                }
            }
            return 0; // 所有字段都相同的情况下
        });
    }
}

if (!function_exists('listToTree')) {
    // 转换列表为树形结构
    function listToTree($list, $pk = 'id', $pid = 'pid', $child = 'children', $root = 0)
    {
        if (empty($list)) {
            return [];
        }

        $tree  = [];
        $refer = [];

        foreach ($list as $key => $data) {
            $refer[$data[$pk]] =& $list[$key];
        }

        foreach ($list as $key => $data) {
            $parentId = $data[$pid];
            if ($root == $parentId) {
                $tree[] =& $list[$key];
            } else {
                if (isset($refer[$parentId])) {
                    $parent           =& $refer[$parentId];
                    $parent[$child][] =& $list[$key];
                }
            }
        }

        return $tree;
    }
}

if (!function_exists('getTimeAgo')) {
    function getTimeAgo($dateTime)
    {
        // 判断参数类型
        if (is_int($dateTime)) {
            $commentTime = $dateTime;  // 时间戳
        } else {
            $commentTime = strtotime($dateTime);  // 日期格式
        }

        // 当前时间
        $currentTime = time();

        // 创建时间对象
        $commentDate = new DateTime();
        $commentDate->setTimestamp($commentTime);

        $currentDate = new DateTime();
        $currentDate->setTimestamp($currentTime);

        // 计算时间差
        $interval = $currentDate->diff($commentDate);

        // 获取时间差的各个部分
        $year   = $interval->y;   // 年份
        $month  = $interval->m;   // 月份
        $day    = $interval->d;   // 天数
        $hour   = $interval->h;   // 小时数
        $minute = $interval->i;   // 分钟数
        $second = $interval->s;   // 秒数

        if ($year > 0) {
            return $year . '年前';
        }

        if ($month > 0) {
            return $month . '个月前';
        }

        if ($day > 0) {
            return $day . '天前';
        }

        if ($hour > 0) {
            return $hour . '小时前';
        }

        if ($minute > 0) {
            return $minute . '分钟前';
        }

        return '刚刚';
    }
}

// 生成序列号
if (!function_exists('setNum')) {
    function setNum($sign, $object_id = '')
    {
        $sign = strtoupper(trim($sign));
        if (strlen($object_id) > 5) {
            return $sign . '-' . date("ymd") . $object_id;
        }

        return $sign . '-' . date("ymd") . sprintf('%05s', $object_id);
    }
}

// 判断是否为手机端
if (!function_exists('isMobile')) {
    function isMobile()
    {
        if (isset($_SERVER['HTTP_VIA']) && stristr($_SERVER['HTTP_VIA'], "wap")) {
            return true;
        } elseif (isset($_SERVER['HTTP_ACCEPT']) && strpos(strtoupper($_SERVER['HTTP_ACCEPT']), "VND.WAP.WML")) {
            return true;
        } elseif (isset($_SERVER['HTTP_X_WAP_PROFILE']) || isset($_SERVER['HTTP_PROFILE'])) {
            return true;
        } elseif (isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/(blackberry|configuration\/cldc|hp |hp-|htc |htc_|htc-|iemobile|kindle|midp|mmp|motorola|mobile|nokia|opera mini|opera |Googlebot-Mobile|YahooSeeker\/M1A1-R2D2|android|iphone|ipod|mobi|palm|palmos|pocket|portalmmm|ppc;|smartphone|sonyericsson|sqh|spv|symbian|treo|up.browser|up.link|vodafone|windows ce|xda |xda_)/i', $_SERVER['HTTP_USER_AGENT'])) {
            return true;
        } else {
            return false;
        }
    }
}

// 字符串转换成首字母英文大写
if (!function_exists('getCharsWithPinyin')) {
    function getCharsWithPinyin($str, $length = null)
    {
        // 如果传入的字符串为空，返回空字符串
        if (empty($str)) {
            return '';
        }
        $result    = '';
        $strLength = mb_strlen($str); // 获取字符串的长度
        // 限制处理的最大字符数
        $maxLength = ($length === null) ? $strLength : min($length, $strLength);
        for ($i = 0; $i < $maxLength; $i++) {
            $char = mb_substr($str, $i, 1);  // 获取字符串中的每个字符
            // 判断字符是否为中文
            if (preg_match('/[\x{4e00}-\x{9fa5}]/u', $char)) {
                // 如果是中文，使用Pinyin类转换为拼音并取首字母
                $pinyin      = new Pinyin();
                $firstLetter = $pinyin::abbr($char);  // 获取拼音
                $result      .= strtoupper(substr($firstLetter, 0, 1)); // 转为大写并添加到结果中
            } // 如果是英文字符，则直接转换为大写字母
            elseif (preg_match('/^[a-zA-Z]+$/', $char)) {
                $result .= strtoupper($char); // 转为大写并添加到结果中
            } // 如果是数字或符号，则使用 '#' 作为占位符
            elseif (preg_match('/^[0-9\W_]+$/', $char)) {
                $result .= '#'; // 符号或数字用 '#' 表示
            } else {
                // 默认情况下，排在最后的字符用 '#' 表示
                $result .= '#';
            }
        }
        return $result;
    }
}

if (!function_exists('_date_diff')) {
    function _date_diff($enddate, $startdate): float
    {
        $date = ceil((strtotime($enddate) - strtotime($startdate)) / 86400);
        return $date;
    }
}

