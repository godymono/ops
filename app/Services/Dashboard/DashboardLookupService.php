<?php

namespace App\Services\Dashboard;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Officer;
use App\Models\RcsaTemplate;
use Illuminate\Support\Facades\DB;

class DashboardLookupService
{
    public function branchId(string $name): int
    {
        return Branch::firstOrCreate(
            ['name' => trim($name)],
            ['is_active' => true]
        )->id;
    }

    public function categoryId(string $name, string $module): ?int
    {
        $name = trim($name);
        if ($name === '') {
            return null;
        }

        return Category::firstOrCreate(
            ['module' => $module, 'name' => $name],
            ['is_active' => true]
        )->id;
    }

    public function officerId(string $name): ?int
    {
        $name = trim($name);
        if ($name === '') {
            return null;
        }

        return Officer::firstOrCreate(
            ['name' => $name],
            ['is_active' => true]
        )->id;
    }

    public function resolveRcsaTemplateId(string $category, string $item): int
    {
        $categoryId = $this->categoryId($category, 'rcsa');

        $template = RcsaTemplate::query()
            ->where('category_id', $categoryId)
            ->where('item', $item)
            ->first();

        if ($template) {
            return $template->id;
        }

        return (int) DB::table('rcsa_templates')->insertGetId([
            'category_id' => $categoryId,
            'item' => $item,
            'sort_order' => 999999,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
