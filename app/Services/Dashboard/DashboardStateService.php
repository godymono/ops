<?php

namespace App\Services\Dashboard;

use App\Models\Licence;
use App\Models\MaintenanceIssue;
use App\Models\RcsaAssessment;
use Illuminate\Support\Facades\DB;

class DashboardStateService
{
    public function __construct(
        private readonly DashboardLookupService $lookup,
        private readonly DashboardOptionsService $options,
    ) {}

    public function payload(): array
    {
        return [
            'state' => $this->state(),
            'options' => $this->options->all(),
        ];
    }

    public function state(): array
    {
        return [
            'maintenance' => $this->maintenanceRows(),
            'licences' => $this->licenceRows(),
            'rcsa' => $this->rcsaRows(),
        ];
    }

    public function persist(array $state): void
    {
        DB::transaction(function () use ($state) {
            $this->syncMaintenance($state['maintenance'] ?? []);
            $this->syncLicences($state['licences'] ?? []);
            $this->syncRcsa($state['rcsa'] ?? []);
        });
    }

    private function maintenanceRows()
    {
        return MaintenanceIssue::with(['branch:id,name', 'category:id,name', 'officer:id,name'])
            ->orderByDesc('id')
            ->get()
            ->map(fn (MaintenanceIssue $row) => [
                'id' => $row->id,
                'branch' => $row->branch?->name,
                'category' => $row->category?->name ?? '',
                'title' => $row->title,
                'priority' => $row->priority,
                'officer' => $row->officer?->name ?? '',
                'targetDate' => $row->target_date?->format('Y-m-d') ?? '',
                'status' => $row->status,
            ])
            ->values();
    }

    private function licenceRows()
    {
        return Licence::with(['branch:id,name', 'category:id,name'])
            ->orderByDesc('id')
            ->get()
            ->map(fn (Licence $row) => [
                'id' => $row->id,
                'branch' => $row->branch?->name,
                'category' => $row->category?->name ?? '',
                'title' => $row->title,
                'authority' => $row->authority ?? '',
                'expiryDate' => $row->expiry_date?->format('Y-m-d') ?? '',
                'status' => $row->status,
            ])
            ->values();
    }

    private function rcsaRows()
    {
        return RcsaAssessment::with(['branch:id,name', 'template.category:id,name', 'template:id,category_id,item'])
            ->orderBy('id')
            ->get()
            ->map(fn (RcsaAssessment $row) => [
                'id' => $row->id,
                'branch' => $row->branch?->name,
                'category' => $row->template?->category?->name ?? '',
                'item' => $row->template?->item ?? '',
                'status' => $row->status,
                'remarks' => $row->remarks ?? '',
                'actionPlan' => $row->action_plan ?? '',
                'targetDate' => $row->target_date?->format('Y-m-d') ?? '',
            ])
            ->values();
    }

    private function syncMaintenance(array $items): void
    {
        $ids = [];

        foreach ($items as $item) {
            $id = (int) $item['id'];
            $ids[] = $id;

            MaintenanceIssue::updateOrCreate(['id' => $id], [
                'branch_id' => $this->lookup->branchId($item['branch']),
                'category_id' => $this->lookup->categoryId($item['category'] ?? '', 'maintenance'),
                'title' => $item['title'],
                'priority' => $item['priority'] ?? 'Medium',
                'officer_id' => $this->lookup->officerId($item['officer'] ?? ''),
                'target_date' => $item['targetDate'] ?: null,
                'status' => $item['status'],
            ]);
        }

        $query = MaintenanceIssue::query();
        $ids === [] ? $query->delete() : $query->whereNotIn('id', $ids)->delete();
    }

    private function syncLicences(array $items): void
    {
        $ids = [];

        foreach ($items as $item) {
            $id = (int) $item['id'];
            $ids[] = $id;

            Licence::updateOrCreate(['id' => $id], [
                'branch_id' => $this->lookup->branchId($item['branch']),
                'category_id' => $this->lookup->categoryId($item['category'] ?? '', 'licences'),
                'title' => $item['title'],
                'authority' => $item['authority'] ?? '',
                'expiry_date' => $item['expiryDate'] ?: null,
                'status' => $item['status'],
            ]);
        }

        $query = Licence::query();
        $ids === [] ? $query->delete() : $query->whereNotIn('id', $ids)->delete();
    }

    private function syncRcsa(array $items): void
    {
        $ids = [];

        foreach ($items as $item) {
            $id = (int) $item['id'];
            $ids[] = $id;

            RcsaAssessment::updateOrCreate(['id' => $id], [
                'branch_id' => $this->lookup->branchId($item['branch']),
                'rcsa_template_id' => $this->lookup->resolveRcsaTemplateId($item['category'], $item['item']),
                'status' => $item['status'],
                'remarks' => $item['remarks'] ?? '',
                'action_plan' => $item['actionPlan'] ?? '',
                'target_date' => $item['targetDate'] ?: null,
            ]);
        }

        $query = RcsaAssessment::query();
        $ids === [] ? $query->delete() : $query->whereNotIn('id', $ids)->delete();
    }
}
