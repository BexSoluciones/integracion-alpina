<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });


Route::group([
    'prefix' => 'auth'
], function () {
    Route::post('login', 'Auth\AuthController@login');
    Route::post('signup', 'Auth\AuthController@signUp');

    Route::group([
      'middleware' => 'auth:api'
    ], function() {
        Route::get('logout', 'Auth\AuthController@logout');
        Route::get('integracionecom/v1/pedidos', 'integracionecom\v1\PedidoController@getPedidoSiesa');
        Route::post('integracionecom/v1/pedidos', 'integracionecom\v1\PedidoController@subirPedidoSiesa');
        //Route::post('integracionecom/v1/pedidos', 'integracionecom\v1\IngresoPedidoController@recibirPedidoJson');

        Route::get('integracionecom/v1/compras-devolucion-compras', 'integracionecom\v1\CompraDevolucionCompraController@getComprasDevolucionesCompra');
        Route::get('integracionecom/v1/inventarios', 'integracionecom\v1\InventarioController@getInventario');
        Route::post('integracionecom/v1/clientes', 'integracionecom\v1\ClienteController@saveCliente');
        Route::post('integracionecom/v1/facturas', 'integracionecom\v1\FacturaController@subirFacturaSiesa');
    });
});
