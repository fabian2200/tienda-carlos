@extends("maestra")
@section("titulo", "Registrar usuario")
@section('contenido')
<div class="pagina pagina-formulario">
    <div class="encabezado-pagina">
        <div>
            <h1 class="titulo-pagina">Registrar usuario</h1>
            <p class="subtitulo-pagina">Para cajas nuevas es más práctico <a href="{{ route('usuarios.create') }}">Agregar usuario</a>: aquí el usuario debe ser un correo electrónico.</p>
        </div>
    </div>

    @if(session('success') == 1)
        <div class="alert alert-success">{{ session('message') }}</div>
    @elseif(session('success') == 2)
        <div class="alert alert-danger">{{ session('message') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <strong>Revisa estos datos:</strong>
            <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('register') }}" autocomplete="off">
        @csrf
        <section class="panel">
            <div class="form-group">
                <label for="name">Nombre</label>
                <input id="name" type="text" name="name" value="{{ old('name') }}" class="form-control input-grande @error('name') is-invalid @enderror" required autofocus>
            </div>
            <div class="form-group">
                <label for="email">Usuario (correo electrónico)</label>
                <input id="email" type="text" name="email" value="{{ old('email') }}" class="form-control input-grande @error('email') is-invalid @enderror" required autocapitalize="none" spellcheck="false">
            </div>
            <div class="form-row mb-0">
                <div class="form-group col-sm-6 mb-0">
                    <label for="password">Contraseña</label>
                    <input id="password" type="password" name="password" class="form-control input-grande @error('password') is-invalid @enderror" required autocomplete="new-password">
                </div>
                <div class="form-group col-sm-6 mb-0">
                    <label for="password-confirm">Repite la contraseña</label>
                    <input id="password-confirm" type="password" name="password_confirmation" class="form-control input-grande" required autocomplete="new-password">
                </div>
            </div>
        </section>
        <div class="formulario-pie">
            <a class="btn btn-secondary" href="{{ route('usuarios.index') }}">Cancelar</a>
            <button type="submit" class="btn btn-mazorca">Registrar usuario</button>
        </div>
    </form>
</div>
@endsection
