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
        Route::get('integracionalpina/v1/pedidos', 'integracionalpina\v1\PedidoController@getPedidoSiesa');
        //Route::post('integracionecom/v1/pedidos', 'integracionecom\v1\PedidoController@subirPedidoSiesa'); 
        Route::post('integracionalpina/v1/pedidos', 'integracionalpina\v1\IngresoPedidoController@recibirPedidoJson');
        
        Route::get('integracionalpina/v1/log-pedidos', 'integracionalpina\v1\LogPedidoController@getLogPedido');
        Route::get('integracionalpina/v1/compras-devolucion-compras', 'integracionalpina\v1\CompraDevolucionCompraController@getComprasDevolucionesCompra');
        Route::get('integracionalpina/v1/inventarios', 'integracionalpina\v1\InventarioController@getInventario');
        Route::post('integracionalpina/v1/clientes', 'integracionalpina\v1\ClienteController@saveCliente');
        Route::post('integracionalpina/v1/facturas', 'integracionalpina\v1\FacturaController@subirFacturaSiesa');
    });
});
