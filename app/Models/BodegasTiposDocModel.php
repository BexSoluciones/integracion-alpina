<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BodegasTiposDocModel extends Model
{
    protected $connection = 'mysql';

    protected $table="bodegas_tipos_doc";

    protected $fillable = [
        'bodega_siesa', 
        'bodega_ecom',
        'tipo_documento_siesa',        
        'tipo_documento_ecom',        
        'centro_operacion_siesa',        
    ];
    public $timestamps=false;

    public function validarTipoDocumento($tipoDoc,$bodega){

        $datos=$this->where('bodega_siesa','=',$bodega)->where('tipo_documento_siesa','=',$tipoDoc)->get();

        if(count($datos)>0){
            return true;
        }else{
            return false;
        }
        
    }

    public function validarTipoDocumentoFactura($tipoDocFactura,$bodega){

        $datos=$this->where('bodega_siesa','=',$bodega)->where('tipo_documento_factura','=',$tipoDocFactura)->get();

        if(count($datos)>0){
            return true;
        }else{
            return false;
        }
        
    }

    public function obtenerVendedor($bodega,$tipoDoc,$centroOperacionSiesa){
        $datos=$this->where('bodega_siesa','=',$bodega)
                    ->where('tipo_documento_siesa','=',$tipoDoc)
                    ->where('centro_operacion_siesa','=',$centroOperacionSiesa)->get();

        if(count($datos)>0){
            return $datos;
        }else{
            return null;
        }
    }
}
