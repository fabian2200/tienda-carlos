{{--

  ____          _____               _ _           _
 |  _ \        |  __ \             (_) |         | |
 | |_) |_   _  | |__) |_ _ _ __ _____| |__  _   _| |_ ___
 |  _ <| | | | |  ___/ _` | '__|_  / | '_ \| | | | __/ _ \
 | |_) | |_| | | |  | (_| | |   / /| | |_) | |_| | ||  __/
 |____/ \__, | |_|   \__,_|_|  /___|_|_.__/ \__, |\__\___|
         __/ |                               __/ |
        |___/                               |___/

    Blog:       https://parzibyte.me/blog
    Ayuda:      https://parzibyte.me/blog/contrataciones-ayuda/
    Contacto:   https://parzibyte.me/blog/contacto/

    Copyright (c) 2020 Luis Cabrera Benito
    Licenciado bajo la licencia MIT

    El texto de arriba debe ser incluido en cualquier redistribucion
--}}
@extends("maestra")
@section("titulo", "Acerca del sistema")
@section("contenido")
<div class="pagina pagina-formulario">
    <div class="encabezado-pagina">
        <div>
            <h1 class="titulo-pagina">Acerca del sistema</h1>
            <p class="subtitulo-pagina">{{ $negocio->firma_software ?: 'Punto de venta' }}</p>
        </div>
    </div>

    <section class="panel acerca">
        <h2 class="panel-titulo">Punto de venta de {{ $negocio->nombre }}</h2>
        <p class="mb-0">
            Basado en el sistema de ventas en Laravel de
            <a href="https://parzibyte.me/blog" target="_blank" rel="noopener">Luis Cabrera Benito (parzibyte)</a>,
            publicado con licencia MIT.
        </p>
    </section>

    <section class="panel acerca">
        <h2 class="panel-titulo">Licencia del código original</h2>
        <pre class="acerca-licencia">Copyright (c) 2020 Luis Cabrera Benito
Licenciado bajo la licencia MIT

Blog:       https://parzibyte.me/blog
Ayuda:      https://parzibyte.me/blog/contrataciones-ayuda/
Contacto:   https://parzibyte.me/blog/contacto/

El texto de arriba debe ser incluido en cualquier redistribución</pre>
    </section>

    <section class="panel acerca">
        <h2 class="panel-titulo">Tipografías e iconos</h2>
        <ul class="mb-0">
            <li>Atkinson Hyperlegible, de Braille Institute (SIL Open Font License).</li>
            <li>Bricolage Grotesque, de Mathieu Triay (SIL Open Font License).</li>
            <li>IBM Plex Mono, de IBM (SIL Open Font License).</li>
            <li>Iconos de Font Awesome Free (CC BY 4.0).</li>
        </ul>
    </section>
</div>
@endsection
