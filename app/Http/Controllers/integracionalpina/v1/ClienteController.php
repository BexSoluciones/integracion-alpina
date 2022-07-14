<?php

namespace App\Http\Controllers\integracionalpina\v1;

use App\Custom\WebServiceSiesa;
use App\Http\Controllers\Controller;
use App\Traits\TraitHerramientas;
use App\Models\ConexionesModel;
use App\Models\BodegasTiposDocModel;
use App\Models\Criterios;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ClienteController extends Controller
{
    use TraitHerramientas;

    public function saveCliente(Request $request){

        $respValidacion = $this->validarEstructuraJson($request);

        if ($respValidacion['valid'] == false) {

            return response()->json([
                'created' => false,
                'code' => 412,
                'errors' => $respValidacion['errors'],
            ], 412);

        }

        $datosCliente=$this->convertirArrayMayuscula($this->decodificarArray($request->input('data.0')));

        $respTerceroSiesa=$this->crearTerceroSiesa($datosCliente);

        if($respTerceroSiesa['created']===false){
            return response()->json([
                'created' => false,
                'code' => 412,
                'errors' =>$respTerceroSiesa['errors'] ,
            ], 412);
        }

        $respClienteSiesa=$this->crearClienteSiesa($datosCliente);

        if($respClienteSiesa['created']===false){
            return response()->json([
                'created' => false,
                'code' => 412,
                'errors' =>$respClienteSiesa['errors'] ,
            ], 412);
        }


        $respImpuestoClienteSiesa=$this->crearImpuestoCriterioCliente($datosCliente);
        
        if($respImpuestoClienteSiesa['created']===false){
            return response()->json([
                'created' => false,
                'code' => 412,
                'errors' =>$respImpuestoClienteSiesa['errors'] ,
            ], 412);
        }

        return response()->json([
            'created' => true,
            'code' => 201,
            'errors' =>0,
        ], 201);

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

        $datos=$this->decodificarArray($request->input('data.0'));
          
        //--------Validando tipo identificacion
        $rules = [
            'tipo_identificacion' => [
                'required',
                Rule::in(['C', 'N']),
            ]
        ];
        
        $validator = Validator::make($datos, $rules);

        if ($validator->fails()) {
            return [
                'valid' => false,
                'errors' => $validator->errors(),
            ];
        }

        $rules = [
            'tipo_identificacion' => [
                'required',
                Rule::in(['C', 'N']),
            ],
            'nit' => 'required|max:15',
            'sucursal' => 'required|digits_between:1,3',
            'nombre_establecimiento' => 'required|max:50',
            'direccion' => 'required|max:50',
            'nombre_contacto' => 'required|max:50',
            // 'departamento' => 'required|digits_between:1,2',
            'codigo_ciudad' => 'required|size:5',
            'barrio' => 'required|max:40',
            'telefono' => 'required|max:20',
        ];

        if($datos['tipo_identificacion'] =='C'){
            $rules['nombres']='required|max:20';
            $rules['apellido_1']='required|max:15';
            $rules['apellido_2']='max:15';
        }elseif($datos['tipo_identificacion'] =='N'){
            $rules['razon_social']='required|max:50';
        }       
        
        //--------Validacion final
        
        $validator = Validator::make($datos, $rules);

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

    

    public function crearTerceroSiesa($data){

        $nombres   = $data['tipo_identificacion']=='C'? $data['nombres']: '';
        $apellido1 = $data['tipo_identificacion']=='C'? $data['apellido_1']: '';
        $apellido2 = $data['tipo_identificacion']=='C'? $data['apellido_2']: '';
        $razonSocial = $data['tipo_identificacion']=='N'? $data['razon_social']: '';
        $fecNac = date('Ymd');
        $cadena = "";
        $cadena .= str_pad(1, 7, "0", STR_PAD_LEFT) . "00000001002\n"; // Linea 1

        $cadena .= str_pad(2, 7, "0", STR_PAD_LEFT); //Numero de registros
        $cadena .= str_pad('0200', 4, "0", STR_PAD_LEFT); //Tipo de registro
        $cadena .= '00'; //Subtipo de registro
        $cadena .= '02'; //version del tipo de registro
        $cadena .= '002'; //Compañia
        $cadena .= '0'; //Indica si remplaza la información del tercero cuando este ya existe --> se deja en cero porque debe respetar la información de siesa
        $cadena .= str_pad($data['nit'], 15, " ", STR_PAD_RIGHT); //Código del tercero
        $cadena .= str_pad($data['nit'], 15, " ", STR_PAD_RIGHT);; //Numero de documento de identificación
        $cadena .= '0'; //Digito de verificación del TERC-NIT
        $cadena .= $data['tipo_identificacion']; //Tipo de identificación
        $cadena .= $data['tipo_identificacion']=='C'?'1':'2'; //Tipo de tercero
        $cadena .= str_pad($razonSocial, 50, " ", STR_PAD_RIGHT);; //Razón social
        $cadena .= str_pad($apellido1, 15, " ", STR_PAD_RIGHT); //Apellido 1
        $cadena .= str_pad($apellido2, 15, " ", STR_PAD_RIGHT); //Apellido 2
        $cadena .= str_pad($nombres, 20, " ", STR_PAD_RIGHT); //Nombres
        $cadena .= str_pad($data['nombre_establecimiento'], 50, " ", STR_PAD_RIGHT); //Nombre establecimiento
        $cadena .= '1'; //Indicador de tercero cliente
        $cadena .= '1'; //Indicador de tercero proveedor
        $cadena .= '0'; //Indicador de tercero empleado
        $cadena .= '0'; //Indicador de tercero accionista
        $cadena .= '0'; //Indicador de tercero otros
        $cadena .= '0'; //Indicador de tercero interno
        $cadena .= str_pad($data['nombre_contacto'], 50, " ", STR_PAD_RIGHT); //Contacto
        $cadena .= str_pad($data['direccion'], 40, " ", STR_PAD_RIGHT); //Renglón 1 de la dirección del contacto
        $cadena .= str_pad('', 40, " ", STR_PAD_RIGHT); //Renglón 2 de la dirección del contacto
        $cadena .= str_pad('', 40, " ", STR_PAD_RIGHT); //Renglón 3 de la dirección del contacto
        $cadena .= '169'; //País
        $cadena .= $data['codigo_ciudad']; //Ciudad y departamento
        $cadena .= str_pad($data['barrio'], 40, " ", STR_PAD_RIGHT);//Barrio
        $cadena .= str_pad($data['telefono'], 20, " ", STR_PAD_RIGHT);//Telefono
        $cadena .= str_pad('', 20, " ", STR_PAD_RIGHT);//Fax
        $cadena .= str_pad('', 10, " ", STR_PAD_RIGHT);//Codigo postal
        $cadena .= str_pad('factura720@gmail.com', 50, " ", STR_PAD_RIGHT);//Dirección de correo electrónico
        $cadena .= str_pad($fecNac, 8, "0", STR_PAD_RIGHT); // Fecha nacimiento
        if($data['tipo_identificacion']=='N'){
            $cadena .= str_pad('4711', 4, "0", STR_PAD_LEFT); //Valida en maestro, código del actividad económica 
        }elseif ($data['tipo_identificacion']=='C') {
            $cadena .= str_pad('0081', 4, "0", STR_PAD_LEFT); //Valida en maestro, código del actividad económica 
        }
        $cadena .= "\n";
        $cadena .= str_pad(3, 7, "0", STR_PAD_LEFT)."99990001002";

        $lineas = explode("\n", $cadena);

        $nombreArchivo = str_pad($data['nit'], 15, "0", STR_PAD_LEFT) . '.xml';
        
        $xml=$this->crearXml('pandapan/terceros/xml/',$nombreArchivo,$lineas);
        $respImport=$this->importarXml($xml);

        if($respImport['created']===true){
            return [
                'created' => true,
                'errors' => 0,
            ];
        }elseif($respImport['created']===false){
            return [
                'created' => false,
                'errors' => $respImport['errors'],
            ];
        }

    }

    public function crearClienteSiesa($data){

        
        $cadena = "";
        $cadena .= str_pad(1, 7, "0", STR_PAD_LEFT) . "00000001002\n"; // Linea 1

        $cadena .= str_pad(2, 7, "0", STR_PAD_LEFT); //Numero de registros
        $cadena .= str_pad('0201', 4, "0", STR_PAD_LEFT); //Tipo de registro
        $cadena .= '00'; //Subtipo de registro
        $cadena .= '01'; //version del tipo de registro
        $cadena .= '002'; //Compañia
        $cadena .= '0'; //Indica si remplaza la información del tercero cuando este ya existe --> se deja en cero porque debe respetar la información de siesa
        $cadena .= str_pad($data['nit'], 15, " ", STR_PAD_RIGHT); //Código del cliente
        $cadena .= str_pad($data['sucursal'], 3, " ", STR_PAD_RIGHT); //Sucursal del cliente
        $cadena .= '1'; //Estado del cliente
        $cadena .= str_pad($data['nombre_contacto'], 40, " ", STR_PAD_RIGHT); //Razón social del cliente
        $cadena .= 'COP'; //Moneda
        $cadena .= str_pad('000', 4, " ", STR_PAD_RIGHT);; //Codigo del vendedor
        $cadena .= 'A'; //Clasificacion
        $cadena .= 'C01'; //Condicion de pago
        $cadena .= str_pad(2, 3, " ", STR_PAD_LEFT); //Días de gracia
        $cadena .= '+000000002000000.0000';//Cupo de credito
        $cadena .= '0001';//Tipo de cliente
        $cadena .= str_pad('', 4, " ", STR_PAD_RIGHT); //Grupo de descuento
        $cadena .= '001'; //Lista de precios
        $cadena .= '0'; //Indicador de backorder
        $cadena .= '9999.99'; //Porcentaje para poder vender por encima de lo pedido
        $cadena .= '0000.00'; //Porcentaje de margen mínimo
        $cadena .= '0000.00'; //Porcentaje de margen máximo
        $cadena .= '0'; //Indicador de bloquea por cupo
        $cadena .= '0'; //Indicador de bloquea por mora
        $cadena .= '0'; //Indicador de factura unificada
        $cadena .= str_pad('', 3, " ", STR_PAD_RIGHT); //Centro de operación por defecto para facturación
        $cadena .= str_pad('ALPINA', 255, " ", STR_PAD_RIGHT); //Observaciones
        $cadena .= str_pad($data['nombre_contacto'], 50, " ", STR_PAD_RIGHT); //contacto
        $cadena .= str_pad($data['direccion'], 40, " ", STR_PAD_RIGHT); //direccion1
        $cadena .= str_pad($data['nombre_establecimiento'], 40, " ", STR_PAD_RIGHT); //direccion2
        $cadena .= str_pad($data['nit'].'-S-'.$data['sucursal'], 40, " ", STR_PAD_RIGHT); //direccion3
        $cadena .= '169'; //Pais
        $cadena .= $data['codigo_ciudad']; //Departamento - ciudad -> aca van las dos al tiempo
        $cadena .= str_pad($data['barrio'], 40, " ", STR_PAD_RIGHT); //Barrio
        $cadena .= str_pad($data['telefono'], 20, " ", STR_PAD_RIGHT); //Telefono
        $cadena .= str_pad('', 20, " ", STR_PAD_RIGHT); //Fax
        $cadena .= str_pad('', 10, " ", STR_PAD_RIGHT); //Codigo postal
        $cadena .= str_pad('factura720@gmail.com', 50, " ", STR_PAD_RIGHT); //Direccion de correo electrónico
        $cadena .= "\n";
        $cadena .= str_pad(3, 7, "0", STR_PAD_LEFT)."99990001002";

        $lineas = explode("\n", $cadena);

        $nombreArchivo = str_pad($data['nit'], 15, "0", STR_PAD_LEFT) . '.txt';
        
        $xml=$this->crearXml('pandapan/clientes/xml/',$nombreArchivo,$lineas);
        $respImport=$this->importarXml($xml);

        
        if($respImport['created']===true){
            return [
                'created' => true,
                'errors' => 0,
            ];
        }elseif($respImport['created']===false){
            return [
                'created' => false,
                'errors' => $respImport['errors'],
            ];
        }

    }

    public function crearImpuestoCriterioCliente($data){
        //Asignar impuestos IVA
        $cadena = "";
        $cadena .= str_pad(1, 7, "0", STR_PAD_LEFT) . "00000001002\n"; // Linea 1

        $cadena .= str_pad(2, 7, "0", STR_PAD_LEFT); //Numero de registros
        $cadena .= str_pad('0046', 4, "0", STR_PAD_LEFT); //Tipo de registro
        $cadena .= '00'; //Subtipo de registro
        $cadena .= '01'; //version del tipo de registro
        $cadena .= '002'; //Compañia
        $cadena .= '0'; //Indica si remplaza la información del tercero cuando este ya existe --> se deja en cero porque debe respetar la información de siesa
        $cadena .= str_pad($data['nit'], 15, " ", STR_PAD_RIGHT); //Código del cliente        
        $cadena .= str_pad($data['sucursal'], 3, " ", STR_PAD_RIGHT); //Sucursal del cliente
        $cadena .= '001'; //Código de la clase de impuesto / retención
        $cadena .= '01'; //Configuracion del tercero respecto al impuesto / retención
        $cadena .= str_pad('', 4, " ", STR_PAD_RIGHT); //Llave de impuesto / retención
        $cadena .= "\n";

        //Asignar impuestos IMPCO
        $cadena .= str_pad(3, 7, "0", STR_PAD_LEFT); //Numero de registros
        $cadena .= str_pad('0046', 4, "0", STR_PAD_LEFT); //Tipo de registro
        $cadena .= '00'; //Subtipo de registro
        $cadena .= '01'; //version del tipo de registro
        $cadena .= '002'; //Compañia
        $cadena .= '0'; //Indica si remplaza la información del tercero cuando este ya existe --> se deja en cero porque debe respetar la información de siesa
        $cadena .= str_pad($data['nit'], 15, " ", STR_PAD_RIGHT); //Código del cliente        
        $cadena .= str_pad($data['sucursal'], 3, " ", STR_PAD_RIGHT); //Sucursal del cliente
        $cadena .= '003'; //Código de la clase de impuesto / retención
        $cadena .= '01'; //Configuracion del tercero respecto al impuesto / retención
        $cadena .= str_pad('', 4, " ", STR_PAD_RIGHT); //Llave de impuesto / retención
        $cadena .= "\n";
        
        //Asignar impuestos RETENCION

        $cadena .= str_pad(4, 7, "0", STR_PAD_LEFT); //Numero de registros
        $cadena .= str_pad('0047', 4, "0", STR_PAD_LEFT); //Tipo de registro
        $cadena .= '00'; //Subtipo de registro
        $cadena .= '01'; //version del tipo de registro
        $cadena .= '002'; //Compañia
        $cadena .= '0'; //Indica si remplaza la información del tercero cuando este ya existe --> se deja en cero porque debe respetar la información de siesa
        $cadena .= str_pad($data['nit'], 15, " ", STR_PAD_RIGHT); //Código del cliente        
        $cadena .= str_pad($data['sucursal'], 3, " ", STR_PAD_RIGHT); //Sucursal del cliente
        $cadena .= '001'; //Código de la clase de impuesto / retención
        if($data['tipo_identificacion']=='N'){
            $cadena .= '01'; //Configuracion del tercero respecto al impuesto / retención
        }elseif ($data['tipo_identificacion']=='C') {
            $cadena .= '00'; //Configuracion del tercero respecto al impuesto / retención
        }
        $cadena .= str_pad('', 4, " ", STR_PAD_RIGHT); //Llave de impuesto / retención
        $cadena .= "\n";

        // Criterios Clasificación clientes
        $criteriosCliente = Criterios::get();
        $count = 5;
        foreach ($criteriosCliente as $key => $criterio) {
            $cadena .= str_pad($count, 7, "0", STR_PAD_LEFT); //Numero de registros
            $cadena .= str_pad('0207', 4, "0", STR_PAD_LEFT); //Tipo de registro
            $cadena .= '00'; //Subtipo de registro
            $cadena .= '01'; //version del tipo de registro
            $cadena .= '002'; //Compañia
            $cadena .= '0'; //Indica si remplaza la información del tercero cuando este ya existe --> se deja en cero porque debe respetar la información de siesa
            $cadena .= str_pad($data['nit'], 15, " ", STR_PAD_RIGHT); //Código del cliente        
            $cadena .= str_pad($data['sucursal'], 3, " ", STR_PAD_RIGHT); //Sucursal del cliente
            $cadena .= str_pad($criterio['Plan'], 3," ", STR_PAD_RIGHT); // Plan de criterio de clasificación
            $cadena .= str_pad($criterio['Criterio'], 10," ", STR_PAD_RIGHT); // Mayor de criterio de clasificación
            $cadena .= "\n";

            $count++;
        }


        //$numRegistro=$numRegistro+1;
        
        
        $cadena .= str_pad($count, 7, "0", STR_PAD_LEFT)."99990001002";

        $lineas = explode("\n", $cadena);

        $nombreArchivo = str_pad($data['nit'], 15, "0", STR_PAD_LEFT) . '.xml';
        
        $xml=$this->crearXml('pandapan/impuestos_cliente/xml/',$nombreArchivo,$lineas);
        $respImport=$this->importarXml($xml);

        if($respImport['created']===true){
            return [
                'created' => true,
                'errors' => 0,
            ];
        }elseif($respImport['created']===false){
            return [
                'created' => false,
                'errors' => $respImport['errors'],
            ];
        }

    }

    public function crearArchivoTxt($directorio,$nombreArchivo,$cadena){

        Storage::disk('local')->put($directorio . $nombreArchivo, $cadena);

    }

    public function crearXml($directorio,$nombreArchivo,$lineas){

    
        $datosConexionSiesa = $this->getConexionesModel()->getConexionXid(35);
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

        
        Storage::disk('local')->put($directorio . $nombreArchivo, $xmlPedido);

        return $datos;

    
    }

    public function importarXml($xml){        

        $resp = $this->getWebServiceSiesa(35)->importarXml($xml);
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

    public function getConexionesModel()
    {
        return new ConexionesModel();
    }

    public function getWebServiceSiesa($idConexion)
    {
        return new WebServiceSiesa($idConexion);
    }

    

    
}


//CAMPOS SEGUN JANDER
// tipo_identificacion -> solo permitir C o N
// nit
// sucursal
// razon_social
// nombre_establecimiento
// direccion
// nombre_contacto
// departamento->recibir como tipo texto validar el cero a la izquierda  ejemplo 05
// ciudad->recibir como tipo texto validar el cero a la izquierda ejemplo 001
// barrio-> texto
// telefono-> texto




// campos por defecto
// codigo_cliente y codigo_tercero son el nit


// cuando sea una cedula nos debe llenar la informacion de primer nombre y primer apellido
// cuando es nit es obligatorio el campo de razon social

// si es C poner 1 si es N poner un dos en el campo Tipo tercero


// {
//     "data":[
//         {
//             "tipo_identificacion": "C",
//             "nit": "1152197046",
//             "sucursal": "002",
//             "razon_social":"Robledo bajo supermercado",
//             "nombre_establecimiento":"Supermercado Meli sucursal 2",
//             "nombres": "Melissa",
//             "apellido_1": "Gil",
//             "apellido_2": "Montenegro",
//             "direccion": "Calle 12",
//             "nombre_contacto": "Melissa Gil Montenegro",
//             "codigo_ciudad": "05001",
//             "barrio": "Robledo",
//             "telefono":"65443"          
            
//         }
//     ]
// }


