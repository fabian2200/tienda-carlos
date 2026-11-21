{{-- Formulario compartido de agregar y editar usuario. Recibe $usuario (null al agregar). --}}
@if($errors->any())
    <div class="alert alert-danger">
        <strong>Revisa estos datos:</strong>
        <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif
<section class="panel">
    <div class="form-group">
        <label for="name">Nombre</label>
        <input id="name" name="name" class="form-control input-grande" required autocomplete="off" autofocus
               value="{{ old('name', optional($usuario)->name) }}" placeholder="Ej.: Caja 4">
        <p class="ayuda-campo mb-0">Es el nombre que sale en el ticket como "Vendido por".</p>
    </div>
    <div class="form-group">
        <label for="email">Usuario para entrar</label>
        <input id="email" name="email" type="text" class="form-control input-grande cifra" required autocomplete="off"
               autocapitalize="none" spellcheck="false"
               value="{{ old('email', optional($usuario)->email) }}" placeholder="Ej.: caja4">
    </div>
    <div class="form-group mb-0">
        <label for="password">{{ $usuario ? 'Contraseña nueva' : 'Contraseña' }}</label>
        <input id="password" name="password" type="password" class="form-control input-grande" autocomplete="new-password"
               @if(!$usuario) required @endif minlength="4">
        <p class="ayuda-campo mb-0">{{ $usuario ? 'Déjala vacía para conservar la contraseña actual.' : 'Mínimo 4 caracteres.' }}</p>
    </div>
</section>
<div class="formulario-pie">
    <a class="btn btn-secondary" href="{{ route('usuarios.index') }}">Cancelar</a>
    <button type="submit" class="btn btn-mazorca">{{ $usuario ? 'Guardar cambios' : 'Agregar usuario' }}</button>
</div>
