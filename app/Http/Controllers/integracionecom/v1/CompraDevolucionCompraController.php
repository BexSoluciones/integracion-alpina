<?php

namespace App\Http\Controllers\integracionecom\v1;

use App\Custom\WebServiceSiesa;
use App\Http\Controllers\Controller;
use App\Traits\TraitHerramientas;
use App\Models\ConexionesModel;
use Illuminate\Http\Request;
use Log;
use Storage;
use Validator;
use Illuminate\Support\Facades\Route; 
$currentPath= Route::getFacadeRoot()->current()->uri(); 

class CompraDevolucionCompraController extends Controller
{
    use TraitHerramientas;


    public function getComprasDevolucionesCompra(Request $request){

        

        Log::info($request->all());
        $filasXpagina = $request->input('row_per_page') && ctype_digit($request->input('row_per_page'))?$request->input('row_per_page'):10;
        $pagina       = $request->input('page') && is_numeric($request->input('page'))?$request->input('page'):1;
        $desde        = (((int) ($pagina) - 1) * (int) ($filasXpagina));
        $hasta        = (int) ($filasXpagina);
        $atras        = $pagina > 2 ? $pagina - 1 : 1;
        $siguiente    = $pagina + 1;
        $estaRuta     =  Route::getFacadeRoot()->current()->uri(); 

        $parametros = [
            ['desde' => $desde],
            ['hasta' => $hasta],
        ];

        Log::info($parametros);

        $idConexion=29;//id conexion get compras y devoluciones de compra
        $objWebserviceSiesa = $this->getWebServiceSiesa($idConexion);
        $datos = $objWebserviceSiesa->ejecutarConsulta($parametros);

        $respuesta=[
            'code'=>200,
            'next'=>url('/').'/'.$estaRuta.'?page='.$siguiente,
            'data'=>$datos,
        ];
        return response()->json($respuesta, 200);

    }

    public function getWebServiceSiesa($idConexion)
    {
        return new WebServiceSiesa($idConexion);
    }

    public function getConexionesModel()
    {
        return new ConexionesModel();
    }

}
