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
@section("titulo", "Agregar cliente")
@section("contenido")
<div class="pagina pagina-formulario">
    <div class="encabezado-pagina">
        <div>
            <a class="volver" href="{{ route('clientes.index') }}"><i class="fas fa-arrow-left" aria-hidden="true"></i> Clientes</a>
            <h1 class="titulo-pagina">Agregar cliente</h1>
        </div>
    </div>
    <form method="POST" action="{{ route('clientes.store') }}" autocomplete="off">
        @csrf
        @include("clientes._formulario", ["cliente" => null])
    </form>
</div>
@endsection
