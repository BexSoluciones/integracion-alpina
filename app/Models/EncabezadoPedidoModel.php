<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DB;
use Log;

class EncabezadoPedidoModel extends Model
{
    protected $connection = 'mysql';

    protected $table="tbldmovenc";

    protected $fillable = [
        'CODMOVENC',
        'centro_operacion',
        'CODTIPODOC',
        'tipo_documento',
        'numero_pedido',
        'cedula_vendedor',
        'NUMVISITA' ,
        'nit_cliente',
        'CODPRECIO',
        'CODDESCUENTO',
        'CODMOTVIS',
        'FECHORINIVISITA',
        'FECHORFINVISITA',
        'EXTRARUTAVISITA',
        'fecha_pedido',
        'CODVEHICULO',
        'MOTENTREGA',
        'FECHORENTREGAMOV',
        'CODFPAGOVTA',
        'NUMCIERRE',
        'FECHORCIERRE',
        'CODGRACIERRE',
        'NUMCARGUE',
        'FECHORCARGUE',
        'DIARUTERO',
        'NUMLIQUIDACION',
        'FECHORLIQUIDACION',
        'ORDENCARGUEMOV',
        'observaciones_pedido',
        'JAVAID',
        'FECCAP',
        'NUMMOVALT',
        'FECHORENTREGACLI',
        'FECNOVEDAD',
        'AUTORIZACION',
        'CODGRAAUTORIZACION',
        'DCTOGLOBAL',
        'NUMCIERREREC',
        'FECHORCIERREREC',
        'CODGRACIERREREC',
        'PROYECTO',
        'EXPORTADO',
        'MENSAJEADIC',
        'CONSCAMPANAOK',
        'CODVENDEDORTRANS',
        'EMAILB2B',
        'ORIGEN',
        'ORDENDECOMPRA',
        'direntrega',
        'tipoentrega',
        'nummovtr',
        'prefmovtr',
        'backorder',
        'prospecto',
        'puntosenvio',
        'estadoenviows',
        'udid',
        'os',
        'ip',
        'tipo_cliente',
        'bodega',
        'sucursal_cliente',
        'vendedor',        
    ];
    public $timestamps=false;
    //eloquent
    // public function obtenerPedidoEncabezado($estadoPedido){
    //     $datos=$this->where('estadoenviows','=',$estadoPedido)
    //                 ->get();
    //     // dump($datos);
    //     if(count($datos)>0){
    //         return $datos;
    //     }else{
    //         return null;
    //     }
    // }

    //DB
    public function obtenerPedidoEncabezado($estado){
        $sql="select * from ".$this->table." where estadoenviows='".$estado."'";          
        $resultadoSql = DB::select($sql);
        //log::info($resultadoSql);     
        return json_decode(json_encode($resultadoSql),true);
    }


}
