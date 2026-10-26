<?php

namespace App\Http\Controllers;

use App\Services\ComponentBulkImportService;
use App\Services\ComponentExcelTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ComponentImportController extends Controller
{
    /**
     * Download the official 2-Sheet Excel template.
     */
    public function downloadTemplate(ComponentExcelTemplateService $templateService): BinaryFileResponse
    {
        $tempDir = storage_path('app/temp');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        $filename = 'Template_Import_Master_Komponen_HPK_' . date('Ymd_His') . '.xlsx';
        $tempPath = $tempDir . '/' . $filename;

        $templateService->generateTemplate($tempPath);

        return response()->download($tempPath, 'Template_Import_Master_Komponen_HPK.xlsx')->deleteFileAfterSend(true);
    }

    /**
     * Parse uploaded file and return a live preview with row statuses.
     */
    public function preview(Request $request, ComponentBulkImportService $importService): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:15360', // max 15MB
            'duplicate_action' => 'nullable|string|in:skip,update',
        ], [
            'file.required' => 'Silakan pilih file Excel (.xlsx) atau CSV yang akan diimpor.',
            'file.max' => 'Ukuran file maksimal adalah 15MB.',
        ]);

        $file = $request->file('file');
        $extension = $file->getClientOriginalExtension() ?: 'xlsx';
        $duplicateAction = $request->input('duplicate_action', 'skip');

        try {
            $previewData = $importService->parseAndPreview($file->getPathname(), $extension, $duplicateAction);

            return response()->json([
                'success' => true,
                'data' => $previewData,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membaca file Excel: ' . $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Confirm and commit the previewed rows into the database.
     */
    public function confirm(Request $request, ComponentBulkImportService $importService): JsonResponse
    {
        $request->validate([
            'cache_token' => 'required_without:preview_token|nullable|string',
            'preview_token' => 'required_without:cache_token|nullable|string',
            'duplicate_action' => 'nullable|string|in:skip,update',
        ], [
            'cache_token.required_without' => 'Token sesi impor tidak ditemukan.',
            'preview_token.required_without' => 'Token sesi impor tidak ditemukan.',
        ]);

        $cacheToken = $request->input('cache_token') ?: $request->input('preview_token');
        $duplicateAction = $request->input('duplicate_action', 'skip');

        try {
            $result = $importService->confirmAndStore($cacheToken, $duplicateAction);

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => $result,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan data ke database: ' . $e->getMessage(),
            ], 422);
        }
    }
}
