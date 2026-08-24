<?php

namespace App\Http\Controllers;

use App\Services\SupportConversationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SupportChatController extends Controller
{
    public function __construct(private SupportConversationService $conversations) {}

    public function current(Request $request): JsonResponse
    {
        $data = $request->validate([
            'after_id' => ['nullable', 'integer', 'min:0'],
        ]);

        return response()->json(
            $this->conversations->state($request, (int) ($data['after_id'] ?? 0)),
        );
    }

    public function message(Request $request): JsonResponse
    {
        $payload = [
            'message' => $request->input('message', $request->input('body')),
        ];
        $data = Validator::make($payload, [
            'message' => ['required', 'string', 'max:2000'],
        ])->validate();
        $message = trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $data['message']) ?? '');

        Validator::make(['message' => $message], [
            'message' => ['required', 'string', 'max:2000'],
        ])->validate();

        return response()->json($this->conversations->send($request, $message), 201);
    }

    public function handoff(Request $request): JsonResponse
    {
        return response()->json($this->conversations->handoff($request));
    }

    public function read(Request $request): JsonResponse
    {
        return response()->json($this->conversations->markRead($request));
    }
}
