<x-layouts.app title="Iniciar sesión">
    <div class="school-panel mx-auto mt-10 max-w-md rounded-xl p-8">
        <div class="mb-6 text-center">
            <img src="{{ asset('images/logo_san_genaro.svg') }}" alt="Logo IEP Sagrado Corazón" class="mx-auto h-20 w-20 rounded-xl border border-[#e2e1db] bg-white p-2">
            <h1 class="mt-4 text-2xl font-bold text-[#17427f]">IEP Sagrado Corazón</h1>
            <p class="mt-1 text-sm font-medium text-[#73726c]">Gestión Escolar</p>
        </div>
        <p class="mt-2 text-center text-sm text-[#73726c]">Matrícula de alumnos y evaluación docente</p>
        <form class="mt-8 space-y-4" method="POST" action="{{ route('login.store') }}">
            @csrf
            <div>
                <label class="text-sm font-medium text-[#17427f]">Correo</label>
                <input class="mt-1 w-full rounded-md px-3 py-2.5 text-sm" type="email" name="email" value="{{ old('email') }}" required autofocus>
            </div>
            <div>
                <label class="text-sm font-medium text-[#17427f]">Contraseña</label>
                <input class="mt-1 w-full rounded-md px-3 py-2.5 text-sm" type="password" name="password" required>
            </div>
            <button class="school-button-primary w-full rounded-md px-4 py-2.5 font-semibold">Ingresar</button>
        </form>
        <p class="mt-4 rounded-md bg-[#f7f7f5] px-3 py-2 text-xs text-[#73726c]">Usuario inicial: admin@school.test / password</p>
    </div>
</x-layouts.app>
