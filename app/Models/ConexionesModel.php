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
        'id_cliente_bex',
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
                        c.id_cliente_bex,
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

    public function getConexionXidProcesoXidClienteBexXidTablaPrestashop($idProcesoIntegracion,$idClienBex,$idTablaPrestashop){
        return $this->selectRaw("
                        c.id_conexion, 
                        c.nombre_conexion,
                        c.id_cliente_bex,
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
                        c.bd_puerto,
                        c.ftp_host,
                        c.ftp_usuario,
                        c.ftp_clave,
                        c.ftp_directorio,
                        cb.razon_social,
                        pi.nombre_proceso    
                    ")
                    ->from('conexiones as c')
                    ->join('clientes_bex as cb','c.id_cliente_bex','=','cb.id_cliente_bex')
                    ->join('conexion_procesos_integracion as cpi','cpi.id_conexion','=','c.id_conexion')
                    ->join('procesos_integracion as pi','cpi.id_proceso_integracion','=','pi.id_proceso_integracion')
                    ->leftjoin('tablas_prestashop AS tp','cpi.id_tabla_prestashop','=','tp.id')
                    ->where('c.id_cliente_bex',$idClienBex)
                    ->where('pi.id_proceso_integracion',$idProcesoIntegracion)
                    ->where('tp.id',$idTablaPrestashop)
                    ->get()[0];
    }

    public function getConexionGeneral($idProcesoIntegracion,$idClienBex){
        return $this->selectRaw("
                        c.id_conexion, 
                        c.nombre_conexion,
                        c.id_cliente_bex,
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
                        c.bd_puerto,
                        c.ftp_host,
                        c.ftp_usuario,
                        c.ftp_clave,
                        c.ftp_directorio,
                        cb.razon_social,
                        pi.nombre_proceso    
                    ")
                    ->from('conexiones as c')
                    ->join('clientes_bex as cb','c.id_cliente_bex','=','cb.id_cliente_bex')
                    ->join('conexion_procesos_integracion as cpi','cpi.id_conexion','=','c.id_conexion')
                    ->join('procesos_integracion as pi','cpi.id_proceso_integracion','=','pi.id_proceso_integracion')
                    ->leftjoin('tablas_prestashop AS tp','cpi.id_tabla_prestashop','=','tp.id')
                    ->where('c.id_cliente_bex',$idClienBex)
                    ->where('pi.id_proceso_integracion',$idProcesoIntegracion)
                    ->where('cpi.es_general',1)
                    ->get()[0];
    }
}
