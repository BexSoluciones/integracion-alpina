<?php

namespace App\Http\Controllers\integracionecom\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Custom\WebServiceSiesa;
use App\Models\BodegasTiposDocModel;
use App\Models\ConexionesModel;
use App\Traits\TraitHerramientas;
use Illuminate\Validation\Rule;
use Log;
use Storage;
use Validator;


class FacturaController extends Controller
{

    use TraitHerramientas;

    

    public function getWebServiceSiesa($idConexion)
    {
        return new WebServiceSiesa($idConexion);
    }

    public function subirFacturaSiesa(Request $request)
    {

        
        $respValidacion = $this->validarEstructuraJson($request);

        Log::info($respValidacion);

        if ($respValidacion['valid'] == false) {

            return response()->json([
                'created' => false,
                'code' => 412,
                'errors' => $respValidacion['errors'],
            ], 412);

        }

        $factura= $request->input('data')[0];
        $detallesFactura = $request->input('data.0.detalle_factura');

        if (count($detallesFactura) > 0) {

            //-------- Encabezado factura

            $vendedorFactura=$this->obtenerVendedorFactura($factura['bodega'],$factura['tipo_documento'],$factura['centro_operacion']);
            if (empty($vendedorFactura)) {

                return response()->json([
                    'created' => false,
                    'code' => 412,
                    'errors' => 'No existe vendedor para la bodega '.$factura['bodega'].' tipo documento factura '.$factura['tipo_documento'].' centro de operacion '.$factura['centro_operacion']
                ], 412);
    
            }

            $cadena = "";
            $cadena .= str_pad(1, 7, "0", STR_PAD_LEFT) . "00000001001\n"; // Linea 1

            $cadena .= str_pad(2, 7, "0", STR_PAD_LEFT); //Numero de registros
            $cadena .= str_pad(461, 4, "0", STR_PAD_LEFT); //Tipo de registro
            $cadena .= '00'; //Subtipo de registro
            $cadena .= '01'; //version del tipo de registro
            $cadena .= '001'; //Compañia
            $cadena .= '1'; //Indicador para liquidar impuestos
            $cadena .= '1'; //Indicador para liquidar retenciones
            $cadena .= '0'; //Indica si el numero consecutivo de docto es manual o automático
            $cadena .= '001'; //Centro de operación del documento
            $cadena .= $factura['tipo_documento']; //Tipo de documento
            $cadena .= str_pad($factura['numero_factura'], 8, "0", STR_PAD_LEFT); //Numero documento
            $cadena .= $factura['fecha_factura']; //Fecha del documento
            $cadena .= str_pad($factura['nit'], 15, " ", STR_PAD_RIGHT); //Tercero cliente a facturar
            $cadena .= '520'; //Clase interna del documento
            $cadena .= '1'; //Estado del documento
            $cadena .= '1'; //Estado de impresión del documento
            $cadena .= $factura['sucursal_cliente']; //Sucursal cliente a facturar
            $cadena .= '0001'; //Tipo de cliente
            $cadena .= $factura['centro_operacion']; //Centro de operacion de la factura
            $cadena .= str_pad('', 15, " ", STR_PAD_LEFT); //Cliente de contado
            $cadena .= str_pad($factura['nit'], 15, " ", STR_PAD_RIGHT); //Tercero cliente a remisionar
            $cadena .= $factura['sucursal_cliente'];//Sucursal cliente a remisionar
            $cadena .=  str_pad($vendedorFactura, 15, " ",STR_PAD_RIGHT);//Tercero vendedor
            $cadena .= str_pad($factura['numero_factura'], 10, "0", STR_PAD_LEFT); //Referencia del documento
            $cadena .= str_pad('', 12, " ", STR_PAD_RIGHT); //Numero orden de compra
            $cadena .= str_pad('', 10, " ", STR_PAD_RIGHT); //Numero de cargue
            $cadena .= 'C01'; //Condicion de pago
            $cadena .= 'COP'; //Moneda documento
            $cadena .= 'COP'; //Moneda base de conversión
            $cadena .= '00000001.0000'; //Tasa de conversión
            $cadena .= 'COP'; //Moneda local
            $cadena .= '00000001.0000'; //Tasa local
            $cadena .= str_pad('n1.1:'.$factura['observaciones_factura'], 2000, " ", STR_PAD_RIGHT); //Observaciones del documento
            $cadena .= '000'; //Punto de envio
            $cadena .= '1'; //Indicador de contacto
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
            $cadena .= str_pad('', 10, " ", STR_PAD_RIGHT); //Codigo del vehiculo
            $cadena .= str_pad('', 15, " ", STR_PAD_RIGHT); //Codigo transportador
            $cadena .= str_pad('', 3, " ", STR_PAD_RIGHT); //Código sucursal transportador
            $cadena .= str_pad('', 15, " ", STR_PAD_RIGHT); //Código conductor
            $cadena .= str_pad('', 50, " ", STR_PAD_RIGHT); //Nombre conductor
            $cadena .= str_pad('', 15, " ", STR_PAD_RIGHT); //Identificación del conductor
            $cadena .= str_pad('', 30, " ", STR_PAD_RIGHT); //Numero de guia
            $cadena .= '0000000000.0000'; //Cajas/bultos
            $cadena .= '000000000000000.0000'; //Peso
            $cadena .= '000000000000000.0000'; //Volumen
            $cadena .= '000000000000000.0000'; //Valor asegurado
            $cadena .= str_pad('', 255, " ", STR_PAD_RIGHT); //Notas
            $cadena .= str_pad('2', 3, "0", STR_PAD_LEFT); //Caja de recaudo
            $cadena .= '0'; //Genera Kit
            $cadena .= str_pad('', 3, " ", STR_PAD_RIGHT); //Tipo de documento de proceso
            $cadena .= str_pad('', 5, " ", STR_PAD_RIGHT); //Bodega de componentes del kit
            $cadena .= str_pad('', 2, " ", STR_PAD_RIGHT); //Motivo de salida proceso
            $cadena .= str_pad('', 2, " ", STR_PAD_RIGHT); //Motivo de entrada proceso
            $cadena .= '070'; //Clase de documento proceso
            $cadena .= "\n";
            
            //-------- Caja

            $cadena .= str_pad(3, 7, "0", STR_PAD_LEFT); //Numero de registro
            $cadena .= str_pad(358, 4, "0", STR_PAD_LEFT); //Tipo de registro
            $cadena .= '00'; //Subtipo de registro
            $cadena .= '01'; //version del tipo de registro
            $cadena .= '001'; //Compañia
            $cadena .= '001'; //Centro de operación del documento
            $cadena .= $factura['tipo_documento']; //Tipo de documento
            $cadena .= str_pad($factura['numero_factura'], 8, "0", STR_PAD_LEFT); //Numero documento
            $cadena .= $factura['medio_pago'];//Medio de pago
            $cadena .= str_pad(intval($factura['valor_medio_pago']), 15, "0", STR_PAD_LEFT) . '.0000';//Valor de medio de pago
            $cadena .= str_pad('', 10, " ", STR_PAD_RIGHT);//Codigo de banco
            $cadena .= '00000000';// Numero de cheque
            $cadena .= str_pad('', 25, " ", STR_PAD_RIGHT);//Numero de cuenta / tarjeta
            $cadena .= str_pad('', 3, " ", STR_PAD_RIGHT);//Código de seguridad
            $cadena .= str_pad('', 10, " ", STR_PAD_RIGHT);//Numero de autorizacion
            $cadena .= str_pad('', 8, " ", STR_PAD_RIGHT);//Fecha o año/mes de vencimiento
            if($factura['medio_pago']=='CG1'){
                $cadena .= str_pad($factura['referencia_medio_pago'], 30, " ", STR_PAD_RIGHT);//Referencia
                $cadena .= str_pad($factura['fecha_consignacion_cheque'], 8, " ", STR_PAD_RIGHT);//Fecha de consignación del cheque devuelto
            }else{
                $cadena .= str_pad('', 30, " ", STR_PAD_RIGHT);//Referencia
                $cadena .= str_pad('', 8, " ", STR_PAD_RIGHT);//Fecha de consignación del cheque devuelto
            }
            $cadena .= str_pad('', 3, " ", STR_PAD_RIGHT);//Causal de devolución
            $cadena .= str_pad('', 15, " ", STR_PAD_RIGHT);//Código del tercero al que le devolvieron el cheque
            $cadena .= str_pad('', 255, " ", STR_PAD_RIGHT);//Observaciones del movimiento
            $cadena .= str_pad('', 15, " ", STR_PAD_RIGHT);//Auxiliar de centro de costos
            $cadena .= "\n";

            //-------Relacion documentos

            $cadena .= str_pad(4, 7, "0", STR_PAD_LEFT); //Numero de registro
            $cadena .= str_pad(461, 4, "0", STR_PAD_LEFT); //Tipo de registro
            $cadena .= '02'; //Subtipo de registro
            $cadena .= '01'; //version del tipo de registro
            $cadena .= '001'; //Compañia
            $cadena .= '001'; //Centro de operación del documento
            $cadena .= $factura['tipo_documento']; //Tipo de documento
            $cadena .= str_pad($factura['numero_factura'], 8, "0", STR_PAD_LEFT); //Numero documento
            $cadena .= '001'; //Centro de operación del documento
            $cadena .= $factura['tipo_documento_remision']; //Tipo de documento remision
            $cadena .= str_pad($factura['numero_documento_remision'], 8, "0", STR_PAD_LEFT); //Numero documento
            $cadena .= "\n";


            //Creacion Detalle factura - movimientos factura
            $contador = 5;
            $contadorDetalleFactura = 1;
            foreach ($detallesFactura as $key => $detalleFactura) {
                //---Declarando variables
                $listaPrecio = $detalleFactura['lista_precio'];
                $productoSiesa = $this->obtenerCodigoProductoSiesa($detalleFactura['codigo_producto']);
                $codigoProductoSiesa = $productoSiesa[0]['codigo_producto'];
                
                
                $cadena .= str_pad($contador, 7, "0", STR_PAD_LEFT); //Numero consecutivo
                $cadena .= '0470'; //Tipo registro
                $cadena .= '01'; //Subtipo registro
                $cadena .= '01'; //Version del tipo de registro
                $cadena .= '001'; //compañia
                $cadena .= $factura['centro_operacion']; //Centro de operacion
                $cadena .= $factura['tipo_documento']; //Tipo de documento
                $cadena .= str_pad($factura['numero_factura'], 8, "0", STR_PAD_LEFT); //Consecutivo de documento
                $cadena .= str_pad($contadorDetalleFactura, 10, "0", STR_PAD_LEFT); //Numero de registro 
                $cadena .= str_pad($codigoProductoSiesa, 7, "0", STR_PAD_LEFT); //Item
                $cadena .= str_pad('', 20, " ", STR_PAD_LEFT); //Referencia item
                $cadena .= str_pad('', 20, " ", STR_PAD_LEFT); //Codigo de barras
                $cadena .= str_pad('', 4, " ", STR_PAD_LEFT); //Extencion 1
                $cadena .= str_pad('', 4, " ", STR_PAD_LEFT); //Extencion 2
                $cadena .= $factura['bodega']; //Bodega
                $cadena .= str_pad('GENERAL', 10, " ", STR_PAD_RIGHT);//Ubicacion
                $cadena .= str_pad('', 15, " ", STR_PAD_LEFT); //Lote
                $cadena .= '501'; //Concepto  ----> Ojo: cuando nos definan el tipo de documento para devolucion colocar condicional
                $cadena .= '01'; //Motivo
                $cadena .= '0'; //Indicador de obsequio
                $cadena .= $factura['centro_operacion']; //Centro de operacion movimiento
                $cadena .= '01'; //Unidad de negocio movimiento
                $cadena .= str_pad('', 15, " ", STR_PAD_LEFT); //Centro de costo movimiento
                $cadena .= str_pad('', 15, " ", STR_PAD_LEFT); //Proyecto
                $cadena .= $listaPrecio; //Lista de precio
                $cadena .= 'UNID'; //Unidad de medida precio
                $cadena .= 'UNID'; //Unidad de medida del movimiento
                $cadena .= str_pad(intval($detalleFactura['cantidad']), 15, "0", STR_PAD_LEFT) . '.0000'; //Cantidad base
                $cadena .= str_pad('', 15, "0", STR_PAD_LEFT) . '.0000'; //Cantidad adicional
                $cadena .= str_pad(intval($detalleFactura['valor_bruto']), 15, "0", STR_PAD_LEFT) . '.0000'; //Valor bruto
                $cadena .= '2'; //Naturaleza de la transaccion
                $cadena .= '0'; //Solo valor
                $cadena .= '0'; //Impuestos asumidos
                $cadena .= str_pad('', 255, " ", STR_PAD_LEFT); //Notas
                $cadena .= str_pad('', 2000, " ", STR_PAD_LEFT); //Descripcion
                $cadena .= str_pad('', 40, " ", STR_PAD_LEFT); //Descripcion item
                $cadena .= str_pad('', 4, " ", STR_PAD_LEFT); //Unidad de medida de inventario del item.
                $cadena .= "\n";
                $contador++;
                $contadorDetalleFactura++;
            }

            $cadena .= str_pad($contador, 7, "0", STR_PAD_LEFT) . "99990001001";

            $lineas = explode("\n", $cadena);

            $nombreArchivo = str_pad($factura['numero_factura'], 15, "0", STR_PAD_LEFT) . '.txt';
            Storage::disk('local')->put('pandapan/facturas/txt/' . $nombreArchivo, $cadena);
            $xmlFactura = $this->crearXmlFactura($lineas, $factura['numero_factura']);

            // $ip = $this->getIpCliente();
            // Log::info($ip);

            
            $respImport=$this->importarXml($xmlFactura);
            
            if($respImport['created']===false){
                return response()->json([
                    'created' => false,
                    'code' => 412,
                    'errors' =>$respImport['errors'] ,
                ], 412);
            }

           

        }

        return response()->json([
            'created' => true,
            'code' => 201,
            'errors' => 0,
        ], 201);

    }

    public function crearXmlFactura($lineas, $idOrder)
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
        Storage::disk('local')->put('pandapan/facturas/xml/' . $nombreArchivo, $xmlPedido);

        return $datos;

    }

    public function importarXml($xml){        

        $resp = $this->getWebServiceSiesa(36)->importarXml($xml);
// Log::info(print_r($resp,true));
                if (empty($resp)) {
                    $transaccionExitosa=true;
                    return [
                        'created'=>true,
                        'errors'=>0
                    ];
                } else {

                    return [
                        'created'=>false,
                        'errors'=>$this->convertirObjetosArrays($resp->NewDataSet->Table)
                    ];                    

                }

               
    }

    public function validarEstructuraJson($request)
    {

        //--------Valido que exista data
        $formatoValido = false;
        $formatoValido = $request->input('data') ?? false;

        if (!$formatoValido) {
            return [
                'valid' => false,
                'errors' => "Formato json no válido, data no está definido",
            ];
        }

        //--------Valido que exista detalle factura
        $formatoValido = false;
        $formatoValido = $request->input('data.0.detalle_factura') ?? false;
        if (!$formatoValido) {
            return [
                'valid' => false,
                'errors' => "Formato json no válido, detalle factura no está definido",
            ];
        }

        //--------Defino data -> captura todos los datos del json
        $this->data = $request->input('data');

        //--------Valido datos encabezado pedido
        $respValidarEncabezado = $this->validarEncabezadoFactura($this->data[0]);

        //--------Valido datos detalle pedido
        $item = 1;
        $erroresDetallePedido = [];
        foreach ($this->data[0]["detalle_factura"] as $key => $detalleFactura) {

            $respValidacion = $this->validarDetalleFactura($detalleFactura);
            if ($respValidacion['valid'] == false) {
                $erroresDetallePedido[$key]['item_' . $item] = $respValidacion['errors'];
            }

            $item++;
        }

        if ($respValidarEncabezado['valid'] == false || count($erroresDetallePedido) > 0) {

            return [
                'valid' => false,
                'errors' => [
                    'ErroresEncabezadoFactura' => $respValidarEncabezado['errors'],
                    'ErroresDetalleFactura' => $erroresDetallePedido,
                ],
            ];
        } else {
            return [
                'valid' => true,
                'errors' => 0,
            ];
        }

    }

    public function validarEncabezadoFactura($datosEncFactura)
    {
        //------Elimino detalle pedido el cual no esta dentro de esta validación
        unset($datosEncFactura['detalle_factura']);

        $datosEncFactura = $this->decodificarArray($datosEncFactura);

        //--------Validando tipo documento
        $rules = [
            'medio_pago' => [
                'required',
                Rule::in(['CG1', 'CHD','CHE','CHP','EFE','TC','TD']),
            ]
        ];
        
        $validator = Validator::make($datosEncFactura, $rules);

        if ($validator->fails()) {
            return [
                'valid' => false,
                'errors' => $validator->errors(),
            ];
        }
        //--------Fin validando tipo documento

        $rules = [
            'tipo_documento' => 'required',
            'numero_factura' => 'required',
            'fecha_factura' => 'required|date_format:"Ymd"',
            'nit' => 'required|max:15',
            'sucursal_cliente' => 'required|digits_between:1,3',
            'nombre_vendedor' => 'required',
            'medio_pago' => [
                'required',
                Rule::in(['CG1', 'CHD','CHE','CHP','EFE','TC','TD']),
            ],
            'valor_medio_pago' => 'required|regex:/^[0-9]+(\.[0-9]{1,4})?$/',
            'tipo_documento_remision' => 'required',
            'numero_documento_remision' => 'required',
            'bodega' => 'required|digits_between:1,5',
            'centro_operacion' => 'required|digits_between:1,3',
            'observaciones_factura' => 'max:2000',
            
        ];

        if($datosEncFactura['medio_pago'] =='CG1'){
            $rules['referencia_medio_pago']='required';
            $rules['fecha_consignacion_cheque']='required|date_format:"Ymd"';
        }

        $validator = Validator::make($datosEncFactura, $rules);

        $tipoDocumentoValido = $this->validarTipoDocumentoFactura($datosEncFactura['tipo_documento'], $datosEncFactura['bodega']);

        if ($validator->fails()) {
            return [
                'valid' => false,
                'errors' => $validator->errors(),
            ];
        } else if ($tipoDocumentoValido['valid'] === false) {
            return [
                'valid' => false,
                'errors' => $tipoDocumentoValido['errors'],
            ];

        } else {
            return [
                'valid' => true,
                'errors' => 0,
            ];
        }

    }

    public function validarDetalleFactura($datosDetallePedido)
    {

        $rules = [
            'codigo_producto' => 'required',
            'cantidad' => 'required|digits_between:1,15',
            'valor_bruto' => 'required|regex:/^[0-9]+(\.[0-9]{1,4})?$/',
            'lista_precio'=>'required|max:3'
        ];

        $validator = Validator::make($datosDetallePedido, $rules);

        if ($validator->fails()) {
            return [
                'valid' => false,
                'errors' => $validator->errors(),
            ];
        } else {
            return [
                'valid' => true,
                'errors' => 0,
            ];
        }

    }

    public function noEmpty($factura)
    {

        if (empty($factura)) {
            return false;
        }
        return true;
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

    public function validarTipoDocumentoFactura($tipoDocFactura, $bodega)
    {

        $objBodegaTipoDo = new BodegasTiposDocModel();
        $resp = $objBodegaTipoDo->validarTipoDocumentoFactura($tipoDocFactura, $bodega);

        if ($resp === true) {
            return [
                'valid' => true,
                'errors' => 0,
            ];
        } else {
            return [
                'valid' => false,
                'errors' => 'El tipo de documento ' . $tipoDocFactura . ' y la bodega ' . $bodega . ' no pertenece a un documento valido',
            ];
        }
    }

    public function obtenerCodigoProductoSiesa($productoEcom)
    {
        $parametros = [
            ['PARAMETRO1' => $productoEcom],
        ];
        return $this->getWebServiceSiesa(34)->ejecutarConsulta($parametros);

    }

    public function obtenerVendedorFactura($bodega,$tipoDoc,$centroOperacionSiesa)
    {
        $objBodegaTipoDo = new BodegasTiposDocModel();
        $resp = $objBodegaTipoDo->obtenerVendedorFactura($bodega,$tipoDoc,$centroOperacionSiesa);
        if(!empty($resp)){
            return $resp[0]->vendedor;
        }else{
            Log::error("No existe vendedor para bodega = ".$bodega." tipo documento factura = ".$tipoDoc." centro de operacion = ".$centroOperacionSies);
            return null;
        }
        
    }
    
}


// {
//     "data":[
//         {
//             "tipo_documento":"EUY",
//             "numero_factura":"",
//             "fecha_factura":"",
//             "nit":"",
//             "sucursal_cliente":"",            
//             "nombre_vendedor":"",  
//             "medio_pago":"CG1",                 
//             "valor_medio_pago":"",                 
//             "referencia_medio_pago":"",   
//             "fecha_consignacion_cheque":"", 
//             "tipo_documento_remision":"",
//             "numero_documento_remision":"",   
//             "bodega":"00111",    
//             "centro_operacion":"",    
//             "detalle_factura":[
//                     {
//                         "codigo_producto": "",
//                          "lista_precio":"B2B",                              
//                         "cantidad":2,
//                         "valor_bruto": 8116
//                     }
                   
//             ]

//         }
//     ]
// }