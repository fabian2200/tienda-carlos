@if(session("mensaje"))
    @php
        // Tipos de Bootstrap que usan los controladores → iconos de SweetAlert
        $tipo = session("tipo") ?: "info";
        $icono = ["success" => "success", "danger" => "error", "error" => "error", "warning" => "warning"][$tipo] ?? "info";
    @endphp
    {{-- Respaldo si SweetAlert no carga: el mensaje se sigue viendo --}}
    <noscript><div class="alert alert-{{ $tipo }}">{{ session("mensaje") }}</div></noscript>
    <script>
        (function () {
            const mostrar = () => Swal.fire({
                toast: true,
                position: "top-end",
                icon: @json($icono),
                title: @json(session("mensaje")),
                showConfirmButton: false,
                timer: 3500,
                timerProgressBar: true,
                customClass: { popup: "aviso-pos" }
            });
            if (window.Swal) { mostrar(); } else { document.addEventListener("DOMContentLoaded", mostrar); }
        })();
    </script>
@endif
