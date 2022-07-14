<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\EncabezadoPedidoModel;
use App\Models\DetallePedidoModel;
use App\Jobs\ProcessSubirPedidoSiesa;
use App\Custom\PedidoCore;
use Log;

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
        $pedidos=$this->obtenerPedidos();
        if(!empty($pedidos)){
            foreach ($pedidos as $key => $pedido) {
                $objPedidoDetalle = new DetallePedidoModel();
                $detallePedido = $objPedidoDetalle->obtenerDetallePedido($pedido['numero_pedido'],$pedido['centro_operacion'],$pedido['tipo_documento'],$pedido['bodega']);
                ProcessSubirPedidoSiesa::dispatch($pedido,$detallePedido);
            }
        }

        $pedidosSinConexion=$this->obtenerPedidosErrorWs();
        if(!empty($pedidosSinConexion)){
            foreach ($pedidosSinConexion as $key => $pedido) {
                $objPedidoDetalle = new DetallePedidoModel();
                $detallePedido = $objPedidoDetalle->obtenerDetallePedido($pedido['numero_pedido'],$pedido['centro_operacion'],$pedido['tipo_documento'],$pedido['bodega']);
                ProcessSubirPedidoSiesa::dispatch($pedido,$detallePedido);
            }
        }

        // $pedidos=$this->obtenerPedidos();
        // if(!empty($pedidos)){
        //     foreach ($pedidos as $key => $pedido) {
        //         $objPedidoDetalle = new DetallePedidoModel();
        //         $detallePedido = $objPedidoDetalle->obtenerDetallePedido($pedido['numero_pedido'],$pedido['centro_operacion'],$pedido['tipo_documento'],$pedido['bodega']);
        //         $objPedidoCore= new PedidoCore();
        //         $objPedidoCore->subirPedidoSiesa($pedido,$detallePedido);
        //     }
        // }
    }

    public function obtenerPedidos()
    {
        $estado="0";
        $objPedidoEncabezado = new EncabezadoPedidoModel();
        return $objPedidoEncabezado->obtenerPedidoEncabezado($estado);
    }

    public function obtenerPedidosErrorWs()
    {
        $estado="4";
        $objPedidoEncabezado = new EncabezadoPedidoModel();
        return $objPedidoEncabezado->obtenerPedidoEncabezado($estado);
    }

}
