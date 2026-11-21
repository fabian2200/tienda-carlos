@extends("maestra")
@section("titulo", "Registrar producto")
@section("contenido")
<div class="pagina">
    <div class="encabezado-pagina">
        <div>
            <a class="volver" href="{{ route('productos.index') }}"><i class="fas fa-arrow-left" aria-hidden="true"></i> Productos</a>
            <h1 class="titulo-pagina">Registrar producto</h1>
            <p class="subtitulo-pagina">Al guardar, el formulario queda listo para el siguiente.</p>
        </div>
    </div>

    @include("notificacion")

    <form method="POST" enctype="multipart/form-data" action="{{ route('productos.store') }}" autocomplete="off">
        @csrf
        @include("productos._formulario", ["producto" => null])
    </form>
</div>
@endsection
