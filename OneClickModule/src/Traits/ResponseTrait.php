<?php

namespace App\Traits;


use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;

trait ResponseTrait
{
    protected function returnPaginatedData($message, $code, $data, $additionalData = []): JsonResponse
    {
        return Response::json([
            'status' => 'success',
            'code' => $code,
            'message' => $message,
            'data' => $data,
            'additional_data' => $additionalData,
            'pagination' => [
                'total' => $data->total(),
                'per_page' => $data->perPage(),
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'from' => $data->firstItem(),
                'to' => $data->lastItem(),
            ],
        ], $code);
    }

    public function returnError($msg, $code): JsonResponse
    {
        return Response::json([
            'status' => 'error',
            'code' => $code,
            'message' => $msg,
        ], $code);
    }

    public function success($msg, $code): JsonResponse
    {
        return Response::json([
            'status' => 'success',
            'code' => $code,
            'message' => $msg,
        ], $code);
    }

    public function returnData($msg, $code, $value): JsonResponse
    {
        return Response::json([
            'status' => 'success',
            'code' => $code,
            'message' => $msg,
            'data' => $value,
        ], $code);
    }

    public function returnErrorFromMethod($error, $data)
    {
        return [
            'error' => $error,
            'data' => $data
        ];
    }
}
