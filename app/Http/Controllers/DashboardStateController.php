<?php

namespace App\Http\Controllers;

use App\Services\Dashboard\DashboardOptionsService;
use App\Services\Dashboard\DashboardResetService;
use App\Services\Dashboard\DashboardStateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardStateController extends Controller
{
    public function __construct(
        private readonly DashboardStateService $state,
        private readonly DashboardOptionsService $options,
        private readonly DashboardResetService $reset,
    ) {}

    public function show(): JsonResponse
    {
        return response()->json($this->state->payload());
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'state' => ['required', 'array'],
            'state.maintenance' => ['required', 'array'],
            'state.licences' => ['required', 'array'],
            'state.rcsa' => ['required', 'array'],
            'state.maintenance.*.id' => ['required', 'integer', 'min:1'],
            'state.maintenance.*.branch' => ['required', 'string', 'max:150'],
            'state.maintenance.*.category' => ['nullable', 'string', 'max:150'],
            'state.maintenance.*.title' => ['required', 'string', 'max:2000'],
            'state.maintenance.*.priority' => ['nullable', 'string', 'max:30'],
            'state.maintenance.*.officer' => ['nullable', 'string', 'max:150'],
            'state.maintenance.*.targetDate' => ['nullable', 'date'],
            'state.maintenance.*.status' => ['required', 'string', 'max:40'],
            'state.licences.*.id' => ['required', 'integer', 'min:1'],
            'state.licences.*.branch' => ['required', 'string', 'max:150'],
            'state.licences.*.category' => ['nullable', 'string', 'max:150'],
            'state.licences.*.title' => ['required', 'string', 'max:2000'],
            'state.licences.*.authority' => ['nullable', 'string', 'max:150'],
            'state.licences.*.expiryDate' => ['nullable', 'date'],
            'state.licences.*.status' => ['required', 'string', 'max:40'],
            'state.rcsa.*.id' => ['required', 'integer', 'min:1'],
            'state.rcsa.*.branch' => ['required', 'string', 'max:150'],
            'state.rcsa.*.category' => ['required', 'string', 'max:150'],
            'state.rcsa.*.item' => ['required', 'string', 'max:10000'],
            'state.rcsa.*.status' => ['required', 'in:YES,NO,N/A,N/E'],
            'state.rcsa.*.remarks' => ['nullable', 'string', 'max:10000'],
            'state.rcsa.*.actionPlan' => ['nullable', 'string', 'max:10000'],
            'state.rcsa.*.targetDate' => ['nullable', 'date'],
        ]);

        $this->state->persist($validated['state']);

        return response()->json([
            'message' => 'Dashboard data saved successfully.',
            'state' => $this->state->state(),
        ]);
    }

    public function metadata(): JsonResponse
    {
        return response()->json(['options' => $this->options->all()]);
    }

    public function reset(): JsonResponse
    {
        $payload = $this->reset->restoreDefaults();

        return response()->json([
            'message' => 'Dashboard restored to initial dataset defaults.',
            ...$payload,
        ]);
    }
}
