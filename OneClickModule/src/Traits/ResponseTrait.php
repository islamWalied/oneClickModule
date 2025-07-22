<?php

namespace App\Traits;


use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;

trait ResponseTrait
{
    protected function returnPaginatedData($message, $code, $resource,$data)
    {
        return Response::json([
            'status' => 'success',
            'code' => $code,
            'message' => $message,
            'data' => $data,
            'pagination' => [
                'total' => $data->total(),
                'per_page' => $data->perPage(),
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'from' => $data->firstItem(),
                'to' => $data->lastItem(),
            ],
        ]);
    }


    public function returnAbort($msg, $code): void
    {
        abort($code, $msg);
    }
    public function returnError($msg,$code): JsonResponse
    {
        return Response::json([
            'status' => 'error',
            'code' => $code,
            'message' => $msg,
        ]);
    }
    public function success($msg,$code): JsonResponse
    {
        return Response::json([
            'status' => 'success',
            'code' => $code,
            'message' => $msg,
        ]);
    }
    public function returnData($msg, $code, $value): JsonResponse
    {
        return Response::json([
            'status' => 'success',
            'code' => $code,
            'message' => $msg,
            'data' => $value,
        ]);
    }
}
