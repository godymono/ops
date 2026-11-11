<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesAuthBranch;
use App\Services\Dashboard\DashboardRecordService;
use App\Services\Dashboard\RcsaMonthlyReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class DashboardRecordController extends Controller
{
    use ResolvesAuthBranch;

    private const MODULES = ['maintenance', 'licences', 'rcsa'];

    public function __construct(
        private readonly DashboardRecordService $records,
        private readonly RcsaMonthlyReportService $rcsaReports,
    ) {}

    public function store(Request $request, string $module): JsonResponse
    {
        $this->assertModule($module);
        $payload = $this->validatedPayload($request, $module);
        try {
            $payload = $this->constrainPayloadBranch($request, $payload);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }
        $this->assertRcsaEditable($module, $payload['branch'] ?? null);
        $record = $this->records->create($module, $payload);

        return response()->json([
            'message' => 'Record created successfully.',
            'record' => $record,
        ], 201);
    }

    public function update(Request $request, string $module, int $id): JsonResponse
    {
        $this->assertModule($module);
        $payload = $this->validatedPayload($request, $module);
        try {
            $payload = $this->constrainPayloadBranch($request, $payload);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }
        $this->assertRcsaEditable($module, $payload['branch'] ?? null, $id);
        $record = $this->records->update($module, $id, $payload);

        return response()->json([
            'message' => 'Record updated successfully.',
            'record' => $record,
        ]);
    }

    public function updateStatus(Request $request, string $module, int $id): JsonResponse
    {
        $this->assertModule($module);
        $this->assertRcsaEditable($module, null, $id);

        $validated = $request->validate([
            'status' => ['required', 'string', 'max:40'],
        ]);

        $record = $this->records->updateStatus($module, $id, $validated['status']);

        return response()->json([
            'message' => 'Status updated successfully.',
            'record' => $record,
        ]);
    }

    public function destroy(string $module, int $id): JsonResponse
    {
        $this->assertModule($module);
        $this->assertRcsaEditable($module, null, $id);
        $this->records->delete($module, $id);

        return response()->json([
            'message' => 'Record deleted successfully.',
        ]);
    }

    private function assertModule(string $module): void
    {
        abort_unless(in_array($module, self::MODULES, true), 404);
    }

    private function assertRcsaEditable(string $module, ?string $branchName = null, ?int $id = null): void
    {
        if ($module !== 'rcsa') {
            return;
        }

        $this->rcsaReports->assertCanEditBranch($branchName, $id);
    }

    private function validatedPayload(Request $request, string $module): array
    {
        $rules = [
            'branch' => ['required', 'string', 'max:150'],
            'category' => ['nullable', 'string', 'max:150'],
            'title' => ['nullable', 'string', 'max:2000'],
            'item' => ['nullable', 'string', 'max:10000'],
            'priority' => ['nullable', 'string', 'max:30'],
            'officer' => ['nullable', 'string', 'max:150'],
            'targetDate' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'max:40'],
            'authority' => ['nullable', 'string', 'max:150'],
            'expiryDate' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string', 'max:10000'],
            'actionPlan' => ['nullable', 'string', 'max:10000'],
        ];

        if ($module === 'maintenance') {
            $rules['title'] = ['required', 'string', 'max:2000'];
            $rules['status'] = ['required', 'string', 'max:40'];
        } elseif ($module === 'licences') {
            $rules['title'] = ['required', 'string', 'max:2000'];
            $rules['status'] = ['required', 'string', 'max:40'];
        } else {
            $rules['category'] = ['required', 'string', 'max:150'];
            $rules['item'] = ['required_without:title', 'nullable', 'string', 'max:10000'];
            $rules['title'] = ['required_without:item', 'nullable', 'string', 'max:2000'];
            $rules['status'] = ['required', Rule::in(['YES', 'NO', 'N/A', 'N/E'])];
        }

        return $request->validate($rules);
    }

    private function constrainPayloadBranch(Request $request, array $payload): array
    {
        $branch = $this->resolveBranchName($request, $payload['branch'] ?? null);
        if ($branch) {
            $payload['branch'] = $branch;
        }

        return $payload;
    }
}
