<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'IEP Sagrado Corazón' }}</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        :root {
            --school-blue: #17427f;
            --school-blue-dark: #0b2d5f;
            --school-yellow: #f8e600;
            --school-green: #078b2f;
            --school-orange: #e94a1a;
            --school-red: #e63700;
            --school-bg: #f7f7f5;
            --school-surface: #ffffff;
            --school-muted: #f0efe9;
            --school-border: #e2e1db;
            --school-text: #1a1a18;
            --school-text-muted: #73726c;
        }

        body {
            background: var(--school-bg);
            color: var(--school-text);
            -webkit-font-smoothing: antialiased;
        }

        input, select, textarea {
            border: 1px solid var(--school-border);
            background-color: #fff;
            color: var(--school-text);
            transition: border-color .12s ease-in-out, box-shadow .12s ease-in-out;
        }

        input:focus, select:focus, textarea:focus {
            border-color: var(--school-blue);
            box-shadow: 0 0 0 3px rgba(23, 66, 127, .12);
            outline: none;
        }

        .school-sidebar, .unfv-sidebar {
            background: var(--school-surface);
            border-right: 1px solid var(--school-border);
        }

        .school-card, .unfv-card {
            position: relative;
            overflow: hidden;
            border: 1px solid var(--school-border);
            background: var(--school-surface);
            box-shadow: 0 1px 2px rgba(26, 26, 24, .04);
        }

        .school-card::before, .unfv-card::before {
            content: "";
            position: absolute;
            inset: 0 0 auto 0;
            height: 3px;
            background: linear-gradient(90deg, var(--school-blue), var(--school-green), var(--school-yellow));
        }

        .school-panel, .unfv-panel {
            border: 1px solid var(--school-border);
            background: var(--school-surface);
            box-shadow: 0 1px 2px rgba(26, 26, 24, .04);
        }

        .school-button-primary, .unfv-button-primary {
            background: var(--school-blue);
            color: #fff;
            border: 1px solid var(--school-blue);
            transition: background .12s ease, border-color .12s ease, opacity .12s ease;
        }

        .school-button-primary:hover, .unfv-button-primary:hover {
            background: var(--school-blue-dark);
            border-color: var(--school-blue-dark);
        }

        .school-button-secondary, .unfv-button-secondary {
            background: #fff;
            border: 1px solid var(--school-border);
            color: var(--school-blue-dark);
        }

        .school-button-secondary:hover, .unfv-button-secondary:hover {
            background: var(--school-muted);
        }

        .school-badge {
            border: 1px solid var(--school-border);
            background: var(--school-muted);
            color: var(--school-blue-dark);
        }

        .school-nav-link {
            position: relative;
            display: flex;
            align-items: center;
            gap: .65rem;
            border-radius: .5rem;
            padding: .5rem .65rem .5rem .8rem;
            color: #3d3d3a;
            font-size: .875rem;
            font-weight: 500;
            transition: background .12s ease, color .12s ease;
        }

        .school-nav-link:hover {
            background: var(--school-muted);
            color: var(--school-text);
        }

        .school-nav-link.is-active {
            background: #eef4fd;
            color: var(--school-blue-dark);
            font-weight: 700;
        }

        .school-nav-link.is-active::before {
            content: "";
            position: absolute;
            bottom: .55rem;
            left: 0;
            top: .55rem;
            width: 3px;
            border-radius: 0 999px 999px 0;
            background: var(--school-green);
        }

        .school-nav-dot {
            display: inline-flex;
            height: 1.35rem;
            width: 1.35rem;
            flex-shrink: 0;
            align-items: center;
            justify-content: center;
            border-radius: .375rem;
            background: var(--school-muted);
            color: var(--school-text-muted);
            font-size: .7rem;
            font-weight: 700;
        }

        .school-nav-link.is-active .school-nav-dot {
            background: #dbeafe;
            color: var(--school-blue-dark);
        }

        .school-page-shell {
            margin: 0 auto;
            width: 100%;
            max-width: 1180px;
        }
    </style>
</head>
<body class="text-slate-900">
    <div class="min-h-screen md:flex">
        @auth
            <aside class="school-sidebar flex shrink-0 flex-col md:fixed md:inset-y-0 md:left-0 md:z-40 md:h-screen md:w-60">
                <div class="border-b border-[#e2e1db] px-4 py-3">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex min-w-0 items-center">
                            <img src="{{ asset('images/logo_san_genaro.svg') }}" alt="Logo IEP Sagrado Corazón" class="mr-2.5 h-9 w-9 rounded-lg border border-[#e2e1db] bg-white p-1">
                            <div class="min-w-0">
                                <h1 class="truncate text-sm font-bold text-[#17427f]">IEP Sagrado Corazón</h1>
                                <p class="mt-0.5 truncate text-xs text-[#73726c]">Gestión Escolar</p>
                            </div>
                        </div>
                        <span class="h-2 w-2 shrink-0 rounded-full bg-[#078b2f]"></span>
                    </div>
                </div>
                <nav class="min-h-0 flex-1 space-y-1 overflow-y-auto px-3 py-3">
                    <div class="px-2 pb-1 text-[11px] font-bold uppercase tracking-wide text-[#73726c]">Principal</div>
                    <a class="school-nav-link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}" href="{{ route('dashboard') }}">
                        <span class="school-nav-dot">D</span>Dashboard
                    </a>

                    @if(auth()->user()->hasRole('administrador'))
                        <div class="px-2 pt-3 text-[11px] font-bold uppercase tracking-wide text-[#73726c]">Operación</div>
                        @foreach([
                            'users' => ['Usuarios', 'U'],
                            'students' => ['Alumnos', 'A'],
                            'guardians' => ['Apoderados', 'P'],
                            'teachers' => ['Docentes', 'T'],
                            'enrollments' => ['Matrículas', 'M'],
                            'student-payments' => ['Pagos', 'S/'],
                        ] as $key => [$label, $abbr])
                            <a class="school-nav-link {{ request()->routeIs('resources.*') && request()->route('resource') === $key ? 'is-active' : '' }}" href="{{ route('resources.index', $key) }}">
                                <span class="school-nav-dot">{{ $abbr }}</span>{{ $label }}
                            </a>
                        @endforeach
                        <a class="school-nav-link {{ request()->routeIs('announcements.*') ? 'is-active' : '' }}" href="{{ route('announcements.index') }}">
                            <span class="school-nav-dot">C</span>Comunicados
                        </a>
                        <a class="school-nav-link {{ request()->routeIs('evaluations.*') ? 'is-active' : '' }}" href="{{ route('evaluations.index') }}">
                            <span class="school-nav-dot">E</span>Calificar docentes
                        </a>
                        <a class="school-nav-link {{ request()->routeIs('reports.*') ? 'is-active' : '' }}" href="{{ route('reports.index') }}">
                            <span class="school-nav-dot">R</span>Reportes
                        </a>
                        <div class="px-2 pt-3 text-[11px] font-bold uppercase tracking-wide text-[#73726c]">Configuración</div>
                        @foreach([
                            'academic-years' => ['Años escolares', 'AE'],
                            'levels' => ['Niveles', 'N'],
                            'grades' => ['Grados', 'G'],
                            'courses' => ['Cursos', 'C'],
                            'payment-concepts' => ['Conceptos de pago', 'CP'],
                            'late-fees' => ['Configuración de moras', 'M'],
                            'evaluation-periods' => ['Periodos de evaluación', 'PE'],
                            'evaluation-criteria' => ['Criterios de evaluación', 'CE'],
                        ] as $key => [$label, $abbr])
                            <a class="school-nav-link {{ ($key === 'late-fees' && request()->routeIs('late-fee-settings.*')) || (request()->routeIs('resources.*') && request()->route('resource') === $key) ? 'is-active' : '' }}" href="{{ $key === 'late-fees' ? route('late-fee-settings.index') : route('resources.index', $key) }}">
                                <span class="school-nav-dot">{{ $abbr }}</span>{{ $label }}
                            </a>
                        @endforeach
                    @endif

                    @if(auth()->user()->hasRole('secretaria'))
                        <div class="px-2 pt-3 text-[11px] font-bold uppercase tracking-wide text-[#73726c]">Secretaría</div>
                        @foreach([
                            'students' => ['Alumnos', 'A'],
                            'guardians' => ['Apoderados', 'P'],
                            'enrollments' => ['Matrículas', 'M'],
                            'student-payments' => ['Pagos', 'S/'],
                        ] as $key => [$label, $abbr])
                            <a class="school-nav-link {{ request()->routeIs('resources.*') && request()->route('resource') === $key ? 'is-active' : '' }}" href="{{ route('resources.index', $key) }}">
                                <span class="school-nav-dot">{{ $abbr }}</span>{{ $label }}
                            </a>
                        @endforeach
                        <a class="school-nav-link {{ request()->routeIs('enrollments.wizard.*') ? 'is-active' : '' }}" href="{{ route('enrollments.wizard.create') }}">
                            <span class="school-nav-dot">+</span>Nueva matrícula
                        </a>
                        <a class="school-nav-link {{ request()->routeIs('announcements.*') ? 'is-active' : '' }}" href="{{ route('announcements.index') }}">
                            <span class="school-nav-dot">C</span>Comunicados
                        </a>
                        <a class="school-nav-link {{ request()->routeIs('late-fees.payments.*') ? 'is-active' : '' }}" href="{{ route('late-fees.payments.index') }}">
                            <span class="school-nav-dot">!</span>Pagos en mora
                        </a>
                    @endif

                    @if(auth()->user()->hasRole('alumno', 'apoderado'))
                        <a class="school-nav-link {{ request()->routeIs('evaluations.*') ? 'is-active' : '' }}" href="{{ route('evaluations.index') }}">
                            <span class="school-nav-dot">E</span>Calificar docentes
                        </a>
                    @endif
                    @if(auth()->user()->hasRole('apoderado'))
                        <a class="school-nav-link {{ request()->routeIs('payments.*') ? 'is-active' : '' }}" href="{{ route('payments.index') }}">
                            <span class="school-nav-dot">S/</span>Pagos
                        </a>
                    @endif
                    @if(auth()->user()->hasRole('docente', 'alumno', 'apoderado'))
                        <a class="school-nav-link {{ request()->routeIs('announcements.*') ? 'is-active' : '' }}" href="{{ route('announcements.index') }}">
                            <span class="school-nav-dot">C</span>Comunicados
                        </a>
                    @endif
                    @if(auth()->user()->hasRole('docente'))
                        <a class="school-nav-link {{ request()->routeIs('reports.*') ? 'is-active' : '' }}" href="{{ route('reports.index') }}">
                            <span class="school-nav-dot">R</span>Mis evaluaciones
                        </a>
                    @endif
                </nav>
                <div class="border-t border-[#e2e1db] p-3">
                    <div class="mb-3 flex items-center">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-[#eef4fd] text-sm font-bold text-[#0b2d5f]">
                            {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                        </div>
                        <div class="ml-2.5 min-w-0">
                            <p class="truncate text-sm font-medium text-[#1a1a18]">{{ auth()->user()->name ?? 'Usuario' }}</p>
                            <p class="truncate text-xs text-[#73726c]">{{ auth()->user()->role_label }}</p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button class="w-full rounded-md border border-[#e2e1db] bg-white px-3 py-1.5 text-left text-sm font-medium text-[#b91c1c] hover:bg-[#fef2f2]">Cerrar sesión</button>
                    </form>
                </div>
            </aside>
        @endauth
        <div class="flex min-w-0 flex-1 flex-col {{ auth()->check() ? 'md:ml-60' : '' }}">
            @auth
                <header class="sticky top-0 z-20 border-b border-[#e2e1db] bg-white/95 backdrop-blur">
                    <div class="flex items-center justify-between px-5 py-2.5">
                        <div>
                            <h2 class="text-base font-bold text-[#17427f]">{{ $title ?? 'IEP Sagrado Corazón' }}</h2>
                            <p class="text-xs font-medium text-[#73726c]">Matrícula, pagos y evaluación docente</p>
                        </div>
                        <div class="school-badge rounded-md px-3 py-1.5 text-xs font-semibold">{{ auth()->user()->role_label }}</div>
                    </div>
                </header>
            @endauth
            <main class="flex-1 overflow-auto p-4 md:p-5">
                @if(session('status'))
                    <div class="mb-4 rounded-md border border-green-200 bg-green-50 p-3 text-green-800 shadow-sm">{{ session('status') }}</div>
                @endif
                @if($errors->any())
                    <div class="mb-4 rounded-md border border-red-200 bg-red-50 p-3 text-red-800 shadow-sm">{{ $errors->first() }}</div>
                @endif
                <div class="school-page-shell">
                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>
</body>
</html>
