<?php

namespace App\Http\Controllers;

use App\Services\Dashboard\DashboardStateService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardStateService $state,
    ) {}

    public function index(Request $request): View
    {
        $payload = $this->state->payload();
        $user = $request->user()->loadMissing('branch');

        return view('dashboard.index', [
            'initialState' => $payload['state'],
            'initialOptions' => $payload['options'],
            'authUser' => $user->toDashboardAuth(),
            'appName' => config('app.name', 'OpsControl'),
        ]);
    }
}
