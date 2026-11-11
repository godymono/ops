<?php

namespace App\Services\Dashboard;

use App\Models\Branch;
use App\Models\Category;
use App\Models\DashboardOption;
use App\Models\Officer;

class DashboardOptionsService
{
    public function all(): array
    {
        return [
            'branches' => Branch::query()->where('is_active', true)->orderBy('name')->pluck('name')->values(),
            'categories' => Category::query()->where('is_active', true)->orderBy('name')->pluck('name')->unique()->values(),
            'maintenanceCategories' => Category::query()
                ->where('is_active', true)
                ->whereIn('module', ['maintenance', 'general'])
                ->orderBy('name')
                ->pluck('name')
                ->values(),
            'licenceCategories' => Category::query()
                ->where('is_active', true)
                ->whereIn('module', ['licences', 'general'])
                ->orderBy('name')
                ->pluck('name')
                ->values(),
            'rcsaCategories' => Category::query()
                ->where('is_active', true)
                ->where('module', 'rcsa')
                ->orderBy('name')
                ->pluck('name')
                ->values(),
            'officers' => Officer::query()->where('is_active', true)->orderBy('name')->pluck('name')->values(),
            'statuses' => DashboardOption::query()
                ->where('group_key', 'status')
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->pluck('option_value')
                ->values(),
            'priorities' => DashboardOption::query()
                ->where('group_key', 'priority')
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->pluck('option_value')
                ->values(),
            'sessions' => ['HQ', ...Branch::query()->where('is_active', true)->orderBy('name')->pluck('name')->all()],
        ];
    }
}
