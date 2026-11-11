<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesAuthBranch;
use App\Services\Dashboard\DashboardStateService;
use App\Services\Dashboard\RcsaMonthlyReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

class RcsaReportController extends Controller
{
    use ResolvesAuthBranch;

    public function __construct(
        private readonly RcsaMonthlyReportService $reports,
    ) {}

    public function index(): JsonResponse
    {
        $periods = $this->reports->periods();
        $active = $this->reports->ensureActivePeriod();

        return response()->json([
            'periods' => $periods,
            'current' => $this->reports->periodDetail($active->id),
            'activePeriodId' => $active->id,
        ]);
    }

    public function show(int $period): JsonResponse
    {
        return response()->json($this->reports->periodDetail($period));
    }

    public function storePeriod(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'year' => ['nullable', 'integer', 'min:2020', 'max:2100'],
            'month' => ['nullable', 'integer', 'min:1', 'max:12'],
        ]);

        return response()->json(
            $this->reports->openOrCreatePeriod(
                $validated['year'] ?? null,
                $validated['month'] ?? null
            )
        );
    }

    public function submit(Request $request, int $period): JsonResponse
    {
        $validated = $request->validate([
            'branch' => ['nullable', 'string', 'max:150'],
            'submittedBy' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $branch = $this->resolveBranchName($request, $validated['branch'] ?? null);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        if (! $branch) {
            return response()->json(['message' => 'Select a branch to submit.'], 422);
        }

        if ($request->user()?->isHq()) {
            return response()->json(['message' => 'Head Office monitors reports. Branch users submit.'], 403);
        }

        $submission = $this->reports->submitBranch(
            $period,
            $branch,
            $validated['submittedBy'] ?? $request->user()?->name,
            $validated['notes'] ?? null
        );

        return response()->json([
            'message' => 'RCSA report submitted.',
            'submission' => $submission,
            'period' => $this->reports->periodDetail($period),
        ]);
    }

    public function submission(Request $request, int $submission): JsonResponse
    {
        $detail = $this->reports->submissionDetail($submission);

        if ($request->user()?->isBranch()) {
            $own = $this->authBranchName($request);
            if (($detail['branch'] ?? null) !== $own) {
                return response()->json(['message' => 'You can only view your own branch reports.'], 403);
            }
        }

        return response()->json([
            'submission' => $detail,
        ]);
    }

    public function checklist(Request $request, int $period): JsonResponse
    {
        $validated = $request->validate([
            'branch' => ['nullable', 'string', 'max:150'],
        ]);

        try {
            $branch = $this->resolveBranchName($request, $validated['branch'] ?? null);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        if ($request->user()?->isBranch()) {
            $branch = $this->authBranchName($request);
        }

        return response()->json(
            $this->reports->checklist($period, $branch)
        );
    }

    public function unlock(Request $request, int $period): JsonResponse
    {
        $validated = $request->validate([
            'branch' => ['nullable', 'string', 'max:150'],
        ]);

        try {
            $branch = $this->resolveBranchName($request, $validated['branch'] ?? null);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        if (! $branch) {
            return response()->json(['message' => 'Select a branch to unlock.'], 422);
        }

        $periodModel = \App\Models\RcsaPeriod::query()->findOrFail($period);

        // Branches may unlock only the current reporting month for their own branch.
        // Past months require Head Office.
        if ($request->user()?->isBranch() && ! $this->reports->isActiveReportingMonth($periodModel)) {
            return response()->json([
                'message' => 'Only Head Office can unlock past month reports.',
            ], 403);
        }

        $result = $this->reports->unlockBranch($period, $branch);

        return response()->json([
            'message' => 'Report unlocked. You can edit in workspace.',
            ...$result,
        ]);
    }

    public function extend(Request $request, int $period): JsonResponse
    {
        $validated = $request->validate([
            'days' => ['required', 'integer', 'min:1', 'max:90'],
            'branch' => ['nullable', 'string', 'max:150'],
        ]);

        $result = $this->reports->extendDeadline(
            $period,
            (int) $validated['days'],
            $validated['branch'] ?? null
        );

        $scope = ($result['scope'] ?? '') === 'branch'
            ? ($result['branch'] ?? 'branch')
            : 'all branches';

        return response()->json([
            'message' => "Deadline extended by {$result['days']} day(s) for {$scope}.",
            ...$result,
        ]);
    }

    public function branchHistory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'branch' => ['nullable', 'string', 'max:150'],
        ]);

        try {
            $branch = $this->resolveBranchName($request, $validated['branch'] ?? null);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        }

        if (! $branch) {
            return response()->json(['message' => 'Select a branch.'], 422);
        }

        return response()->json(
            $this->reports->branchHistory($branch)
        );
    }

    public function workspace(Request $request, int $period)
    {
        $validated = $request->validate([
            'branch' => ['required', 'string', 'max:150'],
        ]);

        try {
            $branch = $this->resolveBranchName($request, $validated['branch']);
        } catch (InvalidArgumentException $e) {
            abort(403, $e->getMessage());
        }

        $checklist = $this->reports->checklist($period, $branch);

        if (! $checklist['periodOpen']) {
            abort(403, 'This RCSA month is closed.');
        }

        if (($checklist['branchStatus'] ?? null) === 'submitted') {
            return redirect('/?tab=rcsa')->with('status', 'Unlock this report before editing. Past months need Head Office.');
        }

        $payload = app(DashboardStateService::class)->payload();
        $user = $request->user()->loadMissing('branch');

        return view('dashboard.rcsa-workspace', [
            'periodId' => $period,
            'branch' => $branch,
            'checklist' => $checklist,
            'initialState' => $payload['state'],
            'initialOptions' => $payload['options'],
            'authUser' => $user->toDashboardAuth(),
            'appName' => config('app.name', 'OpsControl'),
        ]);
    }
}
