<?php

namespace App\Services\Dashboard;

use App\Models\Branch;
use App\Models\RcsaAssessment;
use App\Models\RcsaPeriod;
use App\Models\RcsaSubmission;
use App\Models\RcsaSubmissionItem;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class RcsaMonthlyReportService
{
    public const DEADLINE_DAY = 10;

    public function ensureCurrentPeriod(): RcsaPeriod
    {
        return $this->ensureActivePeriod();
    }

    public function ensureActivePeriod(?Carbon $now = null): RcsaPeriod
    {
        $target = $this->activePeriodDate($now);

        return $this->ensurePeriod($target->year, $target->month);
    }

    public function ensurePeriod(int $year, int $month): RcsaPeriod
    {
        $start = Carbon::create($year, $month, 1)->startOfDay();
        $deadline = $this->deadlineFor($year, $month);

        $period = RcsaPeriod::firstOrCreate(
            ['year' => $year, 'month' => $month],
            [
                'label' => $start->format('F Y'),
                'starts_on' => $start->toDateString(),
                'ends_on' => $start->copy()->endOfMonth()->toDateString(),
                'is_open' => true,
            ]
        );

        $expectedLabel = $start->format('F Y');
        $expectedStart = $start->toDateString();
        $expectedEnd = $start->copy()->endOfMonth()->toDateString();
        $shouldBeOpen = $this->isPeriodOpen($period);
        $dirty = false;

        if ($period->label !== $expectedLabel) {
            $period->label = $expectedLabel;
            $dirty = true;
        }
        if ($period->starts_on?->format('Y-m-d') !== $expectedStart) {
            $period->starts_on = $expectedStart;
            $dirty = true;
        }
        if ($period->ends_on?->format('Y-m-d') !== $expectedEnd) {
            $period->ends_on = $expectedEnd;
            $dirty = true;
        }
        if ((bool) $period->is_open !== $shouldBeOpen) {
            $period->is_open = $shouldBeOpen;
            $dirty = true;
        }
        if ($dirty) {
            $period->save();
        }

        $this->syncBranchPlaceholders($period);

        return $period->fresh();
    }

    public function periods(): Collection
    {
        $this->ensureActivePeriod();
        $this->ensureNearbyPeriods();

        return RcsaPeriod::query()
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get()
            ->map(fn (RcsaPeriod $period) => $this->periodSummary($period));
    }

    public function periodDetail(int $periodId): array
    {
        $period = RcsaPeriod::query()->findOrFail($periodId);
        $this->syncBranchPlaceholders($period);

        $branches = $this->branchRows($period);

        return [
            'period' => $this->periodSummary($period, $branches),
            'branches' => $branches,
            'missing' => $branches->where('status', 'pending')->values(),
            'submitted' => $branches->where('status', 'submitted')->values(),
            'unlocked' => $branches->where('status', 'unlocked')->values(),
        ];
    }

    public function checklist(int $periodId, ?string $branchName = null): array
    {
        $period = RcsaPeriod::query()->findOrFail($periodId);
        $this->syncBranchPlaceholders($period);
        $summary = $this->periodSummary($period);
        $branches = $this->branchRows($period);
        $branchRow = null;

        if ($branchName && $branchName !== 'HQ' && $branchName !== 'ALL') {
            $branchRow = $branches->firstWhere('branch', $branchName);
        }

        $periodOpen = $branchRow
            ? $this->isOpenForBranch($period, $branchRow['branch'])
            : $this->isPeriodOpen($period);

        $branchStatus = $branchRow['status'] ?? 'pending';
        $canEdit = $periodOpen && in_array($branchStatus, ['pending', 'unlocked'], true);
        $useLive = $periodOpen && in_array($branchStatus, ['pending', 'unlocked'], true);

        if ($useLive || ($periodOpen && !$branchRow)) {
            $items = $this->liveItems($branchName);
            $source = 'live';
        } else {
            $items = $this->snapshotItems($period, $branchName);
            $source = 'snapshot';
        }

        // HQ overview without branch: monitoring only.
        if (!$branchName || $branchName === 'HQ' || $branchName === 'ALL') {
            $canEdit = false;
            $items = $periodOpen ? $this->liveItems(null) : $this->snapshotItems($period, null);
            $source = $periodOpen ? 'live' : 'snapshot';
            $branchStatus = 'overview';
            $periodOpen = $this->isPeriodOpen($period);
        }

        $deadline = $branchRow
            ? Carbon::parse($branchRow['deadline'])
            : $this->effectiveDeadline($period);
        $now = Carbon::now()->startOfDay();

        return [
            'period' => $summary,
            'editable' => $canEdit,
            'periodOpen' => $periodOpen,
            'source' => $source,
            'deadline' => $deadline->format('Y-m-d'),
            'deadlineLabel' => $deadline->format('j M Y'),
            'daysRemaining' => (int) $now->diffInDays($deadline, false),
            'isActive' => $summary['isActive'],
            'branch' => $branchRow['branch'] ?? ($branchName && !in_array($branchName, ['HQ', 'ALL'], true) ? $branchName : null),
            'branchStatus' => $branchStatus,
            'branches' => $branches->values(),
            'workspaceUrl' => ($branchRow && $periodOpen)
                ? url('/rcsa/workspace/'.$period->id.'?branch='.urlencode($branchRow['branch']))
                : null,
            'items' => $items,
            'canUnlock' => $periodOpen
                && $branchStatus === 'submitted'
                && ($summary['isActive'] || false),
            'canHqUnlockPast' => $periodOpen && $branchStatus === 'submitted' && ! ($summary['isActive'] ?? false),
            'message' => $this->checklistMessage(
                $periodOpen,
                $branchStatus,
                $branchRow['extended'] ?? false,
                $summary['isActive'] ?? false
            ),
            'roleHints' => [
                'hq' => 'Monitor submissions, unlock access, and extend deadlines.',
                'branch' => 'Submit current month. Unlock to edit. Past months need Head Office unlock.',
            ],
        ];
    }

    public function unlockBranch(int $periodId, string $branchName): array
    {
        $period = $this->assertPeriodOpenForBranch($periodId, $branchName);
        $branch = Branch::query()->where('name', $branchName)->where('is_active', true)->firstOrFail();

        $submission = RcsaSubmission::query()
            ->where('rcsa_period_id', $period->id)
            ->where('branch_id', $branch->id)
            ->firstOrFail();

        if ($submission->status !== 'submitted') {
            throw new InvalidArgumentException('Only a submitted report can be unlocked.');
        }

        $submission->status = 'unlocked';
        $submission->save();

        return [
            'submission' => $this->mapSubmission($submission->fresh(['branch', 'period'])),
            'period' => $this->periodDetail($period->id),
            'workspaceUrl' => url('/rcsa/workspace/'.$period->id.'?branch='.urlencode($branch->name)),
        ];
    }

    public function extendDeadline(int $periodId, int $days, ?string $branchName = null): array
    {
        if ($days < 1 || $days > 90) {
            throw new InvalidArgumentException('Extend by 1–90 days.');
        }

        $period = RcsaPeriod::query()->findOrFail($periodId);
        $this->syncBranchPlaceholders($period);
        $now = Carbon::now()->startOfDay();

        if ($branchName && $branchName !== 'HQ' && $branchName !== 'ALL') {
            $branch = Branch::query()->where('name', $branchName)->where('is_active', true)->firstOrFail();
            $submission = RcsaSubmission::query()
                ->where('rcsa_period_id', $period->id)
                ->where('branch_id', $branch->id)
                ->firstOrFail();

            $current = $this->effectiveDeadline($period, $submission);
            $from = $current->greaterThan($now) ? $current : $now;
            $submission->deadline_extended_to = $from->copy()->addDays($days)->toDateString();
            $submission->save();

            return [
                'scope' => 'branch',
                'branch' => $branch->name,
                'days' => $days,
                'deadline' => $submission->deadline_extended_to,
                'deadlineLabel' => Carbon::parse($submission->deadline_extended_to)->format('j M Y'),
                'period' => $this->periodDetail($period->id),
            ];
        }

        $current = $this->effectiveDeadline($period);
        $from = $current->greaterThan($now) ? $current : $now;
        $period->deadline_extended_to = $from->copy()->addDays($days)->toDateString();
        $period->is_open = true;
        $period->save();

        return [
            'scope' => 'all',
            'branch' => null,
            'days' => $days,
            'deadline' => $period->deadline_extended_to,
            'deadlineLabel' => Carbon::parse($period->deadline_extended_to)->format('j M Y'),
            'period' => $this->periodDetail($period->id),
        ];
    }

    public function branchHistory(string $branchName): array
    {
        $branch = Branch::query()->where('name', $branchName)->where('is_active', true)->firstOrFail();
        $this->ensureNearbyPeriods();

        $rows = RcsaSubmission::with('period')
            ->where('branch_id', $branch->id)
            ->get()
            ->sortByDesc(fn (RcsaSubmission $row) => sprintf('%04d-%02d', $row->period?->year, $row->period?->month))
            ->values()
            ->map(function (RcsaSubmission $row) use ($branch) {
                $period = $row->period;
                $open = $period ? $this->isOpenForBranch($period, $branch->name) : false;
                $isActive = $period ? $this->isActiveReportingMonth($period) : false;

                return [
                    'id' => $row->id,
                    'periodId' => $row->rcsa_period_id,
                    'period' => $period?->label,
                    'year' => $period?->year,
                    'month' => $period?->month,
                    'status' => $row->status,
                    'submittedAt' => $row->submitted_at?->format('Y-m-d H:i'),
                    'deadline' => $period ? $this->effectiveDeadline($period, $row)->format('Y-m-d') : null,
                    'deadlineLabel' => $period ? $this->effectiveDeadline($period, $row)->format('j M Y') : null,
                    'open' => $open,
                    'isActive' => $isActive,
                    'canUnlock' => $open && $isActive && $row->status === 'submitted',
                    'total' => $row->total_count,
                    'yes' => $row->yes_count,
                    'no' => $row->no_count,
                    'rate' => $row->total_count > 0
                        ? (int) round(($row->yes_count / $row->total_count) * 100)
                        : 0,
                    'workspaceUrl' => $open
                        ? url('/rcsa/workspace/'.$row->rcsa_period_id.'?branch='.urlencode($branch->name))
                        : null,
                ];
            });

        return [
            'branch' => $branch->name,
            'history' => $rows,
        ];
    }

    public function assertPeriodOpenForBranch(int $periodId, string $branchName): RcsaPeriod
    {
        $period = RcsaPeriod::query()->findOrFail($periodId);
        if (!$this->isOpenForBranch($period, $branchName)) {
            throw new InvalidArgumentException('This RCSA month is closed for this branch. Ask Head Office to extend the deadline.');
        }

        return $period;
    }

    public function assertActiveEditable(?string $branchName = null): RcsaPeriod
    {
        if ($branchName && $branchName !== 'HQ') {
            return $this->openEditablePeriodForBranch($branchName);
        }

        $period = $this->ensureActivePeriod();
        if (!$this->isPeriodOpen($period)) {
            throw new InvalidArgumentException('RCSA editing is closed for now.');
        }

        return $period;
    }

    public function openEditablePeriodForBranch(string $branchName): RcsaPeriod
    {
        $this->ensureNearbyPeriods();
        $branch = Branch::query()->where('name', $branchName)->where('is_active', true)->firstOrFail();

        $periods = RcsaPeriod::query()
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->get();

        foreach ($periods as $period) {
            if (!$this->isOpenForBranch($period, $branchName)) {
                continue;
            }

            $submission = RcsaSubmission::query()
                ->where('rcsa_period_id', $period->id)
                ->where('branch_id', $branch->id)
                ->first();

            $status = $submission?->status ?? 'pending';
            if (in_array($status, ['pending', 'unlocked'], true)) {
                return $period;
            }
        }

        throw new InvalidArgumentException('RCSA editing is closed for this branch. Ask Head Office to unlock or extend the deadline.');
    }

    public function assertCanEditBranch(?string $branchName = null, ?int $assessmentId = null): void
    {
        if (!$branchName && $assessmentId) {
            $assessment = RcsaAssessment::with('branch:id,name')->findOrFail($assessmentId);
            $branchName = $assessment->branch?->name;
        }

        if (!$branchName) {
            throw new InvalidArgumentException('Select a branch before editing RCSA.');
        }

        $this->openEditablePeriodForBranch($branchName);
    }

    private function checklistMessage(bool $periodOpen, string $branchStatus, bool $extended = false, bool $isActive = false): string
    {
        if (!$periodOpen) {
            return 'Past report — read only.';
        }

        $suffix = $extended ? ' (deadline extended)' : '';

        return match ($branchStatus) {
            'submitted' => $isActive
                ? 'Submitted — unlock to edit in workspace, then submit again.'.$suffix
                : 'Submitted past month — only Head Office can unlock.'.$suffix,
            'unlocked' => 'Unlocked — open workspace, edit, then submit again.'.$suffix,
            'overview' => 'Head Office view — pick a branch to unlock or extend.',
            default => 'Draft — complete checklist, then submit.'.$suffix,
        };
    }

    public function submitBranch(int $periodId, string $branchName, ?string $submittedBy = null, ?string $notes = null): array
    {
        $period = $this->assertPeriodOpenForBranch($periodId, $branchName);
        $branch = Branch::query()->where('name', $branchName)->where('is_active', true)->firstOrFail();

        return DB::transaction(function () use ($period, $branch, $submittedBy, $notes) {
            $submission = RcsaSubmission::firstOrCreate(
                [
                    'rcsa_period_id' => $period->id,
                    'branch_id' => $branch->id,
                ],
                ['status' => 'pending']
            );

            $assessments = RcsaAssessment::with(['template.category'])
                ->where('branch_id', $branch->id)
                ->orderBy('id')
                ->get();

            if ($assessments->isEmpty()) {
                throw new InvalidArgumentException('No RCSA items found for this branch.');
            }

            RcsaSubmissionItem::query()->where('rcsa_submission_id', $submission->id)->delete();

            $rows = [];
            foreach ($assessments as $index => $assessment) {
                $rows[] = [
                    'rcsa_submission_id' => $submission->id,
                    'category' => $assessment->template?->category?->name,
                    'item' => $assessment->template?->item ?? '',
                    'status' => $assessment->status,
                    'remarks' => $assessment->remarks,
                    'action_plan' => $assessment->action_plan,
                    'target_date' => $assessment->target_date?->format('Y-m-d'),
                    'sort_order' => $index + 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
            RcsaSubmissionItem::query()->insert($rows);

            $counts = [
                'total_count' => $assessments->count(),
                'yes_count' => $assessments->where('status', 'YES')->count(),
                'no_count' => $assessments->where('status', 'NO')->count(),
                'na_count' => $assessments->where('status', 'N/A')->count(),
                'ne_count' => $assessments->where('status', 'N/E')->count(),
            ];

            $submission->fill([
                ...$counts,
                'status' => 'submitted',
                'submitted_at' => now(),
                'submitted_by' => $submittedBy ?: 'Branch User',
                'notes' => $notes,
            ])->save();

            return $this->mapSubmission($submission->fresh(['branch', 'period', 'items']));
        });
    }

    public function submissionDetail(int $submissionId): array
    {
        $submission = RcsaSubmission::with(['branch', 'period', 'items' => fn ($q) => $q->orderBy('sort_order')])
            ->findOrFail($submissionId);

        return $this->mapSubmission($submission, true);
    }

    public function openOrCreatePeriod(?int $year = null, ?int $month = null): array
    {
        if ($year && $month) {
            $period = $this->ensurePeriod($year, $month);
        } else {
            $period = $this->ensureActivePeriod();
        }

        return $this->periodDetail($period->id);
    }

    public function isPeriodEditable(RcsaPeriod $period, ?Carbon $now = null): bool
    {
        return $this->isActiveReportingMonth($period, $now);
    }

    public function isActiveReportingMonth(RcsaPeriod $period, ?Carbon $now = null): bool
    {
        $now = ($now ?: Carbon::now())->startOfDay();
        $active = $this->activePeriodDate($now);

        return (int) $period->year === (int) $active->year
            && (int) $period->month === (int) $active->month;
    }

    public function isPeriodOpen(RcsaPeriod $period, ?Carbon $now = null): bool
    {
        $now = ($now ?: Carbon::now())->startOfDay();
        $deadline = $this->effectiveDeadline($period);

        return $now->lte($deadline);
    }

    public function isOpenForBranch(RcsaPeriod $period, string $branchName, ?Carbon $now = null): bool
    {
        $now = ($now ?: Carbon::now())->startOfDay();
        $branch = Branch::query()->where('name', $branchName)->first();
        if (!$branch) {
            return false;
        }

        $submission = RcsaSubmission::query()
            ->where('rcsa_period_id', $period->id)
            ->where('branch_id', $branch->id)
            ->first();

        return $now->lte($this->effectiveDeadline($period, $submission));
    }

    public function deadlineFor(int $year, int $month): Carbon
    {
        return Carbon::create($year, $month, 1)->addMonth()->day(self::DEADLINE_DAY)->startOfDay();
    }

    public function effectiveDeadline(RcsaPeriod $period, ?RcsaSubmission $submission = null): Carbon
    {
        $deadline = $this->deadlineFor($period->year, $period->month);

        if ($period->deadline_extended_to) {
            $extended = Carbon::parse($period->deadline_extended_to)->startOfDay();
            if ($extended->greaterThan($deadline)) {
                $deadline = $extended;
            }
        }

        if ($submission?->deadline_extended_to) {
            $branchExtended = Carbon::parse($submission->deadline_extended_to)->startOfDay();
            if ($branchExtended->greaterThan($deadline)) {
                $deadline = $branchExtended;
            }
        }

        return $deadline;
    }

    private function activePeriodDate(?Carbon $now = null): Carbon
    {
        $now = ($now ?: Carbon::now())->startOfDay();

        // Until the 10th, the previous month remains the fillable report month.
        if ($now->day <= self::DEADLINE_DAY) {
            return $now->copy()->subMonthNoOverflow()->startOfMonth();
        }

        return $now->copy()->startOfMonth();
    }

    private function ensureNearbyPeriods(): void
    {
        $active = $this->activePeriodDate();
        $this->ensurePeriod($active->year, $active->month);
        $prev = $active->copy()->subMonthNoOverflow();
        $this->ensurePeriod($prev->year, $prev->month);
        $next = $active->copy()->addMonthNoOverflow();
        $this->ensurePeriod($next->year, $next->month);
    }

    private function liveItems(?string $branchName = null): array
    {
        $query = RcsaAssessment::with(['branch:id,name', 'template.category:id,name', 'template:id,category_id,item'])
            ->orderBy('id');

        if ($branchName && $branchName !== 'HQ' && $branchName !== 'ALL') {
            $query->whereHas('branch', fn ($q) => $q->where('name', $branchName));
        }

        return $query->get()->map(fn (RcsaAssessment $row) => [
            'id' => $row->id,
            'branch' => $row->branch?->name,
            'category' => $row->template?->category?->name ?? '',
            'item' => $row->template?->item ?? '',
            'status' => $row->status,
            'remarks' => $row->remarks ?? '',
            'actionPlan' => $row->action_plan ?? '',
            'targetDate' => $row->target_date?->format('Y-m-d') ?? '',
        ])->values()->all();
    }

    private function snapshotItems(RcsaPeriod $period, ?string $branchName = null): array
    {
        $query = RcsaSubmission::with(['branch:id,name', 'items' => fn ($q) => $q->orderBy('sort_order')])
            ->where('rcsa_period_id', $period->id)
            ->where('status', 'submitted');

        if ($branchName && $branchName !== 'HQ' && $branchName !== 'ALL') {
            $query->whereHas('branch', fn ($q) => $q->where('name', $branchName));
        }

        $items = [];
        foreach ($query->get() as $submission) {
            foreach ($submission->items as $item) {
                $items[] = [
                    'id' => $item->id,
                    'branch' => $submission->branch?->name,
                    'category' => $item->category ?? '',
                    'item' => $item->item,
                    'status' => $item->status,
                    'remarks' => $item->remarks ?? '',
                    'actionPlan' => $item->action_plan ?? '',
                    'targetDate' => $item->target_date?->format('Y-m-d') ?? '',
                    'submissionId' => $submission->id,
                ];
            }
        }

        return $items;
    }

    private function syncBranchPlaceholders(RcsaPeriod $period): void
    {
        $branchIds = Branch::query()->where('is_active', true)->pluck('id');

        foreach ($branchIds as $branchId) {
            RcsaSubmission::firstOrCreate(
                [
                    'rcsa_period_id' => $period->id,
                    'branch_id' => $branchId,
                ],
                ['status' => 'pending']
            );
        }
    }

    private function periodSummary(RcsaPeriod $period, ?Collection $branches = null): array
    {
        $branches ??= $this->branchRows($period);
        $submitted = $branches->where('status', 'submitted')->count();
        $pending = $branches->where('status', 'pending')->count();
        $unlocked = $branches->where('status', 'unlocked')->count();
        $deadline = $this->effectiveDeadline($period);
        $baseDeadline = $this->deadlineFor($period->year, $period->month);
        $now = Carbon::now()->startOfDay();
        $isActive = $this->isActiveReportingMonth($period, $now);
        $isOpen = $this->isPeriodOpen($period, $now);
        $daysRemaining = $now->diffInDays($deadline, false);

        return [
            'id' => $period->id,
            'year' => $period->year,
            'month' => $period->month,
            'label' => $period->label,
            'startsOn' => $period->starts_on?->format('Y-m-d'),
            'endsOn' => $period->ends_on?->format('Y-m-d'),
            'deadline' => $deadline->format('Y-m-d'),
            'deadlineLabel' => $deadline->format('j M Y'),
            'baseDeadline' => $baseDeadline->format('Y-m-d'),
            'baseDeadlineLabel' => $baseDeadline->format('j M Y'),
            'extended' => $deadline->greaterThan($baseDeadline),
            'daysRemaining' => (int) $daysRemaining,
            'isOpen' => $isOpen,
            'isActive' => $isActive,
            'editable' => $isOpen,
            'totalBranches' => $branches->count(),
            'submittedCount' => $submitted,
            'pendingCount' => $pending,
            'unlockedCount' => $unlocked,
            'completionRate' => $branches->count() > 0
                ? (int) round(($submitted / $branches->count()) * 100)
                : 0,
        ];
    }

    private function branchRows(RcsaPeriod $period): Collection
    {
        $baseDeadline = $this->deadlineFor($period->year, $period->month);

        return RcsaSubmission::with('branch:id,name')
            ->where('rcsa_period_id', $period->id)
            ->get()
            ->sortBy(fn (RcsaSubmission $row) => $row->branch?->name)
            ->values()
            ->map(function (RcsaSubmission $row) use ($period, $baseDeadline) {
                $deadline = $this->effectiveDeadline($period, $row);
                $open = Carbon::now()->startOfDay()->lte($deadline);
                $late = $row->status === 'submitted'
                    && $row->submitted_at
                    && $row->submitted_at->gt($baseDeadline->copy()->endOfDay());

                return [
                    'id' => $row->id,
                    'branch' => $row->branch?->name,
                    'status' => $row->status,
                    'submittedAt' => $row->submitted_at?->format('Y-m-d H:i'),
                    'submittedBy' => $row->submitted_by,
                    'late' => $late,
                    'open' => $open,
                    'deadline' => $deadline->format('Y-m-d'),
                    'deadlineLabel' => $deadline->format('j M Y'),
                    'extended' => $deadline->greaterThan($baseDeadline),
                    'total' => $row->total_count,
                    'yes' => $row->yes_count,
                    'no' => $row->no_count,
                    'na' => $row->na_count,
                    'ne' => $row->ne_count,
                    'rate' => $row->total_count > 0
                        ? (int) round(($row->yes_count / $row->total_count) * 100)
                        : 0,
                ];
            });
    }

    private function mapSubmission(RcsaSubmission $submission, bool $withItems = false): array
    {
        $payload = [
            'id' => $submission->id,
            'periodId' => $submission->rcsa_period_id,
            'period' => $submission->period?->label,
            'branch' => $submission->branch?->name,
            'status' => $submission->status,
            'submittedAt' => $submission->submitted_at?->format('Y-m-d H:i'),
            'submittedBy' => $submission->submitted_by,
            'notes' => $submission->notes,
            'total' => $submission->total_count,
            'yes' => $submission->yes_count,
            'no' => $submission->no_count,
            'na' => $submission->na_count,
            'ne' => $submission->ne_count,
            'rate' => $submission->total_count > 0
                ? (int) round(($submission->yes_count / $submission->total_count) * 100)
                : 0,
        ];

        if ($withItems) {
            $payload['items'] = $submission->items->map(fn (RcsaSubmissionItem $item) => [
                'id' => $item->id,
                'category' => $item->category,
                'item' => $item->item,
                'status' => $item->status,
                'remarks' => $item->remarks,
                'actionPlan' => $item->action_plan,
                'targetDate' => $item->target_date?->format('Y-m-d'),
            ])->values();
        }

        return $payload;
    }
}
