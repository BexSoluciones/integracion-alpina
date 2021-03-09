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
            $cadena .= '001'; //Centro de operación de la factura
            $cadena .= $factura['sucursal_cliente']; //Sucursal cliente a despachar
            $cadena .= $factura['tipo_cliente']; //Tipo de cliente
            $cadena .= $factura['centro_operacion']; //Centro de operacion de la factura
            $cadena .= str_pad('', 15, " ", STR_PAD_LEFT); //Cliente de contado
            $cadena .= str_pad($factura['nit'], 15, " ", STR_PAD_RIGHT); //Tercero cliente a remisionar
            $cadena .= $factura['sucursal_cliente'];//Sucursal cliente a remisionar
            $cadena .= $this->obtenerVendedor($factura['bodega'],$factura['tipo_documento'],$factura['centro_operacion']); //Tercero vendedor
            $cadena .= str_pad($factura['numero_factura'], 10, "0", STR_PAD_LEFT); //Referencia del documento
            $cadena .= str_pad('', 12, " ", STR_PAD_RIGHT); //Numero orden de compra
            $cadena .= str_pad('', 10, " ", STR_PAD_RIGHT); //Numero de cargue
            $cadena .= 'C01'; //Condicion de pago
            $cadena .= 'COP'; //Moneda documento
            $cadena .= 'COP'; //Moneda base de conversión
            $cadena .= '00000001.0000'; //Tasa de conversión
            $cadena .= 'COP'; //Moneda local
            $cadena .= '00000001.0000'; //Tasa local
            $cadena .= str_pad('', 2000, " ", STR_PAD_RIGHT); //Observaciones del documento
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
            $cadena .= '0000000000.0000'; //Peso
            $cadena .= '0000000000.0000'; //Volumen
            $cadena .= '0000000000.0000'; //Valor asegurado
            $cadena .= str_pad('', 255, " ", STR_PAD_RIGHT); //Notas
            $cadena .= str_pad('', 3, " ", STR_PAD_RIGHT); //Caja de recaudo
            $cadena .= '0'; //Genera Kit
            $cadena .= str_pad('', 3, " ", STR_PAD_RIGHT); //Tipo de documento de proceso
            $cadena .= str_pad('', 5, " ", STR_PAD_RIGHT); //Bodega de componentes del kit
            $cadena .= str_pad('', 2, " ", STR_PAD_RIGHT); //Motivo de salida proceso
            $cadena .= str_pad('', 2, " ", STR_PAD_RIGHT); //Motivo de entrada proceso
            $cadena .= '070'; //Clase de documento proceso
            $cadena .= "\n";

            //Creacion Detalle - movimientos pedido
            $contador = 3;
            $contadorDetallePedido = 1;
            foreach ($detallesPedido as $key => $detallePedido) {
                //---Declarando variables
                $listaPrecio = $detallePedido['lista_precio'];
                $productoSiesa = $this->obtenerCodigoProductoSiesa($detallePedido['codigo_producto']);
                $codigoProductoSiesa = $productoSiesa[0]['codigo_producto'];
                $vendedor=$this->obtenerVendedor($pedido['bodega'],$pedido['tipo_documento'],$pedido['centro_operacion']);

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
                $cadena .= $listaPrecio; //Lista de precio-->agregar al migrar productos
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
            }

            $cadena .= str_pad($contador, 7, "0", STR_PAD_LEFT) . "99990001001";

            $lineas = explode("\n", $cadena);

            $nombreArchivo = str_pad($pedido['numero_pedido'], 15, "0", STR_PAD_LEFT) . '.txt';
            Storage::disk('local')->put('pandapan/pedidos_txt/' . $nombreArchivo, $cadena);
            $xmlPedido = $this->crearXmlPedido($lineas, $pedido['numero_pedido']);

            // $ip = $this->getIpCliente();
            // Log::info($ip);

            if (!$this->existePedidoSiesa('1', $pedido['tipo_documento'], str_pad($pedido['numero_pedido'], 15, "Y", STR_PAD_LEFT))) {

                $resp = $this->getWebServiceSiesa(28)->importarXml($xmlPedido);
                if (empty($resp)) {

                    return response()->json([
                        'created' => true,
                        'code' => 201,
                        'errors' => $respValidacion['errors'],
                    ], 201);
                    // $this->cambiarEstadoPedido($pedido->id_order, 15);
                    // $this->info('todo ok');
                } else {
                    //  $resp;
                    $mensaje = "";
                    foreach ($resp->NewDataSet->Table as $key => $errores) {

                        $mensaje .= "error $key ->";
                        foreach ($errores as $key => $detalleError) {
                            $mensaje .= '***' . $key . '=>' . $detalleError;
                        }

                    }

                    Log::info(print_r($resp->NewDataSet->Table, true));

                    return response()->json([
                        'created' => false,
                        'code' => 500,
                        'errors' => "Ha ocurrido un error inesperado al crear el pedido,por favor contactarse con el administrador. Fecha de ejecucion: " . date('Y-m-d h:i:s'),
                    ], 500);

                }

            } elseif ($this->existePedidoSiesa('1', $pedido['tipo_documento'], str_pad($pedido['numero_pedido'], 15, "Y", STR_PAD_LEFT))) {
                return response()->json([
                    'created' => false,
                    'code' => 412,
                    'errors' => "Este pedido ya fue registrado anteriormente, por favor verificar. Fecha de ejecucion: " . date('Y-m-d h:i:s'),
                ], 412);
            }

        }

        return response()->json([
            'created' => true,
            'code' => 201,
            'errors' => 0,
        ], 201);

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

        //--------Defino data
        $this->data = $request->input('data');

        //--------Valido datos encabezado pedido
        $respValidarEncabezado = $this->validarEncabezadoFactura($this->data[0]);

        //--------Valido datos detalle pedido
        $item = 1;
        $erroresDetallePedido = [];
        foreach ($this->data[0]["detalle_factura"] as $key => $detallePedido) {

            $respValidacion = $this->validarDetalleFactura($detallePedido);
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
            'cantidad_base' => 'required|digits_between:1,15',
            'valor_bruto' => 'required|regex:/^[0-9]+(\.[0-9]{1,4})?$/',
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

    public function noEmpty($pedido)
    {

        if (empty($pedido)) {
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

    public function obtenerVendedor($bodega,$tipoDoc,$centroOperacionSiesa)
    {
        $objBodegaTipoDo = new BodegasTiposDocModel();
        $resp = $objBodegaTipoDo->obtenerVendedor($bodega,$tipoDoc,$centroOperacionSiesa);
        return $resp[0]->vendedor;
    }
    
}