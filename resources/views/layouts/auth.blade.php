<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sign in') · Azania Bank OpsControl</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        azania: { navy: '#1e3a8a', blue: '#2563eb', gold: '#d97706' }
                    },
                    fontFamily: {
                        sans: ['Noto Sans', 'Segoe UI', 'Roboto', 'Helvetica Neue', 'Arial', 'sans-serif']
                    }
                }
            }
        }
    </script>
</head>
<body class="min-h-screen antialiased font-sans text-slate-900 bg-slate-950">
    <div class="min-h-screen grid lg:grid-cols-2">
        <aside class="relative hidden lg:flex flex-col justify-between overflow-hidden p-10 text-white"
            style="background:
                radial-gradient(circle at 18% 20%, rgba(37,99,235,.45), transparent 42%),
                radial-gradient(circle at 82% 78%, rgba(217,119,6,.28), transparent 40%),
                linear-gradient(160deg, #0f172a 0%, #1e3a8a 55%, #0b1224 100%);">
            <div>
                <div class="inline-flex items-center gap-3">
                    <div class="h-11 w-11 rounded-xl bg-white/10 border border-white/15 flex items-center justify-center">
                        <i class="fa-solid fa-building-columns"></i>
                    </div>
                    <div>
                        <p class="text-sm font-bold tracking-wide">AZANIA BANK PLC</p>
                        <p class="text-[11px] text-blue-100/80 uppercase tracking-[0.18em]">Risk & Audit Ops</p>
                    </div>
                </div>
                <h1 class="mt-14 text-4xl font-bold leading-tight max-w-md">
                    @yield('hero_title', 'Operate RCSA with clear Head Office and Branch roles.')
                </h1>
                <p class="mt-4 text-sm text-blue-100/85 max-w-md leading-relaxed">
                    @yield('hero_text', 'Head Office monitors and unlocks. Branches complete and submit their own monthly reports.')
                </p>
            </div>
            <div class="space-y-3 text-sm text-blue-50/90">
                <div class="flex items-start gap-3">
                    <i class="fa-solid fa-shield-halved mt-0.5 text-amber-300"></i>
                    <p>Secure sign-in with role-aware dashboards.</p>
                </div>
                <div class="flex items-start gap-3">
                    <i class="fa-solid fa-code-branch mt-0.5 text-amber-300"></i>
                    <p>Branch users only see and submit for their branch.</p>
                </div>
            </div>
        </aside>

        <main class="flex items-center justify-center px-4 py-10 sm:px-8 bg-slate-50">
            <div class="w-full max-w-md">
                <div class="lg:hidden mb-8 flex items-center gap-3">
                    <div class="h-10 w-10 rounded-xl bg-azania-navy text-white flex items-center justify-center">
                        <i class="fa-solid fa-building-columns"></i>
                    </div>
                    <div>
                        <p class="text-sm font-bold">AZANIA BANK PLC</p>
                        <p class="text-[11px] text-slate-500 uppercase tracking-wider">OpsControl</p>
                    </div>
                </div>

                @if (session('status'))
                    <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-800 px-4 py-3 text-xs font-medium">
                        {{ session('status') }}
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>
    @stack('scripts')
</body>
</html>
