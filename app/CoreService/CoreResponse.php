<?php

namespace App\CoreService;

class CoreResponse
{
    /**
     * Create a new class instance.
     */
    public static function ok($output, $message = "")
    {
        return response()->json($output, 200);
    }

    public static function fail($ex)
    {
        $result = [
            "success" => false,
        ];

        if(!empty($ex->getErrorMessage())){
            $result["message"] = $ex->getErrorMessage();
        }

        if(!empty($ex->getErrorList())){
            $result = array_merge($result, $ex->getErrorList());
        }

        return response()->json($result, $ex->getErrorCode());
    }

    public static function error($ex)
    {
        $result["success"] = false;
        if (!empty($ex->getErrorMessage()) && !is_null($ex->getErrorMessage())){
            $result["message"] = $ex->getErrorMessage();
        } 

        $result["error_code"] = $ex->getErrorCode();
        return response()->json($result, $ex->getErrorCode());
    }
}
