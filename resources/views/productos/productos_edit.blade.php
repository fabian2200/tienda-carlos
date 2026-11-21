@extends("maestra")
@section("titulo", "Editar producto")
@section("contenido")
<div class="pagina">
    <div class="encabezado-pagina">
        <div>
            <a class="volver" href="{{ route('productos.index') }}"><i class="fas fa-arrow-left" aria-hidden="true"></i> Productos</a>
            <h1 class="titulo-pagina">{{ $producto->descripcion }}</h1>
            <p class="subtitulo-pagina">Editar producto</p>
        </div>
    </div>

    @include("notificacion")

    <form method="POST" enctype="multipart/form-data" action="{{ route('productos.update', [$producto]) }}" autocomplete="off">
        @method("PUT")
        @csrf
        @include("productos._formulario", ["producto" => $producto])
    </form>
</div>
@endsection
