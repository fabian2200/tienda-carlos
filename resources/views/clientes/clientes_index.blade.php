@extends("maestra")
@section("titulo", "Clientes")
@section("contenido")
<div class="pagina pagina-media">
    <div class="encabezado-pagina">
        <div>
            <h1 class="titulo-pagina">Clientes</h1>
            <p class="subtitulo-pagina"><span data-filtro-contador="#tablaClientes">{{ $clientes->count() }}</span> clientes</p>
        </div>
        <div class="encabezado-acciones">
            <a href="{{ route('clientes.create') }}" class="btn btn-mazorca"><i class="fas fa-user-plus" aria-hidden="true"></i> Agregar cliente</a>
        </div>
    </div>

    @include("notificacion")

    <div class="filtros">
        <label class="filtro-buscar">
            <span class="oculto">Buscar cliente</span>
            <i class="fas fa-search" aria-hidden="true"></i>
            <input type="search" class="form-control" placeholder="Buscar por nombre o teléfono" data-filtrar-tabla="#tablaClientes" autocomplete="off">
        </label>
    </div>

    <div class="panel p-0 tabla-contenedor">
        <table class="tabla-pos" id="tablaClientes">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Teléfono</th>
                    <th class="num">Ventas</th>
                    <th class="acciones-titulo"><span class="oculto">Acciones</span></th>
                </tr>
            </thead>
            <tbody>
            @foreach($clientes as $cliente)
                <tr>
                    <td><strong>{{ $cliente->nombre }}</strong></td>
                    <td class="cifra">{{ $cliente->telefono }}</td>
                    <td class="num">{{ number_format($cliente->numero_ventas, 0, ',', '.') }}</td>
                    <td class="acciones">
                        <a class="btn-accion" href="{{ route('ventas.index', ['cliente' => $cliente->id]) }}" title="Ver sus ventas en tu caja" aria-label="Ver las ventas de {{ $cliente->nombre }}"><i class="fas fa-receipt" aria-hidden="true"></i></a>
                        <a class="btn-accion" href="{{ route('clientes.edit', $cliente) }}" title="Editar cliente" aria-label="Editar {{ $cliente->nombre }}"><i class="fas fa-pen" aria-hidden="true"></i></a>
                        <button type="button" class="btn-accion peligro" title="Eliminar cliente" aria-label="Eliminar {{ $cliente->nombre }}"
                                data-eliminar="{{ route('clientes.destroy', $cliente) }}"
                                data-titulo="¿Eliminar a {{ $cliente->nombre }}?"
                                data-texto="{{ $cliente->numero_ventas > 0 ? 'Tiene ' . number_format($cliente->numero_ventas, 0, ',', '.') . ' ventas registradas: dejarán de aparecer en los listados de ventas y fiados.' : 'No tiene ventas registradas.' }}">
                            <i class="fas fa-trash-alt" aria-hidden="true"></i>
                        </button>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <p class="tabla-vacia" data-filtro-vacio="#tablaClientes" hidden>No hay clientes con ese nombre o teléfono.</p>
        @if($clientes->isEmpty())
            <p class="tabla-vacia">Aún no hay clientes. <a href="{{ route('clientes.create') }}">Agregar el primero</a></p>
        @endif
    </div>
</div>
@endsection
