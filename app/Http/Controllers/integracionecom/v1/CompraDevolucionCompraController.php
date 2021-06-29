<?php

namespace App\Http\Controllers\integracionecom\v1;

use App\Custom\WebServiceSiesa;
use App\Http\Controllers\Controller;
use App\Models\ConexionesModel;
use App\Traits\TraitHerramientas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Log;

class CompraDevolucionCompraController extends Controller
{
    use TraitHerramientas;

    const ID_CONEXION = 29;

    public function __construct()
    {
        $this->datosConexion = $this->getConexionesModel()->getConexionXid(self::ID_CONEXION);
       
    }

    public function getComprasDevolucionesCompra(Request $request)
    {

        
        //---------valida filtros en caso de que existan
        $filtros = $request->input('filter');
        $erroresValidacionFiltro = $this->validarFiltros($filtros);

        if (count($erroresValidacionFiltro) > 0) {
            return response()->json([
                'code' => 412,
                'errors' => $erroresValidacionFiltro,
            ], 412);
        }

        //---------Se declaran variables y se valida si se pagina o no
        Log::info($request->all());
        $idConexion = 29; //id conexion get compras y devoluciones de compra
        $idConexionConteo = 31; //conteo get compras y devoluciones
        $pagina = $request->input('page') && is_numeric($request->input('page')) ? $request->input('page') : null;
        $rutaActual = Route::getFacadeRoot()->current()->uri();

        //---------Armamos sql con filtros
        $sqlCompraDevCompra = $this->armarSqlCompraDevoCompra($filtros);

        // if (!is_null($pagina)) {
        //     $filasXpagina = $request->input('per_page') && ctype_digit($request->input('per_page')) ? $request->input('per_page') : 1000;
        //     $desde = (((int) ($pagina) - 1) * (int) ($filasXpagina));
        //     $hasta = (int) ($filasXpagina);
        //     $anterior = $pagina > 2 ? $pagina - 1 : 1;
        //     $siguiente = $pagina + 1;

        //     $parametros = [
        //         ['desde' => $desde],
        //         ['hasta' => $hasta],
        //     ];

        //     Log::info($parametros);

        //     $objConteo = $this->getWebServiceSiesa($idConexionConteo);
        //     $datosConteo = $objConteo->ejecutarConsulta($parametros, false);

        //     $objWebserviceSiesa = $this->getWebServiceSiesa($idConexion);
        //     $datos = $objWebserviceSiesa->ejecutarConsulta($parametros, true);

        //     Log::info("============datos conteo===========");
        //     Log::info($datosConteo);

        //     $totalRegistros = $datosConteo[0]['conteo'];
        //     $totalPaginas = ceil($totalRegistros / $filasXpagina);

        //     $respuesta = [
        //         'code' => 200,
        //         'data' => $datos,
        //         'links' => [
        //             'previous' => $pagina == 1 ? null : url('/') . '/' . $rutaActual . '?page=' . $anterior,
        //             'next' => url('/') . '/' . $rutaActual . '?page=' . $siguiente,
        //         ],
        //         'meta' => [
        //             'total_rows' => $totalRegistros,
        //             'total_page' => $totalPaginas,

        //         ],
        //     ];

        // } else {

            $objWebserviceSiesa = $this->getWebServiceSiesa($idConexion);
            $datos = $objWebserviceSiesa->ejecutarSql($sqlCompraDevCompra);
            if(!empty($datos)){
                $datosAgrupados = $this->groupArray($datos, 'consec_doc',
                [
                    'cia', 'centro_operacion', 'tipo_doc', 'consec_doc', 'fecha_doc', 'periodo_doc', 'nit','razon_social','sucursal_terc','valor_base_gravable','observacion'
                ]
                );

                $respuesta = [
                'code' => 200,
                'data' => $datosAgrupados,
                ];
            }else{
                return response()->json([
                    'code' => 404,
                    'errors' => 'No se encontraron registros'
                ], 404);
            }


            
        // }

        return response()->json($respuesta, 200);

    }

    public function validarFiltros($param)
    {
        $errores = [];
        if (!empty($param)) {

            $contador = 0;

            foreach ($param as $key => $value) {

                $value = $this->protegerInyeccionSql($value);

                switch ($key) {
                    case 'tipo_doc':

                        if ($this->noEmpty($value) === false || $this->validarTipoDoc($value) == false) {
                            $errores['tipo_doc'] = 'El campo Tipo de Documento no valido, debe ser de tipo EN o SA';
                        }

                        break;
                    case 'bodega':

                        if ($this->noEmpty($value) === false || $this->validarBodega($value) === false) {
                            $errores['bodega'] = 'El campo Bodega no es valido, esta vacio o no es un digito';
                        }

                        break;
                    case 'consec_doc':

                        if ($this->noEmpty($value) === false || $this->validarConsecDoc($value) == false) {
                            $errores['consec_doc'] = 'El campo consec_doc no es valido, esta vacio o no es un digito';
                        }

                        break;
                    case 'fecha_doc':

                        if ($this->noEmpty($value) === false ) {
                            $errores['fecha_doc'] = 'El campo fecha_doc esta vacio';
                        }else{

                            $resp= $this->validarFechaDocumento($value);

                            if($resp['valid']===false){
                                $errores['fecha_doc'] = $resp['message'];
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
        if ($param == 'EN' || $param == 'SA') {
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
                $message='El operador logico utilizado en el campo fecha_doc no es valido ';
            }
           

        } else {
            if ($this->validateDate($param)){
                $valid=true;
                $message='';
            }else{
                $valid=false;
                $message='El campo fecha_doc debe tener el formato Y-m-d';
            }
        }
        

        return [
            'valid'=>$valid,
            'message'=>$message
        ];

    }

    public function noEmpty($data)
    {

        if (empty($data)) {
            return false;
        }
        return true;
    }

    public function armarSqlCompraDevoCompra($filtros)
    {
        $cadenaWhere = '';
        if (!empty($filtros)) {

            $where = [];
            $contador = 0;
            foreach ($filtros as $filtro => $value) {

                $value = trim($value);
                $operador='=';

                $tieneSeparador = strpos($value, '|');
                if ($tieneSeparador == true) {        
                    $paramExplode = explode('|', $param);                    
                    $operador = $paramExplode[0];                      
                    
                }
                
                

                
                switch ($filtro) {
                    case 'tipo_doc':

                        $where[$contador] = [$filtro, $operador, $value];
                        $contador++;
                        break;
                    case 'bodega':

                        $where[$contador] = [$filtro, $operador, $value];
                        $contador++;
                        break;
                    case 'consec_doc':

                        $where[$contador] = [$filtro, $operador, $value];
                        $contador++;
                        break;
                    case 'fecha_doc':
                        $where[$contador] = [$filtro, $operador, $value];
                        $contador++;
                        break;
                }

            }

            Log::info($where);
            $arrayCadenaWhere = [];
            foreach ($where as $keya => $filtro) {

                Log::info($filtro);

                $filtroConcatenado = '';
                foreach ($filtro as $keyb => $value) {
                    if ($keyb == 2) {
                        $filtroConcatenado .= '"' . $value . '"';
                    } else {
                        $filtroConcatenado .= $value;
                    }

                }

                $arrayCadenaWhere[$keya] = $filtroConcatenado;

            }

            $cadenaWhere = ' WHERE ' . implode(' and ', $arrayCadenaWhere);

            Log::info('========mostrando cadena=========');
            Log::info($cadenaWhere);

        }

        $sql = '
        SET QUOTED_IDENTIFIER OFF;
        SELECT  * FROM
        (' . $this->datosConexion->siesa_consulta . '  ) AS a' . $cadenaWhere . ';

        SET QUOTED_IDENTIFIER ON;
        ';

        Log::info($sql);

        return $sql;

    }

    public function protegerInyeccionSql($string)
    {
        
        $listaNegra = ['drop', 'select', 'delete', 'truncate', 'insert', 'update', 'create','DROP', 'SELECT', 'DELETE', 'TRUNCATE', 'INSERT', 'update', 'create'];
        $string = str_replace($listaNegra, '', $string);
        return $string;

    }

    public function validarOperador($operador)
    {
        Log::info("===========entrando a la ufncion validar operador=====");
        Log::info("operador --> ".$operador);
        $operadores = ['>', '<', '=', '>=','<=', '<>'];

        $result = in_array($operador, $operadores);
        if ($result) {
            return true;
        }
        return false;
    }

    public function getWebServiceSiesa($idConexion)
    {
        return new WebServiceSiesa($idConexion);
    }

    public function getConexionesModel()
    {
        return new ConexionesModel();
    }

}
