<?php

namespace App\Http\Controllers\integracionecomweb;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EncabezadoPedidoModel;

class ReenvioPedidoController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    
    public function index(Request $request)
    {
// dump($request->all());

        $buscar =$request->input('buscar');
        $registrosXpagina=100;
        if(!empty($buscar)){
            $pedidosError = EncabezadoPedidoModel::where('estadoenviows', '=', "3")->where('numero_pedido','=',$buscar)->paginate($registrosXpagina);
        }else{
            $pedidosError = EncabezadoPedidoModel::where('estadoenviows', '=', "3")->paginate($registrosXpagina);
        }
        
        // dump($pedidosError);
        return view('reenviopedido.index',compact(['pedidosError','buscar']));
    }

    public function reenviarPedido(){

    }
}
