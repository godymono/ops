<?php

namespace App\Services\Dashboard;

use App\Models\Licence;
use App\Models\MaintenanceIssue;
use App\Models\RcsaAssessment;
use Database\Seeders\AzaniaDashboardSeeder;
use Illuminate\Support\Facades\DB;

class DashboardResetService
{
    public function __construct(
        private readonly DashboardStateService $state,
    ) {}

    public function restoreDefaults(): array
    {
        DB::transaction(function () {
            RcsaAssessment::query()->delete();
            Licence::query()->delete();
            MaintenanceIssue::query()->delete();
        });

        (new AzaniaDashboardSeeder)->run();

        return $this->state->payload();
    }
}
