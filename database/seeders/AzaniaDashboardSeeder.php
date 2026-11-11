<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Category;
use App\Models\DashboardOption;
use App\Models\Licence;
use App\Models\MaintenanceIssue;
use App\Models\Officer;
use App\Models\RcsaAssessment;
use App\Models\RcsaTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AzaniaDashboardSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $branches = [
                'Pemba Branch', 'Sabasaba Service Center', 'Main Branch Dar',
                'Arusha Branch', 'Mwanza Branch',
            ];
            foreach ($branches as $name) Branch::firstOrCreate(['name' => $name], ['is_active' => true]);

            $maintenance = $this->json('maintenance.json');
            $licences = $this->json('licences.json');
            $questions = $this->json('rcsa_templates.json');

            $maintenanceCategories = array_unique(array_column($maintenance, 'category'));
            $licenceCategories = array_unique(array_column($licences, 'category'));
            $rcsaCategories = array_unique(array_column($questions, 'category'));

            foreach ($maintenanceCategories as $name) Category::firstOrCreate(['module' => 'maintenance', 'name' => $name], ['is_active' => true]);
            foreach ($licenceCategories as $name) Category::firstOrCreate(['module' => 'licences', 'name' => $name], ['is_active' => true]);
            foreach ($rcsaCategories as $name) Category::firstOrCreate(['module' => 'rcsa', 'name' => $name], ['is_active' => true]);

            $officers = [
                'Security Manager', 'Operations Control Manager', 'Legal Manager', 'SMRB',
            ];
            foreach ($officers as $name) Officer::firstOrCreate(['name' => $name], ['is_active' => true]);

            foreach ([
                'status' => ['Open', 'In Progress', 'Resolved', 'Valid', 'Renewal Pending', 'Expired', 'YES', 'NO', 'N/A', 'N/E'],
                'priority' => ['High', 'Medium', 'Low'],
            ] as $group => $values) {
                foreach ($values as $index => $value) {
                    DashboardOption::firstOrCreate(
                        ['group_key' => $group, 'option_value' => $value],
                        ['label' => $value, 'sort_order' => $index + 1, 'is_active' => true]
                    );
                }
            }

            foreach ($maintenance as $row) {
                $branch = Branch::firstOrCreate(['name' => $row['branch']]);
                $category = Category::firstOrCreate(['module' => 'maintenance', 'name' => $row['category']]);
                $officer = Officer::firstOrCreate(['name' => $row['officer']]);
                MaintenanceIssue::updateOrCreate(['id' => $row['id']], [
                    'branch_id' => $branch->id,
                    'category_id' => $category->id,
                    'title' => $row['title'],
                    'priority' => $row['priority'],
                    'officer_id' => $officer->id,
                    'target_date' => $row['targetDate'] ?: null,
                    'status' => $row['status'],
                ]);
            }

            foreach ($licences as $row) {
                $branch = Branch::firstOrCreate(['name' => $row['branch']]);
                $category = Category::firstOrCreate(['module' => 'licences', 'name' => $row['category']]);
                Licence::updateOrCreate(['id' => $row['id']], [
                    'branch_id' => $branch->id,
                    'category_id' => $category->id,
                    'title' => $row['title'],
                    'authority' => $row['authority'],
                    'expiry_date' => $row['expiryDate'] ?: null,
                    'status' => $row['status'],
                ]);
            }

            foreach ($questions as $index => $question) {
                $category = Category::firstOrCreate(['module' => 'rcsa', 'name' => $question['category']]);
                RcsaTemplate::firstOrCreate(
                    ['category_id' => $category->id, 'item' => $question['item']],
                    ['sort_order' => $index + 1, 'is_active' => true]
                );
            }

            // Create one unevaluated assessment for each template in each branch.
            // No artificial YES/NO results are generated.
            $templates = RcsaTemplate::where('is_active', true)->orderBy('sort_order')->get();
            foreach ($branches as $branchName) {
                $branch = Branch::where('name', $branchName)->firstOrFail();
                foreach ($templates as $template) {
                    RcsaAssessment::firstOrCreate(
                        ['branch_id' => $branch->id, 'rcsa_template_id' => $template->id],
                        ['status' => 'N/E', 'remarks' => null, 'action_plan' => null, 'target_date' => null]
                    );
                }
            }
        });
    }

    private function json(string $filename): array
    {
        $path = database_path('seeders/data/'.$filename);
        if (!is_file($path)) throw new \RuntimeException("Missing seed data: {$path}");
        return json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }
}
