<x-layouts.app title="Iniciar sesión">
    <div class="school-panel mx-auto mt-10 max-w-md rounded-2xl p-8">
        <div class="mb-6 text-center">
            <img src="{{ asset('images/logo_san_genaro.svg') }}" alt="Logo IEP San Genaro" class="mx-auto h-28 w-28 rounded-full bg-white p-2 shadow">
            <h1 class="mt-4 text-2xl font-black text-[#123b7a]">IEP San Genaro</h1>
            <p class="text-sm font-semibold uppercase tracking-[0.2em] text-[#078b2f]">Gestión Escolar</p>
        </div>
        <p class="mt-2 text-sm text-slate-600">Matrícula de alumnos y evaluación docente</p>
        <form class="mt-8 space-y-4" method="POST" action="{{ route('login.store') }}">
            @csrf
            <div>
                <label class="text-sm font-medium text-[#123b7a]">Correo</label>
                <input class="mt-1 w-full rounded-md px-3 py-2" type="email" name="email" value="{{ old('email') }}" required autofocus>
            </div>
            <div>
                <label class="text-sm font-medium text-[#123b7a]">Contraseña</label>
                <input class="mt-1 w-full rounded-md px-3 py-2" type="password" name="password" required>
            </div>
            <button class="school-button-primary w-full rounded-md px-4 py-2 font-semibold">Ingresar</button>
        </form>
        <p class="mt-4 text-xs text-slate-500">Usuario inicial: admin@school.test / password</p>
    </div>
</x-layouts.app>
