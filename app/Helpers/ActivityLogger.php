<?php

namespace App\Helpers;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Request;

class ActivityLogger
{
    public static function log($action, $module, $moduleId = null, $details = [],$user = null)
    {
        $user = $user ?? auth('sanctum')->user();
        if(!$user)
        {
            $user_id=null;
        }
        else
        {
            $user_id= $user->id;

        }

        if($user)
        {
            $activityLog = ActivityLog::where('user_id', $user->id)
            ->where('role',$user->roles->first()->name ?? 'default_role')
            ->first();
        }
        else
        {
            $activityLog=null;
        }

        if (!$activityLog) {
            $activityLog = ActivityLog::create([
                'user_id' =>$user_id,
                'role' => $user?->roles?->first()?->name ?? 'default_role',
                'logs' => [],
            ]);
        }

        $logs = is_array($activityLog->logs) ? $activityLog->logs : [];
        $logs[] = [
            'action' => $action,
            'module' => $module,
            'module_id' => $moduleId,
            'details' => $details,
            'timestamp' => now()->toIso8601String(),
            'ip_address' => Request::ip(),
            'user_agent' => Request::header('User-Agent'),
        ];
        $activityLog->logs = $logs;

        $activityLog->save();
    }

}
