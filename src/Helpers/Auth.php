<?php

namespace lanerp\common\Helpers;

use Illuminate\Support\Facades\Http;
use Sajya\Client\Client;

/**
 * 权限辅助类
 */
class Auth
{
    public const PROJECT = "Project";//商机

    //通过permission来获取module
    public static function moduleByPermission(string $permission, $module = null)
    {
        return $module ?? ucfirst(explode('.', $permission)[0]);
    }

    //获取整个模块的权限
    public static function userModuleRange($module, $uid = null)
    {
        static $modulePermissions;
        $uid = $uid ?? user()->id;
        $key = "{$module}:$uid";
        if (isset($modulePermissions[$key])) {
            $modulePermission = $modulePermissions[$key];
        } else {
            $response = (new Client(
                Http::baseUrl(config('app.domain_url') . "/v1/oa")->withHeader("uid", $uid)
            ))
                ->execute('oa@userPermissions', ['module' => $module]);
            $modulePermission = $response->result();
            $modulePermissions[$key] = $modulePermission;
        }
        return $modulePermission;
    }

    //获取用户单个权限范围
    public static function userRange(string $permission, $module = null, $uid = null)
    {
        static $userRanges;

        $module = static::moduleByPermission($permission, $module);
        $uid = $uid ?? user()->id;
        $key = "{$permission}:{$module}:$uid";
        if (isset($userRanges[$key])) {
            $userRange = $userRanges[$key];
        } else {
            $range = static::userModuleRange($module, $uid)[$permission] ?? ["isAll" => false, "user_ids" => [], "stock_ids" => []];
            $userRange = [$range["isAll"], $range["user_ids"], $range["stock_ids"]];
            $userRanges[$key] = $userRange;
        }
        return $userRange;
    }

    //获取用户单个权限范围
    public static function haveUser($checkUserId, string $permission, $module = null, $uid = null): bool
    {
        [$isAll, $uids] = static::userRange($permission, $module, $uid);
        return ($isAll || in_array($checkUserId, $uids));
    }



    // 获取用户单个权限范围-从角色模块获取
    public static function moduleDataPermission(string $permission, $module = null, $uid = null)
    {
        static $userRanges;

        $module = static::moduleByPermission($permission, $module);
        $uid = $uid ?? user()->id;
        $key = "{$permission}:{$module}:$uid";
        if (isset($userRanges[$key])) {
            $userRange = $userRanges[$key];
        } else {
            $permissionData = static::getDataPermissionFromRoleModule($module, $uid)[$permission] ?? ["isAll" => false, "user_ids" => [], "stock_ids" => []];
            $userRange = [$permissionData["isAll"], $permissionData["user_ids"], $permissionData["stock_ids"]];
            $userRanges[$key] = $userRange;
        }
        return $userRange;
    }

    //获取整个模块的权限
    public static function getDataPermissionFromRoleModule($module, $uid = null)
    {
        static $modulePermissions;
        $uid = $uid ?? user()->id;
        $key = "{$module}:$uid";
        if (isset($modulePermissions[$key])) {
            $modulePermission = $modulePermissions[$key];
        } else {
            $response = (new Client(
                Http::baseUrl(config('app.domain_url') . "/v1/oa")->withHeader("uid", $uid)
            ))
                ->execute('oa@getDataPermissionFromRoleModule', ['module' => $module]);
            $modulePermission = $response->result();
            $modulePermissions[$key] = $modulePermission;
        }
        return $modulePermission;
    }

}
