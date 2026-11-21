<?php

namespace App\Http\Controllers;

use App\Negocio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Pantalla "Datos del negocio": edita la única fila de la tabla `negocio`.
 * Solo el administrador (mismo criterio que el resto del sistema).
 */
class NegocioController extends Controller
{
    const CARPETA_LOGO = "img/negocio";

    public function edit()
    {
        $this->soloAdmin();
        return view("negocio.negocio_edit", ["datos" => Negocio::actual()]);
    }

    public function update(Request $request)
    {
        $this->soloAdmin();

        $datos = $request->validate([
            "nombre" => "required|string|max:150",
            "nit" => "required|string|max:30",
            "direccion" => "required|string|max:150",
            "barrio" => "nullable|string|max:100",
            "ciudad" => "nullable|string|max:100",
            "telefono" => "nullable|string|max:30",
            "email" => "nullable|email|max:120",
            "regimen" => "nullable|string|max:80",
            "resolucion_dian" => "nullable|string|max:255",
            "mensaje_pie" => "nullable|string|max:255",
            "firma_software" => "nullable|string|max:120",
            "logo" => "nullable|image|mimes:png,jpg,jpeg,webp|max:2048",
        ], [], [
            "nit" => "NIT",
            "direccion" => "dirección",
            "telefono" => "teléfono",
            "regimen" => "régimen",
            "resolucion_dian" => "resolución DIAN",
            "mensaje_pie" => "mensaje de despedida",
            "firma_software" => "firma del software",
        ]);

        $negocio = Negocio::query()->orderBy("id")->first() ?: new Negocio();
        $logoAnterior = $negocio->logo;
        unset($datos["logo"]);
        $negocio->fill($datos);

        if ($request->hasFile("logo")) {
            $archivo = $request->file("logo");
            $nombre = "logo-" . time() . "." . strtolower($archivo->getClientOriginalExtension());
            $archivo->move(public_path(self::CARPETA_LOGO), $nombre);
            $negocio->logo = self::CARPETA_LOGO . "/" . $nombre;
            $this->borrarLogo($logoAnterior);
        } elseif ($request->boolean("quitar_logo")) {
            $negocio->logo = null;
            $this->borrarLogo($logoAnterior);
        }

        $negocio->save();

        return redirect()->route("negocio.edit")
            ->with("mensaje", "Datos del negocio guardados. Los próximos tickets ya salen con estos datos.")
            ->with("tipo", "success");
    }

    private function borrarLogo($ruta)
    {
        if ($ruta && strpos($ruta, self::CARPETA_LOGO . "/") === 0 && is_file(public_path($ruta))) {
            @unlink(public_path($ruta));
        }
    }

    private function soloAdmin()
    {
        abort_unless(Auth::user() && Auth::user()->email == "admin", 403, "Solo el administrador puede cambiar los datos del negocio.");
    }
}
