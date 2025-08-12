<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Modules\Auth\Models\User;

class ActivityLogController extends Controller
{
    public function getUserActivityLog($userId,Request $request)
    {
        $user=User::find($userId);
        if(!$user){
            return $this->respondNotFound(null, 'User not found');
        }

        $query = ActivityLog::where('user_id', $user->id)->where('role','Student');

        // if ($request->has('action')) {
        //     $query->where('action', $request->action);
        // }
        // if ($request->has('module')) {
        //     $query->where('module', $request->module);
        // }
        // if ($request->has('start_date')) {
        //     $query->where('timestamp', '>=', date('Y-m-d H:i:s', strtotime($request->start_date)));
        // }
        // if ($request->has('end_date')) {
        //     $query->where('timestamp', '<=', date('Y-m-d H:i:s', strtotime($request->end_date)));
        // }

        $logs = $query->orderBy('timestamp', 'desc')->paginate();
        return $this->respondOk($logs);
    }
}
