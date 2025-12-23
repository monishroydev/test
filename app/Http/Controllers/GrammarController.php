<?php

namespace App\Http\Controllers;

use App\Services\GrammarCorrectionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class GrammarController extends Controller
{
    public function __construct(
        private GrammarCorrectionService $grammarService
    ) {}

    /**
     * Correct grammar in text
     */
    public function correct(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'text' => 'required|string|max:50000',
            'detailed' => 'boolean',
        ]);

        $text = $validated['text'];
        $detailed = $validated['detailed'] ?? false;

        if (strlen($text) > 3000) {
            $result = $this->grammarService->correctLongText($text);
        } elseif ($detailed) {
            $result = $this->grammarService->correctWithDetails($text);
        } else {
            $result = $this->grammarService->correctGrammar($text);
        }

        return response()->json($result);
    }

    /**
     * Show grammar correction form
     */
    public function index()
    {
        return view('grammar.index');
    }
}
