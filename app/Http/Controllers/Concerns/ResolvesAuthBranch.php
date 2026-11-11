<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;
use InvalidArgumentException;

trait ResolvesAuthBranch
{
    protected function authBranchName(Request $request): ?string
    {
        $user = $request->user();
        if (! $user || $user->isHq()) {
            return null;
        }

        $user->loadMissing('branch');

        return $user->branch?->name;
    }

    protected function resolveBranchName(Request $request, ?string $requested = null): ?string
    {
        $user = $request->user();
        if (! $user) {
            return $requested;
        }

        if ($user->isBranch()) {
            $branch = $this->authBranchName($request);
            if (! $branch) {
                throw new InvalidArgumentException('Your account is not linked to a branch.');
            }

            if ($requested && $requested !== $branch && $requested !== 'HQ' && $requested !== 'ALL') {
                throw new InvalidArgumentException('You can only access your own branch.');
            }

            return $branch;
        }

        return $requested && ! in_array($requested, ['HQ', 'ALL'], true) ? $requested : $requested;
    }

    protected function assertHq(Request $request): void
    {
        if (! $request->user()?->isHq()) {
            abort(403, 'Only Head Office can perform this action.');
        }
    }
}
