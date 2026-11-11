<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(AzaniaDashboardSeeder::class);

        User::query()->updateOrCreate(
            ['email' => 'hq@azania.local'],
            [
                'name' => 'Head Office Admin',
                'password' => 'Password1',
                'role' => User::ROLE_HQ,
                'branch_id' => null,
            ]
        );

        $branch = Branch::query()->where('is_active', true)->orderBy('name')->first();
        if ($branch) {
            User::query()->updateOrCreate(
                ['email' => 'branch@azania.local'],
                [
                    'name' => $branch->name.' User',
                    'password' => 'Password1',
                    'role' => User::ROLE_BRANCH,
                    'branch_id' => $branch->id,
                ]
            );
        }
    }
}
