<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConexionesModel extends Model
{
    protected $connection = 'mysql';

    protected $table="conexiones";

    protected $fillable = [
        'id_conexion', 
        'nombre_conexion',
        'id_tipo_conexion',        
        'operacion',        
        'prestashop_debug',        
        'prestashop_url',        
        'prestashop_auth_key',        
        'prestashop_resource',        
        'siesa_consulta',        
        'siesa_url', 
        'siesa_proxy_host',
        'siesa_proxy_port',       
        'siesa_conexion',       
        'siesa_id_cia',       
        'siesa_proveedor',       
        'siesa_id_consulta',       
        'siesa_usuario',       
        'siesa_clave',       
        'bd_host',       
        'bd_nombre',       
        'bd_usuario',       
        'bd_clave',       
        'bd_puerto',
        'id_proceso_integracion',        
    ];
    public $timestamps=false;


    public function getConexionXid($idConexion){
        return $this->selectRaw("
                        c.id_conexion, 
                        c.nombre_conexion,
                        c.id_tipo_conexion,        
                        c.operacion,        
                        c.prestashop_debug,        
                        c.prestashop_url,        
                        c.prestashop_auth_key,        
                        c.prestashop_resource,        
                        c.siesa_consulta,        
                        c.siesa_url, 
                        c.siesa_proxy_host,
                        c.siesa_proxy_port,       
                        c.siesa_conexion,       
                        c.siesa_id_cia,       
                        c.siesa_proveedor,       
                        c.siesa_id_consulta,       
                        c.siesa_usuario,       
                        c.siesa_clave,       
                        c.bd_host,       
                        c.bd_nombre,       
                        c.bd_usuario,       
                        c.bd_clave,       
                        c.bd_puerto
                         
                    ")
                    ->from('conexiones as c')
                    ->where('c.id_conexion',$idConexion)->get()[0];
    }

    
}
