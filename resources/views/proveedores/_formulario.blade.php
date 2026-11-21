{{-- Formulario compartido de agregar y editar proveedor. Recibe $proveedor (null al agregar). --}}
@if($errors->any())
    <div class="alert alert-danger">
        <strong>Revisa estos datos:</strong>
        <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif
@php $valor = function ($campo) use ($proveedor) { return old($campo, optional($proveedor)->$campo); }; @endphp
<section class="panel">
    <div class="form-group">
        <label for="nombre">Nombre</label>
        <input id="nombre" name="nombre" class="form-control input-grande" required maxlength="150" autocomplete="off" autofocus
               value="{{ $valor('nombre') }}" placeholder="Ej.: Distribuidora Postobón">
    </div>
    <div class="form-row">
        <div class="form-group col-sm-6">
            <label for="nit">NIT <small class="text-muted">(opcional)</small></label>
            <input id="nit" name="nit" class="form-control input-grande cifra" maxlength="30" autocomplete="off" value="{{ $valor('nit') }}">
        </div>
        <div class="form-group col-sm-6">
            <label for="telefono">Teléfono</label>
            <input id="telefono" name="telefono" class="form-control input-grande cifra" maxlength="40" autocomplete="off" inputmode="tel" value="{{ $valor('telefono') }}">
        </div>
    </div>
    <div class="form-row">
        <div class="form-group col-sm-6">
            <label for="contacto">Persona de contacto</label>
            <input id="contacto" name="contacto" class="form-control input-grande" maxlength="120" autocomplete="off" value="{{ $valor('contacto') }}" placeholder="Ej.: el vendedor que visita">
        </div>
        <div class="form-group col-sm-6">
            <label for="direccion">Dirección</label>
            <input id="direccion" name="direccion" class="form-control input-grande" maxlength="150" autocomplete="off" value="{{ $valor('direccion') }}">
        </div>
    </div>
    <div class="form-group mb-0">
        <label for="notas">Notas</label>
        <input id="notas" name="notas" class="form-control input-grande" maxlength="255" autocomplete="off" value="{{ $valor('notas') }}" placeholder="Ej.: visita los martes, pedido mínimo $ 200.000">
    </div>
</section>
<div class="formulario-pie">
    <a class="btn btn-secondary" href="{{ route('proveedores.index') }}">Cancelar</a>
    <button type="submit" class="btn btn-mazorca">{{ $proveedor ? 'Guardar cambios' : 'Agregar proveedor' }}</button>
</div>
