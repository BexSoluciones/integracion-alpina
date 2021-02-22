<?php

namespace App\Http\Controllers\integracionecom\v1;

use App\Custom\WebServiceSiesa;
use App\Http\Controllers\Controller;
use App\Traits\TraitHerramientas;
use App\Models\ConexionesModel;
use App\Models\BodegasTiposDocModel;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Log;
use Storage;
use Validator;

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

        dd('termino proceso');

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
            $rules['apellido_2']='required|max:15';
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

    public function crearClienteSiesa($data){

    }

    public function crearTerceroSiesa($data){

        $nombres   = $data['tipo_identificacion']=='C'? $data['nombres']: '';
        $apellido1 = $data['tipo_identificacion']=='C'? $data['apellido_1']: '';
        $apellido2 = $data['tipo_identificacion']=='C'? $data['apellido_2']: '';
        $razonSocial = $data['tipo_identificacion']=='N'? $data['razon_social']: '';
        
        $cadena = "";
        $cadena .= str_pad(1, 7, "0", STR_PAD_LEFT) . "00000001001\n"; // Linea 1

        $cadena .= str_pad(2, 7, "0", STR_PAD_LEFT); //Numero de registros
        $cadena .= str_pad('0200', 4, "0", STR_PAD_LEFT); //Tipo de registro
        $cadena .= '00'; //Subtipo de registro
        $cadena .= '01'; //version del tipo de registro
        $cadena .= '001'; //Compañia
        $cadena .= '0'; //Indica si remplaza la información del tercero cuando este ya existe --> se deja en cero porque debe respetar la información de siesa
        $cadena .= str_pad($data['nit'], 15, " ", STR_PAD_RIGHT); //Código del tercero
        $cadena .= str_pad($data['nit'], 15, " ", STR_PAD_RIGHT);; //Numero de documento de identificación
        $cadena .= '0'; //Digito de verificación del TERC-NIT
        $cadena .= $data['tipo_identificacion']; //Tipo de identificación
        $cadena .= $data['tipo_identificacion']=='C'?'1':'2'; //Tipo de tercero
        $cadena .= str_pad($razonSocial, 50, " ", STR_PAD_RIGHT);; //Razón social
        $cadena .= str_pad($data['apellido_1'], 15, " ", STR_PAD_RIGHT); //Apellido 1
        $cadena .= str_pad($data['apellido_2'], 15, " ", STR_PAD_RIGHT); //Apellido 2
        $cadena .= str_pad($data['nombres'], 20, " ", STR_PAD_RIGHT); //Nombres
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
        $cadena .= "\n";
        $cadena .= str_pad(3, 7, "0", STR_PAD_LEFT)."99990001001";

        $lineas = explode("\n", $cadena);

        $nombreArchivo = str_pad($data['nit'], 15, "0", STR_PAD_LEFT) . '.txt';
        
        $xml=$this->crearXml($nombreArchivo,$lineas);
        $respImport=$this->importarXml($xml);

        if($respImport['created']===true){
            return [
                'created' => true,
                'errors' => 0,
            ];
        }elseif($respImport['created']===false){
            return [
                'created' => false,
                'errors' => $respValidacion['errors'],
            ];
        }

    }

    public function crearArchivoTxt($nombreArchivo,$cadena){

        Storage::disk('local')->put('pandapan/terceros/txt/' . $nombreArchivo, $cadena);

    }

    public function crearXml($nombreArchivo,$lineas){

    
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

        
        Storage::disk('local')->put('pandapan/terceros/xml/' . $nombreArchivo, $xmlPedido);

        return $datos;

    
    }

    public function importarXml($xml){        

        $resp = $this->getWebServiceSiesa(35)->importarXml($xml);

        Log::info("========aca entra este pirobo=====");
        Log::info($resp->NewDataSet->Table);
                if (empty($resp)) {
                    $transaccionExitosa=true;
                    return [
                        'created'=>true,
                        'errors'=>0
                    ];
                } else {

                    return [
                        'created'=>true,
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
//             "tipo_identificacion": "",
//             "nit": "",
//             "sucursal": "",
//             "razon_social":"",
//             "nombre_establecimiento":"",
//             "direccion": "",
//             "nombre_contacto": "",
//             "departamento": "",
//             "ciudad": "",
//             "barrio": "",
//             "telefono":""          
            
//         }
//     ]
// }


