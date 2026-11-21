{{-- Formulario compartido de agregar y editar cliente. Recibe $cliente (null al agregar). --}}
@if($errors->any())
    <div class="alert alert-danger">
        <strong>Revisa estos datos:</strong>
        <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif
<section class="panel">
    <div class="form-group">
        <label for="nombre">Nombre</label>
        <input id="nombre" name="nombre" class="form-control input-grande" required autocomplete="off" autofocus
               value="{{ old('nombre', optional($cliente)->nombre) }}" placeholder="Nombre y apellido">
    </div>
    <div class="form-group mb-0">
        <label for="telefono">Teléfono</label>
        <input id="telefono" name="telefono" class="form-control input-grande cifra" required autocomplete="off" inputmode="tel"
               value="{{ old('telefono', optional($cliente)->telefono) }}" placeholder="Ej.: 300 123 4567">
    </div>
</section>
<div class="formulario-pie">
    <a class="btn btn-secondary" href="{{ route('clientes.index') }}">Cancelar</a>
    <button type="submit" class="btn btn-mazorca">{{ $cliente ? 'Guardar cambios' : 'Agregar cliente' }}</button>
</div>
