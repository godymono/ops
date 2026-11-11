<?php

namespace App\Services\Dashboard;

use App\Models\Licence;
use App\Models\MaintenanceIssue;
use App\Models\RcsaAssessment;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class DashboardRecordService
{
    public function __construct(
        private readonly DashboardLookupService $lookup,
        private readonly DashboardStateService $state,
    ) {}

    public function create(string $module, array $payload): array
    {
        return DB::transaction(function () use ($module, $payload) {
            $record = match ($module) {
                'maintenance' => $this->createMaintenance($payload),
                'licences' => $this->createLicence($payload),
                'rcsa' => $this->createRcsa($payload),
                default => throw new InvalidArgumentException('Unknown module.'),
            };

            return $this->findMapped($module, $record->id);
        });
    }

    public function update(string $module, int $id, array $payload): array
    {
        return DB::transaction(function () use ($module, $id, $payload) {
            match ($module) {
                'maintenance' => $this->updateMaintenance($id, $payload),
                'licences' => $this->updateLicence($id, $payload),
                'rcsa' => $this->updateRcsa($id, $payload),
                default => throw new InvalidArgumentException('Unknown module.'),
            };

            return $this->findMapped($module, $id);
        });
    }

    public function updateStatus(string $module, int $id, string $status): array
    {
        return DB::transaction(function () use ($module, $id, $status) {
            $model = $this->model($module)::query()->findOrFail($id);
            $model->status = $status;
            $model->save();

            return $this->findMapped($module, $id);
        });
    }

    public function delete(string $module, int $id): void
    {
        $this->model($module)::query()->whereKey($id)->delete();
    }

    private function createMaintenance(array $payload): MaintenanceIssue
    {
        return MaintenanceIssue::create([
            'branch_id' => $this->lookup->branchId($payload['branch']),
            'category_id' => $this->lookup->categoryId($payload['category'] ?? '', 'maintenance'),
            'title' => $payload['title'],
            'priority' => $payload['priority'] ?? 'Medium',
            'officer_id' => $this->lookup->officerId($payload['officer'] ?? ''),
            'target_date' => $payload['targetDate'] ?: null,
            'status' => $payload['status'] ?? 'Open',
        ]);
    }

    private function updateMaintenance(int $id, array $payload): void
    {
        $row = MaintenanceIssue::query()->findOrFail($id);
        $row->fill([
            'branch_id' => $this->lookup->branchId($payload['branch']),
            'category_id' => $this->lookup->categoryId($payload['category'] ?? '', 'maintenance'),
            'title' => $payload['title'],
            'priority' => $payload['priority'] ?? 'Medium',
            'officer_id' => $this->lookup->officerId($payload['officer'] ?? ''),
            'target_date' => $payload['targetDate'] ?: null,
            'status' => $payload['status'] ?? 'Open',
        ])->save();
    }

    private function createLicence(array $payload): Licence
    {
        return Licence::create([
            'branch_id' => $this->lookup->branchId($payload['branch']),
            'category_id' => $this->lookup->categoryId($payload['category'] ?? '', 'licences'),
            'title' => $payload['title'],
            'authority' => $payload['authority'] ?? '',
            'expiry_date' => $payload['expiryDate'] ?: null,
            'status' => $payload['status'] ?? 'Valid',
        ]);
    }

    private function updateLicence(int $id, array $payload): void
    {
        $row = Licence::query()->findOrFail($id);
        $row->fill([
            'branch_id' => $this->lookup->branchId($payload['branch']),
            'category_id' => $this->lookup->categoryId($payload['category'] ?? '', 'licences'),
            'title' => $payload['title'],
            'authority' => $payload['authority'] ?? '',
            'expiry_date' => $payload['expiryDate'] ?: null,
            'status' => $payload['status'] ?? 'Valid',
        ])->save();
    }

    private function createRcsa(array $payload): RcsaAssessment
    {
        return RcsaAssessment::create([
            'branch_id' => $this->lookup->branchId($payload['branch']),
            'rcsa_template_id' => $this->lookup->resolveRcsaTemplateId(
                $payload['category'],
                $payload['item'] ?? $payload['title'] ?? ''
            ),
            'status' => $payload['status'] ?? 'N/E',
            'remarks' => $payload['remarks'] ?? '',
            'action_plan' => $payload['actionPlan'] ?? '',
            'target_date' => $payload['targetDate'] ?: null,
        ]);
    }

    private function updateRcsa(int $id, array $payload): void
    {
        $row = RcsaAssessment::query()->findOrFail($id);
        $row->fill([
            'branch_id' => $this->lookup->branchId($payload['branch']),
            'rcsa_template_id' => $this->lookup->resolveRcsaTemplateId(
                $payload['category'],
                $payload['item'] ?? $payload['title'] ?? ''
            ),
            'status' => $payload['status'] ?? 'N/E',
            'remarks' => $payload['remarks'] ?? '',
            'action_plan' => $payload['actionPlan'] ?? '',
            'target_date' => $payload['targetDate'] ?: null,
        ])->save();
    }

    private function findMapped(string $module, int $id): array
    {
        $state = $this->state->state();
        $row = collect($state[$module])->firstWhere('id', $id);

        if (!$row) {
            throw new InvalidArgumentException('Record not found after save.');
        }

        return $row;
    }

    private function model(string $module): string
    {
        return match ($module) {
            'maintenance' => MaintenanceIssue::class,
            'licences' => Licence::class,
            'rcsa' => RcsaAssessment::class,
            default => throw new InvalidArgumentException('Unknown module.'),
        };
    }
}
