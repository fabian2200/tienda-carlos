{{--

  ____          _____               _ _           _
 |  _ \        |  __ \             (_) |         | |
 | |_) |_   _  | |__) |_ _ _ __ _____| |__  _   _| |_ ___
 |  _ <| | | | |  ___/ _` | '__|_  / | '_ \| | | | __/ _ \
 | |_) | |_| | | |  | (_| | |   / /| | |_) | |_| | ||  __/
 |____/ \__, | |_|   \__,_|_|  /___|_|_.__/ \__, |\__\___|
         __/ |                               __/ |
        |___/                               |___/

    Blog:       https://parzibyte.me/blog
    Ayuda:      https://parzibyte.me/blog/contrataciones-ayuda/
    Contacto:   https://parzibyte.me/blog/contacto/

    Copyright (c) 2020 Luis Cabrera Benito
    Licenciado bajo la licencia MIT

    El texto de arriba debe ser incluido en cualquier redistribucion
--}}
@extends("maestra")
@section("titulo", "Editar usuario")
@section("contenido")
<div class="pagina pagina-formulario">
    <div class="encabezado-pagina">
        <div>
            <a class="volver" href="{{ route('usuarios.index') }}"><i class="fas fa-arrow-left" aria-hidden="true"></i> Usuarios</a>
            <h1 class="titulo-pagina">{{ $usuario->name }}</h1>
            <p class="subtitulo-pagina">Editar usuario</p>
        </div>
    </div>
    <form method="POST" action="{{ route('usuarios.update', [$usuario]) }}" autocomplete="off">
        @method("PUT")
        @csrf
        @include("usuarios._formulario", ["usuario" => $usuario])
    </form>
</div>
@endsection
