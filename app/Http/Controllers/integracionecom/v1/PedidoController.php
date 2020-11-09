<?php

namespace App\Http\Controllers\integracionecom\v1;

use App\Custom\WebServiceSiesa;
use App\Http\Controllers\Controller;
use Log;
use Illuminate\Http\Request;


class PedidoController extends Controller
{

    public function getPedidoSiesa()
    {

        $objWebserviceSiesa = $this->getWebServiceSiesa(14);
        $pedidos = $objWebserviceSiesa->ejecutarConsulta();

        $arrayValues = [];
        $acumValues = 0;
        foreach ($pedidos as $key => $value) {
            $arrayValuesRow = [];
            foreach ($value as $keyb => $valores) {
                $arrayValuesRow[(String) $keyb] = (String) $valores;
            }            
            $arrayValues[$acumValues] = (array) $arrayValuesRow;
            $acumValues++;
        }        

        return response()->json($arrayValues, 200);

    }

    public function getWebServiceSiesa($idConexion)
    {
        return new WebServiceSiesa($idConexion);
    }

    public function subirPedido(Request $request){

        // $bodyContent = $request->getContent();
        Log::info($request->all());

    }

}
