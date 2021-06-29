<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DB;

class DetallePedidoModel extends Model
{
    protected $connection = 'mysql';

    protected $table="tbldmovdet";

    protected $fillable = [
        'CODMOVDET',
        'CODMOVENC',
        'centro_operacion',
        'CODTIPODOC',
        'tipo_documento',
        'numero_pedido',
        'bodega',
        'codigo_producto',
        'cantidad',
        'precio_unitario',
        'IVAMOV',
        'CODMOTDEV',
        'BONENTREGAPRODUCTO',
        'DCTO1MOV',
        'DCTO2MOV',
        'DCTO3MOV',
        'DCTO4MOV',
        'lista_precio',
        'javaid',
        'DCTONC',
        'DCTOPIEFACAUT',
        'FACTOR',
        'BONIFICADO',
        'PREPACK',
        'AUTORIZACION',
        'CANTID1',
        'CANTID2',
        'UNIDAD01',
        'UNIDAD02',
        'OBSEQUIO1',
        'OBSEQUIO2',
        'IDLISPRE',
        'dctovalor',
        'autovalor',
        'ocultorowid',
        'ocultoporcval',
        'rowid',
        'cantidadpines' ,
        'codmotpines',
        'impconsumo',
        'lote',
        'peso',
        'fletesimple',       
    ];
    public $timestamps=false;


    public function obtenerDetallePedido($numeroPedido,$centroOperacion,$tipoDocumento,$bodega){

        $sql="select * from ".$this->table." where numero_pedido='".$numeroPedido."' AND centro_operacion='".$centroOperacion."' AND tipo_documento='".$tipoDocumento."' AND bodega='".$bodega."'";          
        $resultadoSql = DB::select($sql);
        return json_decode(json_encode($resultadoSql),true);
    }
}

