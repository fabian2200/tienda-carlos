@extends("maestra")
@section("titulo", "Proveedores")
@section("contenido")
<div class="pagina pagina-media">
    <div class="encabezado-pagina">
        <div>
            <h1 class="titulo-pagina">Proveedores</h1>
            <p class="subtitulo-pagina"><span data-filtro-contador="#tablaProveedores">{{ $proveedores->count() }}</span> proveedores</p>
        </div>
        <div class="encabezado-acciones">
            <a href="{{ route('proveedores.create') }}" class="btn btn-mazorca"><i class="fas fa-plus" aria-hidden="true"></i> Agregar proveedor</a>
        </div>
    </div>

    @include("notificacion")

    @if($proveedores->isNotEmpty())
        <div class="filtros">
            <label class="filtro-buscar">
                <span class="oculto">Buscar proveedor</span>
                <i class="fas fa-search" aria-hidden="true"></i>
                <input type="search" class="form-control" placeholder="Buscar por nombre, NIT, teléfono o contacto" data-filtrar-tabla="#tablaProveedores" autocomplete="off">
            </label>
        </div>
    @endif

    <div class="panel p-0 tabla-contenedor">
        @if($proveedores->isEmpty())
            <div class="vacio-grande">
                <i class="fas fa-truck" aria-hidden="true"></i>
                <p><strong>Aún no hay proveedores.</strong><br>Agrégalos para elegirlos al registrar productos y compras.</p>
                <a href="{{ route('proveedores.create') }}" class="btn btn-primary">Agregar el primero</a>
            </div>
        @else
            <table class="tabla-pos" id="tablaProveedores">
                <thead>
                    <tr>
                        <th>Proveedor</th>
                        <th>Teléfono</th>
                        <th class="num">Productos</th>
                        <th class="num">Comprado</th>
                        <th class="acciones-titulo"><span class="oculto">Acciones</span></th>
                    </tr>
                </thead>
                <tbody>
                @foreach($proveedores as $proveedor)
                    <tr>
                        <td>
                            <a href="{{ route('proveedores.edit', $proveedor) }}"><strong>{{ $proveedor->nombre }}</strong></a>
                            <small class="d-block text-muted">
                                @if($proveedor->nit)NIT {{ $proveedor->nit }}@endif
                                @if($proveedor->nit && $proveedor->contacto) <span aria-hidden="true">|</span> @endif
                                @if($proveedor->contacto){{ $proveedor->contacto }}@endif
                            </small>
                        </td>
                        <td class="cifra">{{ $proveedor->telefono }}</td>
                        <td class="num">
                            @if($proveedor->numero_productos > 0)
                                <a href="{{ route('productos.index', ['proveedor' => $proveedor->id]) }}">{{ number_format($proveedor->numero_productos, 0, ',', '.') }}</a>
                            @else
                                <span class="text-muted">0</span>
                            @endif
                        </td>
                        <td class="num">{{ dinero($proveedor->total_comprado) }}</td>
                        <td class="acciones">
                            <a class="btn-accion" href="{{ route('proveedores.edit', $proveedor) }}" title="Ver y editar" aria-label="Editar {{ $proveedor->nombre }}"><i class="fas fa-pen" aria-hidden="true"></i></a>
                            <button type="button" class="btn-accion peligro" title="Eliminar proveedor" aria-label="Eliminar {{ $proveedor->nombre }}"
                                    data-eliminar="{{ route('proveedores.destroy', $proveedor) }}"
                                    data-titulo="¿Eliminar a {{ $proveedor->nombre }}?"
                                    data-texto="Los productos y compras se conservan; solo quedan sin proveedor asignado.">
                                <i class="fas fa-trash-alt" aria-hidden="true"></i>
                            </button>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <p class="tabla-vacia" data-filtro-vacio="#tablaProveedores" hidden>No hay proveedores con ese dato.</p>
        @endif
    </div>
</div>
@endsection
