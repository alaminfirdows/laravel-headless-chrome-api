<?php

namespace App\Http\Controllers;

use App\Services\ScrappingService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScrapperController extends Controller
{
    public function __construct(public ScrappingService $scrappingService)
    {
        //
    }

    public function fetchRecursive(Request $request): JsonResponse
    {
        $request->validate([
            'url' => ['required', 'url'],
            'max_execution_time' => ['sometimes', 'nullable', 'integer', 'min:3'],
            'max_stack_length' => ['sometimes', 'nullable', 'integer', 'min:1'],
        ]);

        $maxExecutionTime = $request->input('max_execution_time', 100);
        $maxStackLength = $request->input('max_stack_length', 100);

        try {
            /** @var Illuminate\Http\Client\Response */
            $data = $this->scrappingService->fetchRecursive($request->url, $maxExecutionTime, $maxStackLength);

            return response()->json([
                'message' => 'Data fetched successfully',
                'data'    => $data,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch URL',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function fetch(Request $request): JsonResponse
    {
        $request->validate([
            'url' => ['required', 'url'],
        ]);

        try {
            /** @var Illuminate\Http\Client\Response */
            $data = $this->scrappingService->fetch($request->url);

            return response()->json([
                'message' => 'Data fetched successfully',
                'data'    => $data,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch URL',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
