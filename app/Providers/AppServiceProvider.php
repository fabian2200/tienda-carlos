<?php

namespace App\Providers;

use App\Negocio;
use App\Producto;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Datos del negocio (tabla `negocio`) disponibles en todas las vistas como $negocio.
        View::composer("*", function ($view) {
            $view->with("negocio", Negocio::actual());
        });

        // Contador del menú lateral "Stock bajo" (solo en el layout y con sesión iniciada).
        View::composer("maestra", function ($view) {
            $view->with("productosEnAlerta", Auth::check()
                ? Producto::where("existencia", "<", Producto::UMBRAL_ALERTA)->count()
                : 0);
        });
    }
}
