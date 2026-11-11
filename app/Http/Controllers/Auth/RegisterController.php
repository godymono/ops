<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(): View
    {
        $branches = Branch::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('auth.register', [
            'appName' => config('app.name', 'OpsControl'),
            'branches' => $branches,
            'hasHq' => User::query()->where('role', User::ROLE_HQ)->exists(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'role' => ['required', Rule::in([User::ROLE_HQ, User::ROLE_BRANCH])],
            'branch_id' => [
                Rule::requiredIf(fn () => $request->input('role') === User::ROLE_BRANCH),
                'nullable',
                'integer',
                Rule::exists('branches', 'id')->where(fn ($q) => $q->where('is_active', true)),
            ],
        ], [
            'branch_id.required' => 'Choose your branch to continue.',
            'password.confirmed' => 'Password confirmation does not match.',
        ]);

        if ($validated['role'] === User::ROLE_HQ) {
            $validated['branch_id'] = null;
        }

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => $validated['role'],
            'branch_id' => $validated['branch_id'] ?? null,
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        $tab = $user->isHq() ? 'reports' : 'rcsa';

        return redirect('/?tab='.$tab)
            ->with('status', 'Account created. You are signed in as '.$user->name.'.');
    }
}
