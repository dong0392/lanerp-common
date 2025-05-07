<?php

namespace lanerp\common\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use lanerp\common\Helpers\Arrs;
use Illuminate\Database\Eloquent\Model;

/**
 * This is the model class for table "users".
 *
 * @property   int    $id
 * @property   int    $company_id         公司ID
 * @property   string $name               姓名
 * @property   string $phone              手机号
 * @property   string $password           密码
 * @property   string $avatar             头像
 * @property   string $email              邮箱
 * @property   int    $gender             性别 0=保密 1=男 2=女
 * @property   string $openid             openid
 * @property   string $unionid            unionid
 * @property   string $position           岗位
 * @property   int    $department_id      部门id
 * @property   int    $superior_id        上级id
 * @property   int    $permission_status  权限是否禁用 0=否 1=是
 * @property   int    $is_active          是否激活 0=否 1=是
 * @property   int    $entry_status       邀请入职状态 0=正常 1=待入职
 * @property   string $extends
 * @property   string $last_login_time    最后登录时间
 * @property   string $joined_date        入职日期
 * @property   string $quit_date          离职日期
 * @property   string $deleted_at
 * @property   string $created_at
 * @property   string $updated_at
 */
class User extends Model
{
    use HasFactory;

    protected $fillable = ['company_id', 'name', 'phone', 'password', 'avatar', 'email', 'gender', 'openid', 'unionid', 'position', 'department_id', 'superior_id', 'permission_status', 'is_active', 'entry_status', 'extends', 'last_login_time', 'joined_date', 'quit_date', 'deleted_at'];

    protected $casts = [
        'extends'         => 'json',
        'joined_date'     => 'datetime:Y-m-d',
        'last_login_time' => 'datetime:Y/m/d H:i',
        // 添加其他需要格式化的日期时间字段
    ];

    private static $_authUser;
    private static $_businessUser;

    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    /**
     * Notes:获取字段
     * @param string $type   default
     * @param string $prefix 前缀
     * @return array
     */
    public static function columns(string $type = "", string $prefix = "", $pkId = null): array
    {
        $pkId    = $pkId ?: 'id as user_id';
        $columns = [
            "default" => [$pkId, 'company_id', 'name', 'phone', 'avatar', 'email', 'gender', 'position', 'department_id', 'superior_id', 'is_active', 'entry_status', 'extends', 'joined_date', 'quit_date'],
        ];
        $columns = $columns[$type] ?? $columns["default"];
        return Arrs::unshiftPrefix($columns, $prefix);
    }
    public static function business($uid = 0, $companyId = null, ?User $user = null): ?User
    {
        if (static::$_businessUser === null && $user === null) {
            /* @var User $authUser */
            static::$_businessUser = static::query()->find($uid, static::columns(pkId: "id"));
            if (static::$_businessUser === null) {
                $model                 = new static;
                $model->id             = $uid;
                $model->company_id     = $companyId;
                static::$_businessUser = $model;
            }
        } elseif ($user) {
            static::$_businessUser = $user;
        }
        return static::$_businessUser;
    }

}
