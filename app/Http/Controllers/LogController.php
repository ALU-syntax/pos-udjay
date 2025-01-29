<?php

namespace App\Http\Controllers;

use App\Models\LogErrorAndroid;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LogController extends Controller
{
    public function logErrorAndroid(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'timestamp' => 'required|date',
            'error_message' => 'required|string',
            'stack_trace' => 'required|string',
            'device_info' => 'required|array',
            'device_info.brand' => 'required|string',
            'device_info.model' => 'required|string',
            'device_info.os_version' => 'required|string',
            'user_info' => 'required|array',
            'user_info.user_id' => 'required|string',
            'user_info.username' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $log = LogErrorAndroid::create([
            'timestamp' => $request->timestamp,
            'error_message' => $request->error_message,
            'stack_trace' => $request->stack_trace,
            'device_info' => $request->device_info,
            'user_info' => $request->user_info,
        ]);

        return response()->json(['message' => 'Log created successfully', 'data' => $log], 201);
    }
}
