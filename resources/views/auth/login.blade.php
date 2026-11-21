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
            <p class="login-etiqueta"><i class="fas fa-store" aria-hidden="true"></i> Punto de venta para tiendas de barrio</p>
            <h1 class="login-nombre">{{ $negocio->nombre }}</h1>
            <dl class="login-datos">
                @if($negocio->nit)<dt>NIT</dt><dd>NIT {{ $negocio->nit }}</dd>@endif
                @if($negocio->direccion)<dt>Dirección</dt><dd>{{ $negocio->direccion }}</dd>@endif
                @if($negocio->barrioTexto())<dt>Barrio</dt><dd>{{ $negocio->barrioTexto() }}</dd>@endif
                @if($negocio->ciudad)<dt>Ciudad</dt><dd>{{ $negocio->ciudad }}</dd>@endif
                @if($negocio->telefono)<dt>Teléfono</dt><dd>Tel. {{ $negocio->telefono }}</dd>@endif
            </dl>
            <ul class="login-funciones" aria-label="Qué puedes hacer">
                <li><i class="fas fa-cash-register" aria-hidden="true"></i> Ventas</li>
                <li><i class="fas fa-book" aria-hidden="true"></i> Fiados</li>
                <li><i class="fas fa-boxes" aria-hidden="true"></i> Inventario</li>
                <li><i class="fas fa-truck" aria-hidden="true"></i> Compras</li>
            </ul>
        </div>
        @if($negocio->firma_software)
            <p class="login-firma">{{ $negocio->firma_software }}</p>
        @endif
    </aside>

    {{-- Derecha: formulario --}}
    <div class="login-formulario">
        <form method="POST" action="{{ route('login') }}" class="login-tarjeta">
            @csrf
            <h2 class="login-titulo">¡Buenas, vecino!</h2>
            <p class="login-sub">Entra con tu usuario de caja para abrir la tienda.</p>

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

    <p class="login-credito">Foto: Matthew Zheng en Unsplash</p>
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
