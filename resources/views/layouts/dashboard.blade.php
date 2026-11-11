<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Azania Bank PLC - Risk, Compliance & Maintenance Platform' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        azania: {
                            navy: '#1e3a8a',
                            navyDark: '#0f172a',
                            blue: '#2563eb',
                            gold: '#d97706',
                            goldLight: '#fef3c7'
                        }
                    },
                    fontFamily: {
                        sans: ['Noto Sans', 'Segoe UI', 'Roboto', 'Helvetica Neue', 'Arial', 'sans-serif']
                    },
                    fontSize: {
                        xs: ['0.75rem', { lineHeight: '1.25' }],
                        sm: ['0.8125rem', { lineHeight: '1.375' }],
                        base: ['0.875rem', { lineHeight: '1.5' }],
                        lg: ['1.125rem', { lineHeight: '1.25' }],
                        xl: ['1.25rem', { lineHeight: '1.25' }]
                    }
                }
            }
        }
    </script>
    @stack('head')
</head>
<body class="min-h-screen flex flex-col antialiased bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 selection:bg-blue-100 selection:text-blue-900 transition-colors duration-200">
    @yield('content')

    <script>
        window.OpsControl = {
            csrfToken: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            routes: {
                state: @json(route('dashboard.state.show')),
                saveState: @json(route('dashboard.state.update')),
                options: @json(route('dashboard.options')),
                reset: @json(route('dashboard.reset')),
                records: @json(url('/dashboard/records')),
                reports: @json(route('rcsa.reports.index')),
                reportPeriods: @json(url('/rcsa/reports/periods')),
                reportSubmissions: @json(url('/rcsa/reports/submissions')),
                reportBranchHistory: @json(url('/rcsa/reports/branch-history')),
                dashboard: @json(url('/')),
            },
            initialState: @json($initialState ?? ['maintenance' => [], 'licences' => [], 'rcsa' => []]),
            initialOptions: @json($initialOptions ?? []),
            auth: @json($authUser ?? null),
            flashStatus: @json(session('status')),
        };
        if (window.OpsControl.routes) {
            window.OpsControl.routes.logout = @json(route('logout'));
        }
    </script>
    @stack('scripts')
</body>
</html>
