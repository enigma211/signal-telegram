<?php

namespace App\Http\Controllers\Api;

use App\Enums\SignalResult;
use App\Enums\SignalStatus;
use App\Enums\TargetAudience;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreSignalRequest;
use App\Http\Requests\Api\StoreSignalResultRequest;
use App\Http\Requests\Api\StoreSignalUpdateRequest;
use App\Jobs\BroadcastResultJob;
use App\Jobs\BroadcastSignalJob;
use App\Jobs\BroadcastSignalUpdateJob;
use App\Models\Signal;
use App\Models\SignalUpdate;
use Illuminate\Http\JsonResponse;

class PythonSignalController extends Controller
{
    public function store(StoreSignalRequest $request): JsonResponse
    {
        $signal = Signal::query()->create([
            ...$request->validated(),
            'target_audience' => $request->enum('target_audience', TargetAudience::class)
                ?? TargetAudience::VipOnly,
            'status' => SignalStatus::Pending,
            'result' => SignalResult::Pending,
        ]);

        BroadcastSignalJob::dispatch($signal);

        return response()->json([
            'success' => true,
            'message' => 'Signal created successfully.',
            'data' => $signal,
        ], 201);
    }

    public function update(StoreSignalUpdateRequest $request): JsonResponse
    {
        $signal = Signal::query()->findOrFail($request->integer('signal_id'));

        $update = SignalUpdate::query()->create($request->validated());

        BroadcastSignalUpdateJob::dispatch($update);

        return response()->json([
            'success' => true,
            'message' => 'Signal update created successfully.',
            'data' => [
                'signal' => $signal->fresh(),
                'update' => $update,
            ],
        ], 201);
    }

    public function result(StoreSignalResultRequest $request): JsonResponse
    {
        $signal = Signal::query()->findOrFail($request->integer('signal_id'));

        $signal->update([
            'result' => $request->enum('result', SignalResult::class),
            'pips_gained' => $request->input('pips_gained'),
        ]);

        BroadcastResultJob::dispatch($signal->fresh());

        return response()->json([
            'success' => true,
            'message' => 'Signal result updated successfully.',
            'data' => $signal->fresh(),
        ]);
    }
}
