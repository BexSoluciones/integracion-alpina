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

        $datos=$this->where('bodega_ecom','=',$bodega)->where('tipo_documento_ecom','=',$tipoDoc)->get();

        if(count($datos)>0){
            return true;
        }else{
            return false;
        }
        
    }
}
