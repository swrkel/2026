<?php

namespace Modules\BeautySaloons\Services\Api;

class BeautyPortalApiResponse
{
    public static function success($data = [], string $message = 'Success')
    {
        return response()->json(['success' => true, 'message' => $message, 'data' => $data]);
    }

    public static function error(string $message = 'Error', int $status = 422, $errors = [])
    {
        return response()->json(['success' => false, 'message' => $message, 'errors' => $errors], $status);
    }
}
