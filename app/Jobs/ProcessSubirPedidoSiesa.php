<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Custom\PedidoCore;
use Log;

class ProcessSubirPedidoSiesa implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $pedido;
    
    protected $detallePedido;
    public function __construct($pedido,$detallePedido)
    {
        $this->pedido= $pedido;
        $this->detallePedido= $detallePedido;

    }

    
    public function handle()
    {
        // Log::info('=========imprimiendo datos recibidos al job=====');
        // Log::info($this->pedido);
        // Log::info($this->detallePedido);
        $objPedidoCore=new PedidoCore();
        $objPedidoCore->subirPedidoSiesa($this->pedido,$this->detallePedido);

    }
}
