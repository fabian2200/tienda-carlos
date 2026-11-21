@extends("maestra")
@section("titulo", "Usuarios")
@section("contenido")
<div class="pagina pagina-media">
    <div class="encabezado-pagina">
        <div>
            <h1 class="titulo-pagina">Usuarios</h1>
            <p class="subtitulo-pagina">Cada usuario es una caja: sus ventas quedan a su nombre.</p>
        </div>
        <div class="encabezado-acciones">
            <a href="{{ route('usuarios.create') }}" class="btn btn-mazorca"><i class="fas fa-user-plus" aria-hidden="true"></i> Agregar usuario</a>
        </div>
    </div>

    @include("notificacion")

    <div class="panel p-0 tabla-contenedor">
        <table class="tabla-pos">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Usuario para entrar</th>
                    <th class="num">Ventas</th>
                    <th class="acciones-titulo"><span class="oculto">Acciones</span></th>
                </tr>
            </thead>
            <tbody>
            @foreach($usuarios as $usuario)
                <tr>
                    <td>
                        <div class="producto-celda">
                            <span class="barra-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($usuario->name, 0, 1)) }}</span>
                            <span class="producto-texto">
                                <strong>{{ $usuario->name }}</strong>
                                @if($usuario->id === Auth::id())<small>Sesión actual</small>@endif
                            </span>
                        </div>
                    </td>
                    <td class="cifra">{{ $usuario->email }}</td>
                    <td class="num">{{ number_format($usuario->numero_ventas, 0, ',', '.') }}</td>
                    <td class="acciones">
                        <a class="btn-accion" href="{{ route('usuarios.edit', $usuario) }}" title="Editar usuario" aria-label="Editar {{ $usuario->name }}"><i class="fas fa-pen" aria-hidden="true"></i></a>
                        @if($usuario->id !== Auth::id())
                            <button type="button" class="btn-accion peligro" title="Eliminar usuario" aria-label="Eliminar {{ $usuario->name }}"
                                    data-eliminar="{{ route('usuarios.destroy', $usuario) }}"
                                    data-titulo="¿Eliminar a {{ $usuario->name }}?"
                                    data-texto="Ya no podrá entrar al sistema.{{ $usuario->numero_ventas > 0 ? ' Sus ' . number_format($usuario->numero_ventas, 0, ',', '.') . ' ventas se conservan, pero ya no se verán en Cajas.' : '' }}">
                                <i class="fas fa-trash-alt" aria-hidden="true"></i>
                            </button>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
