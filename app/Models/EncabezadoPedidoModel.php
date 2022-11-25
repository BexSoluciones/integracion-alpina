<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DB;
use Illuminate\Support\Facades\Log;

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
}