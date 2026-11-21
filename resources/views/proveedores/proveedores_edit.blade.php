@extends("maestra")
@section("titulo", $proveedor->nombre)
@section("contenido")
<div class="pagina pagina-media">
    <div class="encabezado-pagina">
        <div>
            <a class="volver" href="{{ route('proveedores.index') }}"><i class="fas fa-arrow-left" aria-hidden="true"></i> Proveedores</a>
            <h1 class="titulo-pagina">{{ $proveedor->nombre }}</h1>
            <p class="subtitulo-pagina">Editar proveedor</p>
        </div>
    </div>

    <div class="proveedor-detalle">
        <form method="POST" action="{{ route('proveedores.update', $proveedor) }}" autocomplete="off">
            @method("PUT")
            @csrf
            @include("proveedores._formulario", ["proveedor" => $proveedor])
        </form>

        <aside class="proveedor-lateral">
            <section class="panel">
                <span class="etiqueta-campo">Comprado en total</span>
                <div class="proveedor-cifra cifra">{{ dinero($totalComprado) }}</div>
                <a class="proveedor-enlace" href="{{ route('productos.index', ['proveedor' => $proveedor->id]) }}">
                    <i class="fas fa-shopping-basket" aria-hidden="true"></i> {{ $numeroProductos }} {{ $numeroProductos == 1 ? 'producto' : 'productos' }} de este proveedor
                </a>
            </section>
            <section class="panel p-0">
                <h2 class="panel-titulo proveedor-compras-titulo">Últimas compras</h2>
                @forelse($ultimasCompras as $compra)
                    <div class="proveedor-compra">
                        <span class="cifra text-muted">{{ str_replace('-', '/', $compra->fecha) }}</span>
                        <strong class="cifra">{{ dinero($compra->total) }}</strong>
                    </div>
                @empty
                    <p class="tabla-vacia">Sin compras registradas. <a href="{{ route('compras.index') }}">Registrar una</a></p>
                @endforelse
            </section>
        </aside>
    </div>
</div>
@endsection
