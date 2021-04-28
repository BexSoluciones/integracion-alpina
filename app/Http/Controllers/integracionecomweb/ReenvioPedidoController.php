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
        $pedido =$request->input('buscar');
        $pedidosError = EncabezadoPedidoModel::where('estadoenviows', '=', "3")->paginate(100);
        // dump($pedidosError);
        return view('reenviopedido.index',compact(['pedidosError']));
    }

    public function reenviarPedido(){
        
    }
}
