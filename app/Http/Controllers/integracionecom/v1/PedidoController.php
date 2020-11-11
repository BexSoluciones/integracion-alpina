<?php

namespace App\Http\Controllers\integracionecom\v1;

use App\Custom\WebServiceSiesa;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Log;
use Storage;
use App\Traits\TraitHerramientas;
use Validator;

class PedidoController extends Controller
{

    use TraitHerramientas;

   

    public static  $datosEncabPedido = [
        'numero_pedido'=>'',
        'centro_operacion_bodega'=>'',
        'tipo_cliente'=>'',
        'fecha_pedido'=>'',
        'nit_cliente'=>'', 
        'sucursal_tercero'=>'', 
        'observaciones_pedido'=>'',
        'detalle_pedido'=>''
    ];

    public static  $datosDetallePedido = array(
        'codigo_producto', 'bodega', 'lista_precio', 'centro_operacion',
        'numero_pedido', 'cantidad', 'precio_producto'
    );


    public function getPedidoSiesa()
    {

        $objWebserviceSiesa = $this->getWebServiceSiesa(14);
        $pedidos = $objWebserviceSiesa->ejecutarConsulta();

        $arrayValues = [];
        $acumValues = 0;
        foreach ($pedidos as $key => $value) {
            $arrayValuesRow = [];
            foreach ($value as $keyb => $valores) {
                $arrayValuesRow[(String) $keyb] = (String) $valores;
            }
            $arrayValues[$acumValues] = (array) $arrayValuesRow;
            $acumValues++;
        }

        return response()->json($arrayValues, 200);

    }

    public function getWebServiceSiesa($idConexion)
    {
        return new WebServiceSiesa($idConexion);
    }

    public function subirPedidoSiesa(Request $request)
    {

        return $this->validarEstructuraJson($request);

        exit();

        foreach ($pedidos as $key => $pedido) {

            $detallePedidos = $this->getDetallePedido($pedido->id_order);

            if (count($detallePedidos) > 0) {

                $this->info("pedido->" . $pedido->id_order);
                $this->info("co_bodega->" . $pedido->co_bodega);
                $this->info("tipo cliente->" . $pedido->tipo_cliente);
                $this->info("fecha pedido->" . $pedido->fecha_pedido    );
                $cadena = "";
                $cadena .= str_pad(1, 7, "0", STR_PAD_LEFT) . "00000001001\n"; // Linea 1

                $cadena .= str_pad(2, 7, "0", STR_PAD_LEFT); //Numero de registros
                $cadena .= str_pad(430, 4, "0", STR_PAD_LEFT); //Tipo de registro
                $cadena .= '00'; //Subtipo de registro
                $cadena .= '02'; //version del tipo de registro
                $cadena .= '001'; //Compañia
                $cadena .= '1'; //Indicador para liquidar impuestos
                $cadena .= '1'; //Indica si el numero consecutivo de docto es manual o automático
                $cadena .= '1'; //Indicador de contacto
                $cadena .= $pedido->co_bodega; //Centro de operación del documento
                $cadena .= 'PEM'; //Tipo de documento
                $cadena .= str_pad($pedido->id_order, 8, "0", STR_PAD_LEFT); //Numero documento
                $cadena .= $pedido->fecha_pedido; //Fecha del documento
                $cadena .= '502'; //Clase interna del documento
                $cadena .= '2'; //Estado del documento
                $cadena .= '0'; //Indicador backorder del documento
                $cadena .= str_pad($pedido->nit_tercero, 15, " ", STR_PAD_RIGHT); //Tercero cliente a facturar
                $cadena .= $pedido->sucursal_tercero; //Sucursal cliente a facturar
                $cadena .= str_pad($pedido->nit_tercero, 15, " ", STR_PAD_RIGHT); //Tercero cliente a despachar
                $cadena .= $pedido->sucursal_tercero; //Sucursal cliente a despachar
                $cadena .= $pedido->tipo_cliente; //Tipo de cliente
                $cadena .= $pedido->co_bodega; //Centro de operacion de la factura
                $cadena .= $this->sumarDias(date('Ymd'), 1); //Fecha Entrega pedido
                $cadena .= '000'; //Nro. dias de entrega del documento
                $cadena .= str_pad($pedido->id_order, 15, "Y", STR_PAD_LEFT); //Orden de compra del Documento
                $cadena .= str_pad($pedido->id_order, 10, "0", STR_PAD_LEFT); //Referencia del documento
                $cadena .= str_pad('GENERICO', 10, " ", STR_PAD_RIGHT); //Codigo de cargue del documento
                $cadena .= 'COP'; //Codigo de moneda del documento
                $cadena .= 'COP'; //Moneda base de conversión
                $cadena .= '00000001.0000'; //Tasa de conversión
                $cadena .= 'COP'; //Moneda local
                $cadena .= '00000001.0000'; //Tasa local
                $cadena .= 'C08'; //Condicion de pago *preguntar*
                $cadena .= '0'; //Estado de impresión del documento
                $observacionesPedido = trim(html_entity_decode($pedido->observaciones_pedido));
                $cadena .= str_pad($this->sanear_string($observacionesPedido), 2000, " ", STR_PAD_RIGHT); //Observaciones del documento
                $cadena .= str_pad('', 15, " ", STR_PAD_LEFT); //cliente de contado
                $cadena .= '000'; //Punto de envio
                $cadena .= str_pad('500', 15, " ", STR_PAD_RIGHT); //Vendedor del pedido
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
                foreach ($detallePedidos as $key => $detallePedido) {
                    //---Declarando variables
                    $bodega = explode('-', $detallePedido->bodega)[0];
                    $listaPrecio = explode('-', $detallePedido->bodega)[1];
                    $centroOperacion = explode('-', $detallePedido->bodega)[2];

                    //----armando cadena

                    $cadena .= str_pad($contador, 7, "0", STR_PAD_LEFT); //Numero consecutivo
                    $cadena .= '0431'; //Tipo registro
                    $cadena .= '00'; //Subtipo registro
                    $cadena .= '02'; //Version del tipo de registro
                    $cadena .= '001'; //compañia
                    $cadena .= $centroOperacion; //Centro de operacion
                    $cadena .= 'PEM'; //Tipo de documento
                    $cadena .= str_pad($pedido->id_order, 8, "0", STR_PAD_LEFT); //Consecutivo de documento
                    $cadena .= str_pad($contadorDetallePedido, 10, "0", STR_PAD_LEFT); //Numero de registro --> hacer contador
                    $cadena .= str_pad($detallePedido->codigo_producto, 7, "0", STR_PAD_LEFT); //Item
                    $cadena .= str_pad('', 50, " ", STR_PAD_LEFT); //Referencia item
                    $cadena .= str_pad('', 20, " ", STR_PAD_LEFT); //Codigo de barras
                    $cadena .= str_pad('', 20, " ", STR_PAD_LEFT); //Extencion 1
                    $cadena .= str_pad('', 20, " ", STR_PAD_LEFT); //Extencion 2
                    $cadena .= $bodega; //Bodega
                    $cadena .= '501'; //Concepto
                    $cadena .= '01'; //Motivo
                    $cadena .= '0'; //Indicador de obsequio
                    $cadena .= $centroOperacion; //Centro de operacion movimiento
                    $cadena .= str_pad('01', 20, " ", STR_PAD_RIGHT); //Unidad de negocio movimiento
                    $cadena .= str_pad('', 15, " ", STR_PAD_LEFT); //Centro de costo movimiento
                    $cadena .= str_pad('', 15, " ", STR_PAD_LEFT); //Proyecto
                    $cadena .= $this->sumarDias(date('Ymd'), 1); //Fecha de entrega del pedido
                    $cadena .= '000'; //Nro. dias de entrega del documento
                    $cadena .= $listaPrecio; //Lista de precio-->agregar al migrar productos
                    $cadena .= 'UNID'; //Unidad de medida-->pendiente
                    $cadena .= str_pad(intval($detallePedido->product_quantity), 15, "0", STR_PAD_LEFT) . '.0000'; //Cantidad base
                    $cadena .= str_pad('', 15, "0", STR_PAD_LEFT) . '.0000'; //Cantidad adicional
                    $cadena .= str_pad(intval($detallePedido->product_price), 15, "0", STR_PAD_LEFT) . '.0000'; //Precio unitario
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

                $nombreArchivo = str_pad($pedido->id_order, 8, "0", STR_PAD_LEFT) . '.txt';
                Storage::disk('local')->put('pandapan/pedidos_txt/' . $nombreArchivo, $cadena);
                // $xmlPedido = $this->crearXmlPedido($lineas, $pedido->id_order);

                // if (!$this->existePedidoSiesa('1', 'PEM', str_pad($pedido->id_order, 15, "Y", STR_PAD_LEFT))) {
                //     $this->info('no existe el pedido ' . $pedido->id_order);
                //     // $resp = $this->getWebServiceSiesa(13)->importarXml($xmlPedido);
                //     if (empty($resp)) {
                //         $this->cambiarEstadoPedido($pedido->id_order, 15);
                //         $this->info('todo ok');
                //     } else {
                //         //  $resp;
                //         $mensaje = "";
                //         foreach ($resp->NewDataSet->Table as $key => $errores) {

                //             $mensaje .= "error $key ->";
                //             foreach ($errores as $key => $detalleError) {
                //                 $mensaje .= '***' . $key . '=>' . $detalleError;
                //             }

                //         }
                //         $this->info($mensaje);
                //         $this->enviarMensaje($pedido->id_order, $mensaje, $pedido->id_customer, $pedido->email);
                //         $this->cambiarEstadoPedido($pedido->id_order, 14);
                //         $this->info('error al enviar pedido');
                //         // $var = print_r($resp->NewDataSet, true);

                //         // echo "\n" . "ANY: " . $var;
                //     }

                // } elseif ($this->existePedidoSiesa('1', 'PEM', str_pad($pedido->id_order, 15, "Y", STR_PAD_LEFT))) {
                //     $this->info('ya existe el pedido ' . $pedido->id_order);
                //     $this->cambiarEstadoPedido($pedido->id_order, 15);
                // }

            }

        }

        $this->info('termino');
        return 0;

    }

    public function crearXmlPedido($lineas, $idOrder)
    {

        $datosConexionSiesa = $this->getConexionesModel()->getConexionXid(13);
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

        $nombreArchivo = str_pad($idOrder, 8, "0", STR_PAD_LEFT) . '.xml';
        //Storage::disk('local')->put('pandapan/pedidos/' . $nombreArchivo, $xmlPedido);

        return $datos;

    }

    public function validarEstructuraJson($request){

        $this->data=$request->input('data');
        $formatoValido=true;
        $formatoValido=$request->input('data')?? false;
        if(!$formatoValido){
            return response()->json( [
                'created' => false,
                'errors'  => "Formato json no válido :(, por favor dirijase a la documentacion"
            ], 400);
        }
        


        $this->encabezado=$this->decodificarArray($this->data[0]);
        // $this->detalle_pedido=$this->data[0]['detalle_pedido'];        
        
        $rules = [
            'numero_pedido'               => 'required|max:8',
            'centro_operacion_bodega'     => 'required|max:8',
            'tipo_cliente'                => 'numeric|required|max:4',
            'fecha_pedido'                => 'required|date_format:"Ymd"',
            'nit_cliente'                 => 'required|max:15',
            'sucursal_tercero'            => 'required|max:15',
            'observaciones_pedido'        => 'max:2000',            
        ];


        $validator = Validator::make($this->encabezado, $rules);
        if ($validator->fails()) {
            return response()->json( [
                'created' => false,
                'errors'  => $validator->errors()
            ], 400);
        }
    }

}


// {
//     "data":[
//         {
//             "numero_pedido": "PEC80001",
//             "centro_operacion_bodega":"001",
//             "tipo_cliente":"0001",
//             "fecha_pedido":"20201101",
//             "nit_cliente":"5415454",
//             "sucursal_tercero":"100019245745",
//             "observaciones_pedido":"",
//             "detalle_pedido":[
//                     {
//                         "codigo_producto": "6039",
//                         "bodega":"00128",
//                         "lista_precio":"ECOM",
//                         "centro_operacion":"001",
//                         "numero_pedido": "PEC80001",                        
//                         "cantidad":10,
//                         "precio_producto": 2550
//                     },
//                     {
//                         "codigo_producto": "6040",
//                         "bodega":"00128",
//                         "lista_precio":"ECOM",
//                         "centro_operacion":"001",
//                         "numero_pedido": "PEC80001",                        
//                         "cantidad":30,
//                         "precio_producto": 4800
//                     }
//             ]

//         }
//     ]
// }

