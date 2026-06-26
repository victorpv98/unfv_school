<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'IEP San Genaro' }}</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --school-blue: #123b7a;
            --school-blue-dark: #08285d;
            --school-yellow: #f8e600;
            --school-green: #078b2f;
            --school-orange: #e94a1a;
            --school-red: #e63700;
            --school-cream: #fffef2;
            --school-light: #f6f8ed;
            --school-border: #e7e5c9;
        }

        body {
            background:
                radial-gradient(circle at top right, rgba(248, 230, 0, .18), transparent 28rem),
                linear-gradient(180deg, var(--school-cream) 0%, var(--school-light) 100%);
        }

        input, select, textarea {
            border: 1px solid #ced4da;
            background-color: #fff;
            color: #1f2937;
            transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out;
        }

        input:focus, select:focus, textarea:focus {
            border-color: var(--school-blue);
            box-shadow: 0 0 0 .2rem rgba(18, 59, 122, .16);
            outline: none;
        }

        .school-sidebar, .unfv-sidebar {
            background:
                linear-gradient(180deg, rgba(255, 255, 255, .08), transparent 14rem),
                linear-gradient(160deg, var(--school-blue-dark) 0%, var(--school-blue) 62%, #0b5c2b 100%);
            box-shadow: 8px 0 30px rgba(8, 40, 93, .18);
        }

        .school-card, .unfv-card {
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(18, 59, 122, .08);
            border-top: 4px solid var(--school-yellow);
            background: rgba(255, 255, 255, .94);
            box-shadow: 0 14px 32px rgba(18, 59, 122, .10);
        }

        .school-card::before, .unfv-card::before {
            content: "";
            position: absolute;
            inset: 0 auto 0 0;
            width: 4px;
            background: linear-gradient(180deg, var(--school-green), var(--school-orange));
        }

        .school-panel, .unfv-panel {
            border: 1px solid rgba(18, 59, 122, .08);
            background: rgba(255, 255, 255, .96);
            box-shadow: 0 14px 32px rgba(18, 59, 122, .10);
        }

        .school-button-primary, .unfv-button-primary {
            background: linear-gradient(135deg, var(--school-blue), var(--school-blue-dark));
            color: #fff;
            box-shadow: 0 10px 18px rgba(18, 59, 122, .18);
        }

        .school-button-primary:hover, .unfv-button-primary:hover {
            background: linear-gradient(135deg, var(--school-blue-dark), #061f4a);
        }

        .school-button-secondary, .unfv-button-secondary {
            background: var(--school-green);
            color: #fff;
        }

        .school-button-secondary:hover, .unfv-button-secondary:hover {
            background: #056f27;
        }

        .school-badge {
            border: 1px solid rgba(248, 230, 0, .65);
            background: rgba(255, 248, 183, .85);
            color: var(--school-blue-dark);
        }
    </style>
</head>
<body class="text-slate-900">
    <div class="min-h-screen md:flex">
        @auth
            <aside class="school-sidebar flex shrink-0 flex-col text-white md:h-screen md:w-72">
                <div class="border-b border-white/10 p-5">
                    <div class="flex items-center">
                        <img src="{{ asset('images/logo_san_genaro.svg') }}" alt="Logo IEP San Genaro" class="mr-3 h-16 w-16 rounded-full bg-white p-1 shadow-md">
                        <div>
                            <h1 class="text-xl font-black tracking-wide text-[#f8e600]">IEP San Genaro</h1>
                            <p class="mt-1 text-sm text-blue-100">Gestión Escolar</p>
                        </div>
                    </div>
                    <div class="mt-4 h-1 rounded-full bg-gradient-to-r from-[#f8e600] via-[#e94a1a] to-[#078b2f]"></div>
                </div>
                <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-5">
                    <a class="flex items-center rounded-md px-3 py-2.5 text-sm font-medium {{ request()->routeIs('dashboard') ? 'bg-[#f8e600] text-[#08285d] shadow-sm' : 'text-slate-100 hover:bg-white/10' }}" href="{{ route('dashboard') }}">
                        <span class="mr-3 h-2 w-2 rounded-full bg-current"></span>Dashboard
                    </a>
                    @if(auth()->user()->hasRole('administrador', 'secretaria'))
                        <a class="flex items-center rounded-md px-3 py-2.5 text-sm font-medium {{ request()->routeIs('enrollments.wizard.*') ? 'bg-[#f8e600] text-[#08285d] shadow-sm' : 'text-slate-100 hover:bg-white/10' }}" href="{{ route('enrollments.wizard.create') }}">
                            <span class="mr-3 h-2 w-2 rounded-full bg-current"></span>Nueva matrícula
                        </a>
                    @endif
                    @foreach(config('school.resources') as $key => $resource)
                        @if(in_array($key, ['students', 'guardians', 'teachers', 'courses'], true) && (auth()->user()->hasRole('administrador', 'director') || (auth()->user()->hasRole('secretaria') && in_array($key, ['students', 'guardians', 'teachers'], true))))
                            <a class="flex items-center rounded-md px-3 py-2.5 text-sm font-medium {{ request()->routeIs('resources.*') && request()->route('resource') === $key ? 'bg-[#f8e600] text-[#08285d] shadow-sm' : 'text-slate-100 hover:bg-white/10' }}" href="{{ route('resources.index', $key) }}">
                                <span class="mr-3 h-2 w-2 rounded-full bg-current"></span>{{ $resource['label'] }}
                            </a>
                        @endif
                    @endforeach
                    @if(auth()->user()->hasRole('alumno', 'apoderado', 'administrador'))
                        <a class="flex items-center rounded-md px-3 py-2.5 text-sm font-medium {{ request()->routeIs('evaluations.*') ? 'bg-[#f8e600] text-[#08285d] shadow-sm' : 'text-slate-100 hover:bg-white/10' }}" href="{{ route('evaluations.index') }}">
                            <span class="mr-3 h-2 w-2 rounded-full bg-current"></span>Evaluación docente
                        </a>
                    @endif
                    @if(auth()->user()->hasRole('apoderado', 'administrador'))
                        <a class="flex items-center rounded-md px-3 py-2.5 text-sm font-medium {{ request()->routeIs('payments.*') ? 'bg-[#f8e600] text-[#08285d] shadow-sm' : 'text-slate-100 hover:bg-white/10' }}" href="{{ route('payments.index') }}">
                            <span class="mr-3 h-2 w-2 rounded-full bg-current"></span>Pagos
                        </a>
                    @endif
                    @if(auth()->user()->hasRole('administrador', 'director', 'profesor'))
                        <a class="flex items-center rounded-md px-3 py-2.5 text-sm font-medium {{ request()->routeIs('reports.*') ? 'bg-[#f8e600] text-[#08285d] shadow-sm' : 'text-slate-100 hover:bg-white/10' }}" href="{{ route('reports.index') }}">
                            <span class="mr-3 h-2 w-2 rounded-full bg-current"></span>Reportes
                        </a>
                    @endif
                </nav>
                <div class="border-t border-white/10 bg-[#08285d]/70 p-4">
                    <div class="mb-4 flex items-center">
                        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-[#f8e600] text-sm font-bold text-[#08285d]">
                            {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                        </div>
                        <div class="ml-3 min-w-0">
                            <p class="truncate text-sm font-medium text-white">{{ auth()->user()->name ?? 'Usuario' }}</p>
                            <p class="truncate text-xs text-slate-300">{{ auth()->user()->role_label }}</p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="w-full rounded-md bg-[#e94a1a] px-3 py-2 text-left text-sm font-medium text-white hover:bg-[#e63700]">Cerrar sesión</button>
                    </form>
                </div>
            </aside>
        @endauth
        <div class="flex min-w-0 flex-1 flex-col">
            @auth
                <header class="border-b border-[#e7e5c9] bg-white/90 shadow-sm backdrop-blur">
                    <div class="flex items-center justify-between px-6 py-4">
                        <div>
                            <h2 class="text-xl font-bold text-[#123b7a]">{{ $title ?? 'IEP San Genaro' }}</h2>
                            <p class="text-xs font-medium uppercase tracking-[0.2em] text-[#078b2f]">Matrícula y evaluación docente</p>
                        </div>
                        <div class="school-badge rounded-md px-3 py-2 text-sm font-semibold">{{ auth()->user()->name ?? 'Usuario' }}</div>
                    </div>
                </header>
            @endauth
            <main class="flex-1 overflow-auto p-6">
                @if(session('status'))
                    <div class="mb-4 rounded-md border border-green-200 bg-green-50 p-3 text-green-800 shadow-sm">{{ session('status') }}</div>
                @endif
                @if($errors->any())
                    <div class="mb-4 rounded-md border border-red-200 bg-red-50 p-3 text-red-800 shadow-sm">{{ $errors->first() }}</div>
                @endif
                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
