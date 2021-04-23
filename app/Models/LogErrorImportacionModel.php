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
        $sql="update ".$this->table." SET 
        estadoenviows = ?,
        msmovws = ?,
        fechamovws = ?
        where
        centro_operacion = ?
        AND bodega = ?
        AND tipo_documento = ?
        AND numero_pedido = ? ";
        $resultadoSql = DB::update($sql,[$estado,$mensaje,$fechaHora,$centroOperacion,$bodega,$tipoDocumento,$numeroPedido]);
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