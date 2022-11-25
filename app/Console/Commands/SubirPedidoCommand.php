<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\EncabezadoPedidoModel;
use App\Models\DetallePedidoModel;
use App\Jobs\ProcessSubirPedidoSiesa;
use App\Custom\PedidoCore;
use Illuminate\Support\Facades\Log;

class SubirPedidoCommand extends Command
{
    
    protected $signature = 'alpina:subir-pedido';

    protected $description = 'sube pedidos';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $pedidos=$this->obtenerPedidos('0');
        if(!empty($pedidos)){
            foreach ($pedidos as $key => $pedido) {
                $objPedidoDetalle = new DetallePedidoModel();
                $detallePedido = $objPedidoDetalle->obtenerDetallePedido($pedido['numero_pedido'],$pedido['centro_operacion'],$pedido['tipo_documento'],$pedido['bodega']);
                ProcessSubirPedidoSiesa::dispatch($pedido,$detallePedido);
            }
        }

        $pedidosSinConexion=$this->obtenerPedidos('4');
        if(!empty($pedidosSinConexion)){
            foreach ($pedidosSinConexion as $key => $pedido) {
                $objPedidoDetalle = new DetallePedidoModel();
                $detallePedido = $objPedidoDetalle->obtenerDetallePedido($pedido['numero_pedido'],$pedido['centro_operacion'],$pedido['tipo_documento'],$pedido['bodega']);
                ProcessSubirPedidoSiesa::dispatch($pedido,$detallePedido);
            }
        }
    }

    public function obtenerPedidos($estado)
    {
        return json_decode(json_encode(EncabezadoPedidoModel::where('estadoenviows', $estado)->get()),true);
    }
}
