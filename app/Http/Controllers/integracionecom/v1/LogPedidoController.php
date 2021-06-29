<?php

namespace App\Http\Controllers\integracionecom\v1;

use App\Http\Controllers\Controller;
use App\Models\LogErrorImportacionModel;
use Log;
use Illuminate\Http\Request;

class LogPedidoController extends Controller
{
    
    public function getLogPedido(Request $request){
        $filtros = $request->input('filter');
        Log::info($filtros);
        $erroresValidacionFiltro = $this->validarFiltros($filtros);

        if (count($erroresValidacionFiltro) > 0) {
            return response()->json([
                'code' => 412,
                'errors' => $erroresValidacionFiltro,
            ], 412);
        }
        $objLogError = new LogErrorImportacionModel();
        $logError = $objLogError->getLogPedidos($filtros);

        if (!empty($logError)) {

            Log::info($logError);
            return response()->json([
                'code' => 200,
                'data' => $logError,
            ], 200);
        } else {
            return response()->json([
                'code' => 404,
                'errors' => 'No se encontraron registros'
            ], 404);
        }

    }

    public function validarFiltros($param)
    {
        $errores = [];
        if (!empty($param)) {

            $contador = 0;

            foreach ($param as $key => $value) {

                $value = $this->protegerInyeccionSql($value);

                switch ($key) {
                    case 'tipo_documento':

                        if ($this->noEmpty($value) === false || $this->validarTipoDoc($value) == false) {
                            $errores['tipo_documento'] = 'El campo Tipo de Documento no valido, debe ser de tipo EN o SA';
                        }

                        break;
                    case 'bodega':

                        if ($this->noEmpty($value) === false || $this->validarBodega($value) === false) {
                            $errores['bodega'] = 'El campo Bodega no es valido, esta vacio o no es un digito';
                        }

                        break;
                    case 'numero_pedido':

                        if ($this->noEmpty($value) === false || $this->validarConsecDoc($value) == false) {
                            $errores['numero_pedido'] = 'El campo numero_pedido no es valido, esta vacio o no es un digito';
                        }

                        break;
                    case 'fecha_pedido':

                        if ($this->noEmpty($value) === false ) {
                            $errores['fecha_pedido'] = 'El campo fecha_pedido esta vacio';
                        }else{

                            $resp= $this->validarFechaDocumento($value);

                            if($resp['valid']===false){
                                $errores['fecha_pedido'] = $resp['message'];
                            }

                        }

                        break;

                }

            }

            Log::info($errores);
            return $errores;

        } else {
            return $errores;
        }

    }

    public function validarTipoDoc($param)
    {

        Log::info('PARAMETRO=>'.$param);
        if ($param == 'PUY' || $param == 'PUM' || $param == 'PUD' || $param == 'PUA' || $param == 'PUC' || $param == 'PUZ') {
            Log::info('Es valido');
            return true;
        }
        Log::info('NO Es valido');
        return false;
    }

    public function validarBodega($param)
    {
        if (ctype_digit($param)) {
            return true;
        }
        return false;
    }

    public function validarConsecDoc($param)
    {
        if (ctype_digit($param)) {
            return true;
        }
        return false;
    }

    public function validarFechaDocumento($param)
    {
        Log::info("================entrando a funcion validar fecha documento ==========");
        Log::info($param);
        $valid=false;
        $message='';
        $param = trim($param);
        $tieneSeparador = strpos($param, '|');
        if ($tieneSeparador == true) {

            $paramExplode = explode('|', $param);
            Log::info("==== explode===");
            Log::info($paramExplode);
            $operador = $paramExplode[0];
            if($this->validarOperador($operador)){
                $fechaDesde   = $paramExplode[1];
                $fechaHasta   = array_key_exists(2,$paramExplode)==true?$paramExplode[2]:null;    
    
                if ($this->validateDate($fechaDesde)){
                    if(is_null($fechaHasta)){
                        $valid=true;
                        $message='';
                    }else{
                        if ($this->validateDate($fechaHasta)){
                            $valid=true;
                            $message='';
                        }else{
                            $valid=false;
                            $message='El formato de la fecha hasta no es valido';
                        }
                    }
                    
                }else{
                    $valid=false;
                    $message='El formato de la fecha desde no es valido';
                }

            }else{
                $valid=false;
                $message='El operador logico utilizado en el campo fecha_pedido no es valido ';
            }
           

        } else {
            if ($this->validateDate($param)){
                $valid=true;
                $message='';
            }else{
                $valid=false;
                $message='El campo fecha_pedido debe tener el formato Y-m-d';
            }
        }
        

        return [
            'valid'=>$valid,
            'message'=>$message
        ];

    }

    public function protegerInyeccionSql($string)
    {
        
        $listaNegra = ['drop', 'select', 'delete', 'truncate', 'insert', 'update', 'create','DROP', 'SELECT', 'DELETE', 'TRUNCATE', 'INSERT', 'update', 'create'];
        $string = str_replace($listaNegra, '', $string);
        return $string;

    }

    public function noEmpty($data)
    {

        if (empty($data)) {
            return false;
        }
        return true;
    }

}
