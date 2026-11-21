<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="{{ $negocio->nombre }}">
    <meta name="author" content="Parzibyte">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield("titulo") - {{ $negocio->nombre }}</title>
    <link href="{{url("/css/bootstrap.min.css")}}" rel="stylesheet">
    <link href="{{url("/css/all.min.css")}}" rel="stylesheet">
    <link href="{{url("/css/jquery.dataTables.min.css")}}" rel="stylesheet">
    <link href="{{url("/select_mio/bootstrap-select.min.css")}}" rel="stylesheet">
    <script src="{{url("/css/sweetalert.js")}}"></script>
    <script src="{{url("/js/jquery-3.7.0.js")}}"></script>
    <script src="{{url("/js/jquery.dataTables.min.js")}}"></script>
    <script src="{{url("/vendor/popper/popper.min.js")}}"></script>
    <script src="{{url("/vendor/bootstrap/bootstrap.min.js")}}"></script>
    <script src="{{url("/select_mio/bootstrap-select.min.js")}}"></script>
    <!-- DataTables Buttons + exportación (copias locales: funcionan sin internet) -->
    <link href="{{url("/vendor/datatables-buttons/buttons.dataTables.min.css")}}" rel="stylesheet">
    <script src="{{url("/vendor/pdfmake/pdfmake.min.js")}}"></script>
    <script src="{{url("/vendor/pdfmake/vfs_fonts.js")}}"></script>
    <script src="{{url("/vendor/datatables-buttons/dataTables.buttons.min.js")}}"></script>
    <script src="{{url("/vendor/jszip/jszip.min.js")}}"></script>
    <script src="{{url("/vendor/datatables-buttons/buttons.html5.min.js")}}"></script>
    <!-- Sistema visual (paleta, fuentes y componentes): va al final para sobrescribir Bootstrap -->
    <link href="{{url("/css/pos.css")}}?v={{ filemtime(public_path('css/pos.css')) }}" rel="stylesheet">
</head>
@php
    $esAdmin = Auth::check() && Auth::user()->email == 'admin';
    $iniciales = $negocio->iniciales();
    $menuCompacto = View::hasSection('menu_compacto');
@endphp
<body class="{{ Auth::check() ? 'con-menu' : 'sin-menu' }} {{ $menuCompacto ? 'menu-compacto' : '' }} @yield('clase_body')" @if($menuCompacto) data-compacto-fijo="1" @endif>
@auth
<script>
    // Recordar si el menú está contraído (antes de pintar, para que no parpadee)
    try { if (localStorage.getItem("menu-compacto") === "1") document.body.classList.add("menu-compacto"); } catch (e) {}
</script>

<aside class="lateral" id="lateral" aria-label="Menú principal">
    <a class="lateral-marca" href="{{ route('home') }}" title="{{ $negocio->nombre }}">
        @if($negocio->urlLogo())
            <img class="lateral-logo" src="{{ $negocio->urlLogo() }}" alt="">
        @else
            <span class="lateral-logo" aria-hidden="true">{{ $iniciales }}</span>
        @endif
        <span class="lateral-nombre">{{ $negocio->nombre }}</span>
    </a>

    <a class="lateral-vender {{ request()->routeIs('vender.*') ? 'activo' : '' }}" href="{{ route('vender.index') }}" data-nombre="Vender">
        <i class="fas fa-cash-register" aria-hidden="true"></i><span>Vender</span>
    </a>

    @php
        // Cada sección del menú se abre solo si contiene la página actual
        $ruta = request();
        $activos = [
            'ventas' => $ruta->routeIs('ventas.*'),
            'compras' => $ruta->routeIs('compras.*'),
            'proveedores' => $ruta->routeIs('proveedores.*'),
            'productos' => $ruta->routeIs('productos.*') && !$ruta->routeIs('productos.alert'),
            'stock' => $ruta->routeIs('productos.alert'),
            'clientes' => $ruta->routeIs('clientes.*'),
            'fiados' => $ruta->routeIs('usuarios.deudores', 'usuarios.infoDeuda'),
            'usuarios' => $ruta->routeIs('usuarios.index', 'usuarios.edit', 'usuarios.show'),
            'cajas' => $ruta->routeIs('cajas.*'),
            'registrar' => $ruta->routeIs('register', 'usuarios.create'),
            'negocio' => $ruta->routeIs('negocio.*'),
        ];
        $secciones = [
            'operacion' => $activos['ventas'] || $activos['compras'] || $activos['proveedores'],
            'inventario' => $activos['productos'] || $activos['stock'],
            'personas' => $activos['clientes'] || $activos['fiados'] || $activos['usuarios'],
            'administracion' => $activos['cajas'] || $activos['registrar'] || $activos['negocio'],
        ];
    @endphp
    <nav class="lateral-nav">
        <a class="{{ $ruta->routeIs('home') ? 'activo' : '' }}" href="{{ route('home') }}" data-nombre="Inicio">
            <i class="fas fa-home" aria-hidden="true"></i><span>Inicio</span>
        </a>

        <div class="lateral-seccion {{ $secciones['operacion'] ? 'abierta' : '' }}">
            <button type="button" class="lateral-grupo" aria-expanded="{{ $secciones['operacion'] ? 'true' : 'false' }}" aria-controls="menuOperacion">
                <span>Operación</span><i class="fas fa-chevron-down" aria-hidden="true"></i>
            </button>
            <div class="lateral-items" id="menuOperacion">
                {{-- "Ventas por fecha" ahora es un filtro dentro de Ventas --}}
                <a class="{{ $activos['ventas'] ? 'activo' : '' }}" href="{{ route('ventas.index') }}" data-nombre="Ventas">
                    <i class="fas fa-receipt" aria-hidden="true"></i><span>Ventas</span>
                </a>
                <a class="{{ $activos['compras'] ? 'activo' : '' }}" href="{{ route('compras.index') }}" data-nombre="Compras">
                    <i class="fas fa-truck-loading" aria-hidden="true"></i><span>Compras</span>
                </a>
                <a class="{{ $activos['proveedores'] ? 'activo' : '' }}" href="{{ route('proveedores.index') }}" data-nombre="Proveedores">
                    <i class="fas fa-truck" aria-hidden="true"></i><span>Proveedores</span>
                </a>
            </div>
        </div>

        <div class="lateral-seccion {{ $secciones['inventario'] ? 'abierta' : '' }}">
            <button type="button" class="lateral-grupo" aria-expanded="{{ $secciones['inventario'] ? 'true' : 'false' }}" aria-controls="menuInventario">
                <span>Inventario</span>
                @if($productosEnAlerta > 0)
                    <b class="lateral-contador solo-cerrada" aria-label="{{ $productosEnAlerta }} productos con stock bajo">{{ $productosEnAlerta }}</b>
                @endif
                <i class="fas fa-chevron-down" aria-hidden="true"></i>
            </button>
            <div class="lateral-items" id="menuInventario">
                <a class="{{ $activos['productos'] ? 'activo' : '' }}" href="{{ route('productos.index') }}" data-nombre="Productos">
                    <i class="fas fa-shopping-basket" aria-hidden="true"></i><span>Productos</span>
                </a>
                <a class="{{ $activos['stock'] ? 'activo' : '' }}" href="{{ route('productos.alert') }}" data-nombre="Stock bajo: {{ $productosEnAlerta }}">
                    <i class="fas fa-exclamation-triangle" aria-hidden="true"></i><span>Stock bajo</span>
                    @if($productosEnAlerta > 0)
                        <b class="lateral-contador" aria-label="{{ $productosEnAlerta }} productos con stock bajo">{{ $productosEnAlerta }}</b>
                    @endif
                </a>
            </div>
        </div>

        <div class="lateral-seccion {{ $secciones['personas'] ? 'abierta' : '' }}">
            <button type="button" class="lateral-grupo" aria-expanded="{{ $secciones['personas'] ? 'true' : 'false' }}" aria-controls="menuPersonas">
                <span>Personas</span><i class="fas fa-chevron-down" aria-hidden="true"></i>
            </button>
            <div class="lateral-items" id="menuPersonas">
                <a class="{{ $activos['clientes'] ? 'activo' : '' }}" href="{{ route('clientes.index') }}" data-nombre="Clientes">
                    <i class="fas fa-user-friends" aria-hidden="true"></i><span>Clientes</span>
                </a>
                <a class="{{ $activos['fiados'] ? 'activo' : '' }}" href="{{ route('usuarios.deudores') }}" data-nombre="Fiados">
                    <i class="fas fa-book" aria-hidden="true"></i><span>Fiados</span>
                </a>
                <a class="{{ $activos['usuarios'] ? 'activo' : '' }}" href="{{ route('usuarios.index') }}" data-nombre="Usuarios">
                    <i class="fas fa-id-badge" aria-hidden="true"></i><span>Usuarios</span>
                </a>
            </div>
        </div>

        @if($esAdmin)
            <div class="lateral-seccion {{ $secciones['administracion'] ? 'abierta' : '' }}">
                <button type="button" class="lateral-grupo" aria-expanded="{{ $secciones['administracion'] ? 'true' : 'false' }}" aria-controls="menuAdministracion">
                    <span>Administración</span><i class="fas fa-chevron-down" aria-hidden="true"></i>
                </button>
                <div class="lateral-items" id="menuAdministracion">
                    <a class="{{ $activos['cajas'] ? 'activo' : '' }}" href="{{ route('cajas.index') }}" data-nombre="Cajas">
                        <i class="fas fa-cash-register" aria-hidden="true"></i><span>Cajas</span>
                    </a>
                    <a class="{{ $activos['registrar'] ? 'activo' : '' }}" href="{{ route('usuarios.create') }}" data-nombre="Registrar usuario">
                        <i class="fas fa-user-plus" aria-hidden="true"></i><span>Registrar usuario</span>
                    </a>
                    <a class="{{ $activos['negocio'] ? 'activo' : '' }}" href="{{ route('negocio.edit') }}" data-nombre="Datos del negocio">
                        <i class="fas fa-store" aria-hidden="true"></i><span>Datos del negocio</span>
                    </a>
                </div>
            </div>
        @endif
        {{-- Domicilios sigue oculto (decisión pendiente en docs/plan-mejora-interfaz.md) --}}
    </nav>

    <div class="lateral-pie">
        <button type="button" class="lateral-contraer" id="botonContraer" data-nombre="Expandir menú" aria-label="Contraer o expandir el menú">
            <i class="fas fa-angle-double-left" aria-hidden="true"></i><span>Contraer menú</span>
        </button>
        @if($negocio->firma_software)
            <a class="lateral-firma" href="{{ route('acerca_de.index') }}">{{ $negocio->firma_software }}</a>
        @endif
    </div>
</aside>
<div class="lateral-velo" id="lateralVelo"></div>

<header class="barra-superior">
    <button type="button" class="barra-menu" id="botonMenu" aria-label="Mostrar el menú" aria-controls="lateral">
        <i class="fas fa-bars" aria-hidden="true"></i>
    </button>
    <div class="barra-titulo">@yield("titulo")</div>
    <div class="barra-usuario">
        <span class="barra-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}</span>
        <span class="barra-nombre">{{ Auth::user()->name }}</span>
        <a class="barra-salir" href="{{ route('logout') }}"><i class="fas fa-sign-out-alt" aria-hidden="true"></i><span>Salir</span></a>
    </div>
</header>
@endauth

<script type="text/javascript">
    var vueltas = 0;
    var numero_anterior = 0;
    document.addEventListener("DOMContentLoaded", () => {
        const cuerpo = document.body,
            botonMenu = document.getElementById("botonMenu"),
            botonContraer = document.getElementById("botonContraer"),
            velo = document.getElementById("lateralVelo");
        if (!botonMenu) return;

        // Pantallas pequeñas: el menú entra y sale por la izquierda
        const cerrar = () => cuerpo.classList.remove("menu-abierto");
        botonMenu.addEventListener("click", () => cuerpo.classList.toggle("menu-abierto"));
        velo.addEventListener("click", cerrar);
        document.addEventListener("keydown", e => { if (e.key === "Escape") cerrar(); });

        // Secciones del menú: se abren y cierran con un clic (al cargar solo está abierta la de la página actual)
        document.querySelectorAll(".lateral-seccion > .lateral-grupo").forEach((boton) => {
            boton.addEventListener("click", () => {
                const abierta = boton.parentElement.classList.toggle("abierta");
                boton.setAttribute("aria-expanded", abierta ? "true" : "false");
            });
        });

        // Pantallas grandes: contraer a solo iconos (se recuerda en este navegador)
        botonContraer.addEventListener("click", () => {
            const compacto = cuerpo.classList.toggle("menu-compacto");
            // En pantallas que piden el menú contraído (Vender) el cambio no se guarda
            if (cuerpo.dataset.compactoFijo) return;
            try { localStorage.setItem("menu-compacto", compacto ? "1" : "0"); } catch (e) {}
        });
    });

    //obtenerDomiciliosP();
    //setInterval(obtenerDomiciliosP, 10000);

    function obtenerDomiciliosP(){
        vueltas += 1;
        $.ajax({
            url: 'https://provisiones-carlosandres.shop/ver_domicilios.php',
            type: 'GET',
            success: function(response) {
                response = JSON.parse(response);
                if(vueltas > 1){
                    if(response.length > numero_anterior){
                        var audio = new Audio('/sounds/sound.mp3');
                        audio.play();
                        numero_anterior = response.length;
                    }
                }else{
                    numero_anterior = response.length;
                }
                document.getElementById("bad").innerHTML = response.length;
            }
        });
        return false;
    }

</script>
<main class="contenido container-fluid">
    @yield("contenido")
</main>
<script src="{{ url('/js/pos.js') }}?v={{ filemtime(public_path('js/pos.js')) }}"></script>
</body>
</html>
