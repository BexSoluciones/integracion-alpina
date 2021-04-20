<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Custom\WebServiceSiesa;
use App\Models\EncabezadoPedidoModel;
use App\Models\DetallePedidoModel;
use App\Models\BodegasTiposDocModel;
use App\Models\ConexionesModel;
use App\Models\LogErrorImportacionModel;
use App\Traits\TraitHerramientas;
use Illuminate\Http\Request;
use Log;
use Storage;
use Validator;

class SubirPedidoCommand extends Command
{
    use TraitHerramientas;

    protected $signature = 'ecom:subir-pedido';

    protected $description = 'sube pedidos';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
       
        $pedidos=$this->obtenerPedidos();


        //dump($pedidos);
        foreach ($pedidos as $key => $pedido) {
            
            $objPedidoDetalle = new DetallePedidoModel();
            $detallePedido = $objPedidoDetalle->obtenerDetallePedido($pedido['numero_pedido'],$pedido['centro_operacion'],$pedido['tipo_documento'],$pedido['bodega']);
            $tipoDocumentoValido = $this->validarTipoDocumento($pedido['tipo_documento'], $pedido['bodega']);
            if($tipoDocumentoValido['valid']==true ){
                
                $this->subirPedidoSiesa($pedido,$detallePedido);
            }else{
                
                $error = json_encode([
                    'errors' => 'El tipo de documento, centro de operacion y bodega no corresponden a una llave valida',
                    'pedido'=>[
                        'centro_operacion'=>$pedido['centro_operacion'],
                        'bodega'=>$pedido['bodega'],
                        'tipo_documento'=>$pedido['tipo_documento'],
                        'numero_pedido'=>$pedido['numero_pedido'],
                    ]
                  ]);
                $estado = 3;
                $importar = false;
                $this->logErrorImportarPedido($error, $estado, $pedido['centro_operacion'], $pedido['bodega'], $pedido['tipo_documento'], $pedido['numero_pedido']);
            }
            
        }
    }

    public function obtenerPedidos()
    {
        $estado='0';
        $objPedidoEncabezado = new EncabezadoPedidoModel();
        return $objPedidoEncabezado->obtenerPedidoEncabezado($estado);

    }


    public function subirPedidoSiesa($pedido,$detallesPedido)
    {
        
        if (count($detallesPedido) > 0) {
            $importar = true;
            $cadena = "";
            $cadena .= str_pad(1, 7, "0", STR_PAD_LEFT) . "00000001001\n"; // Linea 1

            $cadena .= str_pad(2, 7, "0", STR_PAD_LEFT); //Numero de registros
            $cadena .= str_pad(430, 4, "0", STR_PAD_LEFT); //Tipo de registro
            $cadena .= '00'; //Subtipo de registro
            $cadena .= '02'; //version del tipo de registro
            $cadena .= '001'; //Compañia
            $cadena .= '1'; //Indicador para liquidar impuestos
            $cadena .= '0'; //Indica si el numero consecutivo de docto es manual o automático
            $cadena .= '1'; //Indicador de contacto
            $cadena .= $pedido['centro_operacion']; //Centro de operación del documento
            $cadena .= $pedido['tipo_documento']; //Tipo de documento
            $cadena .= str_pad($pedido['numero_pedido'], 8, "0", STR_PAD_LEFT); //Numero documento
            $cadena .= substr($pedido['fecha_pedido'],0,4).substr($pedido['fecha_pedido'],5,2).substr($pedido['fecha_pedido'],8,2); //Fecha del documento
            $cadena .= '502'; //Clase interna del documento
            $cadena .= '2'; //Estado del documento
            $cadena .= '0'; //Indicador backorder del documento
            $cadena .= str_pad($pedido['nit_cliente'], 15, " ", STR_PAD_RIGHT); //Tercero cliente a facturar
            $cadena .= $pedido['sucursal_cliente']; //Sucursal cliente a facturar
            $cadena .= str_pad($pedido['nit_cliente'], 15, " ", STR_PAD_RIGHT); //Tercero cliente a despachar
            $cadena .= $pedido['sucursal_cliente']; //Sucursal cliente a despachar
            $cadena .= $pedido['tipo_cliente']; //Tipo de cliente
            $cadena .= $pedido['centro_operacion']; //Centro de operacion de la factura
            $cadena .= $this->sumarDias(date('Ymd'), 1); //Fecha Entrega pedido
            $cadena .= '000'; //Nro. dias de entrega del documento
            $cadena .= str_pad($pedido['numero_pedido'], 15, "Y", STR_PAD_LEFT); //Orden de compra del Documento
            $cadena .= str_pad($pedido['numero_pedido'], 10, "0", STR_PAD_LEFT); //Referencia del documento
            $cadena .= str_pad('GENERICO', 10, " ", STR_PAD_RIGHT); //Codigo de cargue del documento
            $cadena .= 'COP'; //Codigo de moneda del documento
            $cadena .= 'COP'; //Moneda base de conversión
            $cadena .= '00000001.0000'; //Tasa de conversión
            $cadena .= 'COP'; //Moneda local
            $cadena .= '00000001.0000'; //Tasa local
            $cadena .= 'C01'; //Condicion de pago
            $cadena .= '0'; //Estado de impresión del documento
            $cadena .= str_pad($this->quitarSaltosLinea($this->sanear_string($pedido['observaciones_pedido'] . "//------Vendedor:" . $pedido['vendedor'] . "")), 2000, " ", STR_PAD_RIGHT); //Observaciones del documento
            $cadena .= str_pad('', 15, " ", STR_PAD_LEFT); //cliente de contado
            $cadena .= '000'; //Punto de envio
            $cadena .= str_pad($pedido['cedula_vendedor'], 15, " ", STR_PAD_RIGHT); //Vendedor del pedido
            $cadena .= str_pad('', 50, " ", STR_PAD_RIGHT); //Contacto
            $cadena .= str_pad('', 40, " ", STR_PAD_RIGHT); //Direccion 1
            $cadena .= str_pad('', 40, " ", STR_PAD_RIGHT); //Direccion 2
            $cadena .= str_pad('', 40, " ", STR_PAD_RIGHT); //Direccion 3
            $cadena .= str_pad('', 3, " ", STR_PAD_RIGHT); //Pais
            $cadena .= str_pad('', 2, " ", STR_PAD_RIGHT); //Departamento/Estado
            $cadena .= str_pad('', 3, " ", STR_PAD_RIGHT); //Ciudad
            $cadena .= str_pad('', 40, " ", STR_PAD_RIGHT); //Barrio
            $cadena .= str_pad('', 20, " ", STR_PAD_RIGHT); //Telefono
            $cadena .= str_pad('', 20, " ", STR_PAD_RIGHT); //fax
            $cadena .= str_pad('', 10, " ", STR_PAD_RIGHT); //Codigo postal
            $cadena .= str_pad('', 50, " ", STR_PAD_RIGHT); //E-mail
            $cadena .= '0'; //indicador de descuento
            $cadena .= "\n";

            //Creacion Detalle - movimientos pedido
            $contador = 3;
            $contadorDetallePedido = 1;

            foreach ($detallesPedido as $key => $detallePedido) {
                //---Declarando variables
                $listaPrecio = $detallePedido['lista_precio'];
                $productoSiesa = $this->obtenerCodigoProductoSiesa($detallePedido['codigo_producto']);
                log::info($productoSiesa);
                if (!empty($productoSiesa)) {
                    
                    $codigoProductoSiesa = $productoSiesa[0]['codigo_producto'];
                
                    //$vendedor=$this->obtenerVendedor($pedido['bodega'],$pedido['tipo_documento'],$pedido['centro_operacion']);

                    $cadena .= str_pad($contador, 7, "0", STR_PAD_LEFT); //Numero consecutivo
                    $cadena .= '0431'; //Tipo registro
                    $cadena .= '00'; //Subtipo registro
                    $cadena .= '02'; //Version del tipo de registro
                    $cadena .= '001'; //compañia
                    $cadena .= $pedido['centro_operacion']; //Centro de operacion
                    $cadena .= $pedido['tipo_documento']; //Tipo de documento
                    $cadena .= str_pad($pedido['numero_pedido'], 8, "0", STR_PAD_LEFT); //Consecutivo de documento
                    $cadena .= str_pad($contadorDetallePedido, 10, "0", STR_PAD_LEFT); //Numero de registro --> hacer contador
                    $cadena .= str_pad($codigoProductoSiesa, 7, "0", STR_PAD_LEFT); //Item
                    $cadena .= str_pad('', 50, " ", STR_PAD_LEFT); //Referencia item
                    $cadena .= str_pad('', 20, " ", STR_PAD_LEFT); //Codigo de barras
                    $cadena .= str_pad('', 20, " ", STR_PAD_LEFT); //Extencion 1
                    $cadena .= str_pad('', 20, " ", STR_PAD_LEFT); //Extencion 2
                    $cadena .= $pedido['bodega']; //Bodega
                    $cadena .= '501'; //Concepto
                    $cadena .= '01'; //Motivo
                    $cadena .= '0'; //Indicador de obsequio
                    $cadena .= $pedido['centro_operacion']; //Centro de operacion movimiento
                    $cadena .= str_pad('01', 20, " ", STR_PAD_RIGHT); //Unidad de negocio movimiento
                    $cadena .= str_pad('', 15, " ", STR_PAD_LEFT); //Centro de costo movimiento
                    $cadena .= str_pad('', 15, " ", STR_PAD_LEFT); //Proyecto
                    $cadena .= $this->sumarDias(date('Ymd'), 1); //Fecha de entrega del pedido
                    $cadena .= '000'; //Nro. dias de entrega del documento
                    $cadena .= str_pad($listaPrecio, 3, " ", STR_PAD_RIGHT); //Lista de precio-->agregar al migrar productos
                    $cadena .= 'UNID'; //Unidad de medida-->pendiente
                    $cadena .= str_pad(intval($detallePedido['cantidad']), 15, "0", STR_PAD_LEFT) . '.0000'; //Cantidad base
                    $cadena .= str_pad('', 15, "0", STR_PAD_LEFT) . '.0000'; //Cantidad adicional
                    $cadena .= str_pad(intval($detallePedido['precio_unitario']), 15, "0", STR_PAD_LEFT) . '.0000'; //Precio unitario
                    $cadena .= '0'; //Impuestos asumidos
                    $cadena .= str_pad('', 255, " ", STR_PAD_LEFT); //Notas
                    $cadena .= str_pad('', 2000, " ", STR_PAD_LEFT); //Descripcion
                    $cadena .= '5'; //Indicador backorder del movimiento
                    $cadena .= '2'; //Indicador de precio
                    $cadena .= "\n";
                    $contador++;
                    $contadorDetallePedido++;
                }else {
                    $error = json_encode([
                        'errors' => 'El siguiente producto en el pedido relacionado no existe',
                        'pedido'=>[
                            'centro_operacion'=>$pedido['centro_operacion'],
                            'bodega'=>$pedido['bodega'],
                            'tipo_documento'=>$pedido['tipo_documento'],
                            'numero_pedido'=>$pedido['numero_pedido'],
                            'producto' => $detallePedido['codigo_producto']
                        ]
                      ]);
                    $estado = 3;
                    $importar = false;
                    $this->logErrorImportarPedido($error, $estado, $pedido['centro_operacion'], $pedido['bodega'], $pedido['tipo_documento'], $pedido['numero_pedido']);
                    
                }  
            }

            $cadena .= str_pad($contador, 7, "0", STR_PAD_LEFT) . "99990001001";

            $lineas = explode("\n", $cadena);

            $nombreArchivo = str_pad($pedido['numero_pedido'], 15, "0", STR_PAD_LEFT) . '.txt';
            Storage::disk('local')->put('pandapan/pedidos_txt/' . $nombreArchivo, $cadena);
            $xmlPedido = $this->crearXmlPedido($lineas, $pedido['numero_pedido']);

            // $ip = $this->getIpCliente();
            // Log::info($ip);

            if (!$this->existePedidoSiesa('1', $pedido['tipo_documento'], str_pad($pedido['numero_pedido'], 15, "Y", STR_PAD_LEFT)) && $importar === true) {
                //dump('enviando pedido a siesa');
                $resp = $this->getWebServiceSiesa(28)->importarXml($xmlPedido);
                dump($resp);
                if (empty($resp)) {

                    $error = 'Ok';
                    $estado = 2;
                    $this->logErrorImportarPedido($error, $estado, $pedido['centro_operacion'], $pedido['bodega'], $pedido['tipo_documento'], $pedido['numero_pedido']);
                    
                } else {
                    
                    $mensaje = "";
                    foreach ($resp->NewDataSet->Table as $key => $errores) {

                        $mensaje .= "error $key ->";
                        foreach ($errores as $key => $detalleError) {
                            $mensaje .= '***' . $key . '=>' . $detalleError;
                        }

                    }
                    
                    Log::info(print_r($resp->NewDataSet->Table, true));
                    $error = print_r($resp->NewDataSet->Table, true);
                    $estado = 3;
                    $this->logErrorImportarPedido($error, $estado, $pedido['centro_operacion'], $pedido['bodega'], $pedido['tipo_documento'], $pedido['numero_pedido']);

                }

            } elseif ($this->existePedidoSiesa('1', $pedido['tipo_documento'], str_pad($pedido['numero_pedido'], 15, "Y", STR_PAD_LEFT))) {
                $error = json_encode([
                    'created' => false,
                    'errors' => "Este pedido ya fue registrado anteriormente, por favor verificar. Fecha de ejecucion: " . date('Y-m-d h:i:s'),
                    'pedido'=>[
                        'tipo_documento'=>$pedido['tipo_documento'],
                        'numero_pedido'=>$pedido['numero_pedido'],
                    ]
                ]);
                $estado = 3;
                $this->logErrorImportarPedido($error, $estado, $pedido['centro_operacion'], $pedido['bodega'], $pedido['tipo_documento'], $pedido['numero_pedido']);

            }

        }else {
            $error = json_encode([
                'created' => false,
                'errors' => "El pedido no tiene productos",
                'pedido'=>[
                    'tipo_documento'=>$pedido['tipo_documento'],
                    'numero_pedido'=>$pedido['numero_pedido'],
                ]
                
            ]);
            $estado = 3;
            $this->logErrorImportarPedido($error, $estado, $pedido['centro_operacion'], $pedido['bodega'], $pedido['tipo_documento'], $pedido['numero_pedido']);

        }


    }

    public function obtenerCodigoProductoSiesa($productoEcom)
    {
        $parametros = [
            ['PARAMETRO1' => $productoEcom],
        ];
        return $this->getWebServiceSiesa(34)->ejecutarConsulta($parametros);

    }

    public function getWebServiceSiesa($idConexion)
    {
        return new WebServiceSiesa($idConexion);
    }

    public function validarTipoDocumento($tipoDoc, $bodega)
    {

        $objBodegaTipoDo = new BodegasTiposDocModel();
        $resp = $objBodegaTipoDo->validarTipoDocumento($tipoDoc, $bodega);

        if ($resp === true) {
            return [
                'valid' => true,
                'errors' => 0,
            ];
        } else {
            return [
                'valid' => false,
                'errors' => 'El tipo de documento ' . $tipoDoc . ' y la bodega ' . $bodega . ' no pertenece a un documento valido',
            ];
        }
    }

    public function logErrorImportarPedido($mensaje, $estado, $centroOperacion, $bodega, $tipoDocumento, $numeroPedido)
    {
        $objErrorImpPed = new LogErrorImportacionModel();
        $result = $objErrorImpPed->actualizarEstadoDocumento($mensaje, $estado, $centroOperacion, $bodega, $tipoDocumento, $numeroPedido);
        
    }

    public function crearXmlPedido($lineas, $idOrder)
    {

        $datosConexionSiesa = $this->getConexionesModel()->getConexionXid(14);
        $xmlPedido = "<?xml version='1.0' encoding='utf-8'?>
        <Importar>
        <NombreConexion>" . $datosConexionSiesa->siesa_conexion . "</NombreConexion>
        <IdCia>" . $datosConexionSiesa->siesa_id_cia . "</IdCia>
        <Usuario>" . $datosConexionSiesa->siesa_usuario . "</Usuario>
        <Clave>" . $datosConexionSiesa->siesa_clave . "</Clave>
        <Datos>\n";
        $datos = "";
        foreach ($lineas as $key => $linea) {

            $xmlPedido .= "        <Linea>" . $linea . "</Linea>\n";
            $datos .= "        <Linea>" . $linea . "</Linea>\n";
        }
        $xmlPedido .= "        </Datos>
        </Importar>";

        $nombreArchivo = str_pad($idOrder, 15, "Y", STR_PAD_LEFT) . '.xml';
        Storage::disk('local')->put('pandapan/pedidos/' . $nombreArchivo, $xmlPedido);

        return $datos;

    }
    public function getConexionesModel()
    {
        return new ConexionesModel();
    }

    public function existePedidoSiesa($idCia, $tipoDocumento, $numDoctoReferencia)
    {

        $parametros = [
            ['PARAMETRO1' => $idCia],
            ['PARAMETRO2' => $tipoDocumento],
            ['PARAMETRO3' => $numDoctoReferencia],
        ];
        $resultado = $this->getWebServiceSiesa(27)->ejecutarConsulta($parametros);

        // Log::info("=======respuesta existe pedido=====");
        // Log::info($resultado);
        // dd("fin validando pedido");

        if (!empty($resultado)) {
            return true;
        } else {
            return false;
        }

    }

}
