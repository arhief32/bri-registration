<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ResponseCode extends Model
{
    public static function giroNotExist()
    {
        return response()->json([
            'status' => '01',
            'message' => 'Your giro account number is not registered'
        ]);
    }

    public static function giroExist($request)
    {
        return response()->json([
            'status' => '00',
            'message' => 'Your giro account number is registered',
            'detail' => $request,
        ]);
    }

    public static function successInsertData()
    {
        return response()->json([
            'status' => '00',
            'message' => 'Your data was inserted into platform',
        ]);
    }

    public static function failedInsertData()
    {
        return response()->json([
            'status' => '01',
            'message' => 'Your data is not inserted because data is not valid or not completed',
        ]);
    }
}
