@extends('maestra')
@section("titulo", "Iniciar sesión")
@section('contenido')
<div class="login">
    {{-- Izquierda: datos del negocio (tabla `negocio`) --}}
    <aside class="login-marca">
        <div class="login-marca-cuerpo">
            @if($negocio->urlLogo())
                <img class="login-logo" src="{{ $negocio->urlLogo() }}" alt="Logo de {{ $negocio->nombre }}">
            @else
                <span class="login-logo" aria-hidden="true">{{ $negocio->iniciales() }}</span>
            @endif
            <h1 class="login-nombre">{{ $negocio->nombre }}</h1>
            <dl class="login-datos">
                @if($negocio->nit)<dt>NIT</dt><dd>NIT {{ $negocio->nit }}</dd>@endif
                @if($negocio->direccion)<dt>Dirección</dt><dd>{{ $negocio->direccion }}</dd>@endif
                @if($negocio->barrioTexto())<dt>Barrio</dt><dd>{{ $negocio->barrioTexto() }}</dd>@endif
                @if($negocio->ciudad)<dt>Ciudad</dt><dd>{{ $negocio->ciudad }}</dd>@endif
                @if($negocio->telefono)<dt>Teléfono</dt><dd>Tel. {{ $negocio->telefono }}</dd>@endif
            </dl>
        </div>
        @if($negocio->firma_software)
            <p class="login-firma">{{ $negocio->firma_software }}</p>
        @endif
    </aside>

    {{-- Derecha: formulario --}}
    <div class="login-formulario">
        <form method="POST" action="{{ route('login') }}">
            @csrf
            <h2 class="login-titulo">Iniciar sesión</h2>
            <p class="login-sub">Entra con tu usuario de caja.</p>

            @if($errors->any())
                <div class="login-error" role="alert">
                    <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <div class="campo">
                <label for="email">Usuario</label>
                <input id="email" type="text" name="email" value="{{ old('email') }}"
                       class="form-control input-grande @error('email') is-invalid @enderror"
                       required autocomplete="username" autocapitalize="none" spellcheck="false" autofocus>
            </div>

            <div class="campo">
                <label for="password">Contraseña</label>
                <div class="login-clave">
                    <input id="password" type="password" name="password"
                           class="form-control input-grande @error('password') is-invalid @enderror"
                           required autocomplete="current-password">
                    <button type="button" class="login-ver-clave" id="verClave" aria-label="Mostrar contraseña" aria-pressed="false">
                        <i class="far fa-eye" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-mazorca login-entrar" id="botonEntrar">Iniciar sesión</button>
        </form>
    </div>
</div>

<script>
    (function () {
        const clave = document.getElementById("password"),
            ver = document.getElementById("verClave"),
            usuario = document.getElementById("email");

        ver.addEventListener("click", () => {
            const mostrar = clave.type === "password";
            clave.type = mostrar ? "text" : "password";
            ver.setAttribute("aria-pressed", mostrar);
            ver.setAttribute("aria-label", mostrar ? "Ocultar contraseña" : "Mostrar contraseña");
            ver.firstElementChild.className = mostrar ? "far fa-eye-slash" : "far fa-eye";
            clave.focus();
        });

        // Si ya viene el usuario (error de contraseña), el cursor va a la contraseña
        if (usuario.value) { clave.focus(); }

        // Evitar doble envío
        document.querySelector(".login form").addEventListener("submit", () => {
            const b = document.getElementById("botonEntrar");
            b.disabled = true;
            b.textContent = "Entrando…";
        });
    })();
</script>
@endsection
