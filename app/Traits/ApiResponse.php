<?php

namespace App\Traits;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    /**
     * إرجاع response ناجح
     */
    protected function successResponse($data = null, ?string $message = null, int $statusCode = 200): JsonResponse
    {
        $message = $message ?? __('messages.success');

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    /**
     * إرجاع response به خطأ
     **/
    protected function errorResponse(?string $message = null, int $statusCode = 400, $errorDetails = null): JsonResponse
    {
        $message = $message ?? __('messages.error');
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errorDetails) {
            $response['error'] = $errorDetails;
        }

        return response()->json($response, $statusCode);
    }

    /**
     * إرجاع response للبيانات التي تم إنشاؤها
     */
    protected function createdResponse($data, ?string $message = null): JsonResponse
    {
        return $this->successResponse($data, $message ?? __('messages.resource_created_successfully'), 201);
    }

    /**
     * إرجاع response للبيانات غير الموجودة
     */
    protected function notFoundResponse(?string $message = null): JsonResponse
    {
        return $this->errorResponse($message ?? __('messages.resource_not_found'), 404);
    }

    protected function paginatedResponse($paginator, $resourceCollection, ?string $message = null): JsonResponse
    {
        $message = $message ?? __('messages.fetched_successfully');
        if ($paginator instanceof LengthAwarePaginator) {
            // paginate()
            $meta = [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'next_page_url' => $paginator->nextPageUrl(),
                'prev_page_url' => $paginator->previousPageUrl(),
            ];
        } elseif ($paginator instanceof Paginator) {
            // simplePaginate()
            $meta = [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'next_page_url' => $paginator->nextPageUrl(),
                'prev_page_url' => $paginator->previousPageUrl(),
            ];
        } else {
            $meta = [];
        }

        return $this->successResponse([
            'items' => $resourceCollection,
            'meta' => $meta,
        ], $message);
    }

    protected function paginatedResponseWithSeo($paginator, $resourceCollection, ?string $message = null, $seo = null): JsonResponse
    {
        $message = $message ?? __('messages.fetched_successfully');
        if ($paginator instanceof LengthAwarePaginator) {
            // paginate()
            $meta = [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'next_page_url' => $paginator->nextPageUrl(),
                'prev_page_url' => $paginator->previousPageUrl(),
                'seo' => $seo,
            ];
        } elseif ($paginator instanceof Paginator) {
            // simplePaginate()
            $meta = [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'next_page_url' => $paginator->nextPageUrl(),
                'prev_page_url' => $paginator->previousPageUrl(),
                'seo' => $seo,
            ];
        } else {
            $meta = [];
        }

        return $this->successResponse([
            'items' => $resourceCollection,
            'meta' => $meta,
        ], $message);
    }

    public function returnPaginateData($data)
    {
        $custom_return = collect(
            [
                'status' => true,
                'error_code' => 0,
                'error_msg' => __('messages.successfully'),
            ]
        );

        return response()->json($custom_return->merge($data));
    }

    protected function paginatedWithExtraResponse(
        $paginator,
        $resourceCollection,
        array $extraData = [],
        ?string $message = null
    ): JsonResponse {
        $message = $message ?? __('messages.fetched_successfully');
        if ($paginator instanceof LengthAwarePaginator) {
            $meta = [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'next_page_url' => $paginator->nextPageUrl(),
                'prev_page_url' => $paginator->previousPageUrl(),
            ];
        } elseif ($paginator instanceof Paginator) {
            $meta = [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'next_page_url' => $paginator->nextPageUrl(),
                'prev_page_url' => $paginator->previousPageUrl(),
            ];
        } else {
            $meta = [];
        }

        return response()->json([
            'success' => true,
            'message' => $message,

            // هنا بياناتك الإضافية (doctor, chat info...)
            'extra' => $extraData,

            // الرسائل
            'items' => $resourceCollection,

            // الباجينيشن
            'meta' => $meta,

        ]);
    }
}
