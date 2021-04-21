<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DB;
use Log;

class LogErrorImportacionModel extends Model
{
    protected $connection = 'mysql';

    protected $table="tbldmovenc";

    public function actualizarEstadoDocumento($mensaje, $estado, $centroOperacion, $bodega, $tipoDocumento, $numeroPedido){

        $fechaHora = date('Y-m-d h:m:s');
        $sql="update ".$this->table." SET estadoenviows ='".$estado."', msmovws='".$mensaje."', fechamovws ='".$fechaHora."' where centro_operacion ='".$centroOperacion."' AND bodega ='".$bodega."' AND tipo_documento = '".$tipoDocumento."' AND numero_pedido ='".$numeroPedido."'";
        $resultadoSql = DB::update($sql);
        log::info($resultadoSql);
    }

    public function getLogPedidos($filtros){

        if(!empty($filtros)){
            $where = "";
            foreach ($filtros as $key => $value){
                $where .= " and ".$key." = '".$value."'";
            }
            //log::info($where);
            $sql="select centro_operacion, bodega, tipo_documento, numero_pedido, fecha_pedido, msmovws from ".$this->table." where estadoenviows = '3'". $where;
            $resultadoSql = DB::select($sql);
            return $resultadoSql;

        }else {
            $sql="select centro_operacion, bodega, tipo_documento, numero_pedido, fecha_pedido, msmovws from ".$this->table." where estadoenviows = '3'";
            $resultadoSql = DB::select($sql);
            return $resultadoSql;
        }
    }
}