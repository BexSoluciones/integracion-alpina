<?php

namespace App\Http\Controllers\integracionecom\v1;

use App\Custom\WebServiceSiesa;
use App\Http\Controllers\Controller;
use App\Traits\TraitHerramientas;
use App\Models\ConexionesModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Log;

class InventarioController extends Controller
{
    
    use TraitHerramientas;

    public function getInventario(Request $request)
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
        $idConexion = 32; //id conexion get inventario
        $idConexionConteo = 32; //conteo get inventario
        $pagina = $request->input('page') && is_numeric($request->input('page')) ? $request->input('page') : null;
        // $paginar = !is_null($pagina) ? true : false;
        $rutaActual = Route::getFacadeRoot()->current()->uri();

        if (!is_null($pagina)) {
            $filasXpagina = $request->input('per_page') && ctype_digit($request->input('per_page')) ? $request->input('per_page') : 1000;
            $desde = (((int) ($pagina) - 1) * (int) ($filasXpagina));
            $hasta = (int) ($filasXpagina);
            $anterior = $pagina > 2 ? $pagina - 1 : 1;
            $siguiente = $pagina + 1;

            $paramPaginacion = [
                ['desde' => $desde],
                ['hasta' => $hasta]
            ];    
            
            //---------Armamos sql con filtros
            $sqlInventario = $this->armarSqlInventario($filtros,true,$paramPaginacion);  

            $objConteo = $this->getWebServiceSiesa($idConexionConteo);
            $datosConteo = $objConteo->ejecutarSql($sqlInventario['conteoSqlPrincipal']);

            $objWebserviceSiesa = $this->getWebServiceSiesa($idConexion);
            $datos = $objWebserviceSiesa->ejecutarSql($sqlInventario['sqlPrincipal']);

            $totalRegistros = $datosConteo[0]['conteo'];
            $totalPaginas = ceil($totalRegistros / $filasXpagina);

            $respuesta = [
                'code' => 200,
                'data' => $datos,
                'links' => [
                    'previous' => $pagina == 1 ? null : url('/') . '/' . $rutaActual . '?page=' . $anterior,
                    'next' => url('/') . '/' . $rutaActual . '?page=' . $siguiente,
                ],
                'meta' => [
                    'total_rows' => $totalRegistros,
                    'total_page' => $totalPaginas,

                ],
            ];

        } else {

            $objWebserviceSiesa = $this->getWebServiceSiesa($idConexion);
            $datos = $objWebserviceSiesa->ejecutarSql($sqlInventario['sqlPrincipal']);

            $respuesta = [
                'code' => 200,
                'data' => $datos,
            ];
        }

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

        if ($param == 'EN' || $param == 'SA') {
            return true;
        }
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

    public function armarSqlInventario($filtros,$paginar,$paramPaginacion)
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
        $sqlPrincipal='SELECT * FROM
        (

            SELECT 
		t350_co_docto_contable.f350_id_cia as cia, 
		t350_co_docto_contable.f350_id_co as centro_operacion, 
		CASE t350_co_docto_contable.f350_id_tipo_docto
			WHEN "EM" THEN "EN"
			WHEN "TTE" THEN "SA"
			WHEN "TTS" THEN "SA"
			WHEN "SM" THEN "SA"
			WHEN "AIF" THEN "SA"
			WHEN "TM" THEN "SA"
			WHEN "TMC" THEN "SA"
			WHEN "PM" THEN "SA"
			WHEN "TMP" THEN "SA"
			ELSE f350_id_tipo_docto
		END as tipo_doc,
		t350_co_docto_contable.f350_consec_docto as consec_Doc, 
		t350_co_docto_contable.f350_fecha as fecha_Doc, 
		t350_co_docto_contable.f350_id_periodo as periodo_Doc, 
		t200_mm_terceros.f200_nit as nit,
		t200_mm_terceros.F200_razon_social as razon_social, 
		t202_mm_proveedores.f202_id_sucursal as sucursal_terc, 
		t350_co_docto_contable.f350_notas as observacion,
		t350_co_docto_contable.f350_total_db2 as valor_documento,
		substring(t124_mc_items_referencias.f124_referencia,4,15) as item,
		CASE t150_mc_bodegas.f150_id 
		 	WHEN "00111" THEN "P01" -- UNILEVER MAYORISTA
			WHEN "00121" THEN "P01" -- UNILEVER MEDELLIN
			WHEN "00124" THEN "P01" -- UNILEVER DIRECTOS
			WHEN "00208" THEN "P01" -- UNILEVER PEREIRA
			WHEN "00408" THEN "P01" -- UNILEVER CAUCASIA
			WHEN "00508" THEN "P01" -- UNILEVER MANIZALES
			ELSE f150_id
		END as bodega,
		t470_cm_movto_invent.f470_id_unidad_medida as unid_medida,
		t470_cm_movto_invent.f470_cant_1 as cantidad,
		t470_cm_movto_invent.f470_costo_prom_uni as costo_prom_unit,
		t470_cm_movto_invent.f470_costo_prom_tot as costo_prom_total
	FROM t350_co_docto_contable INNER JOIN t470_cm_movto_invent 
		ON (f350_id_cia = f470_id_cia AND f350_rowid = f470_rowid_docto)
		LEFT JOIN t200_mm_terceros ON (f350_rowid_tercero =  f200_rowid) 
		LEFT JOIN t202_mm_proveedores ON (f350_rowid_tercero = f202_rowid_tercero)
		INNER JOIN t150_mc_bodegas ON (f470_rowid_bodega = f150_rowid)
		INNER JOIN t121_mc_items_extensiones ON (f470_rowid_item_ext = f121_rowid)
		INNER JOIN t120_mc_items on (f121_rowid_item = f120_rowid)
		INNER JOIN t124_mc_items_referencias on (f121_rowid_item = f124_rowid_item)
	WHERE (f350_id_tipo_docto = "EM" OR f350_id_tipo_docto = "TTE" OR f350_id_tipo_docto = "TTS" OR f350_id_tipo_docto = "SM" OR f350_id_tipo_docto = "AIF" OR f350_id_tipo_docto = "TM" OR f350_id_tipo_docto = "TMC" OR f350_id_tipo_docto = "PM" OR f350_id_tipo_docto = "TMP")
		AND (f150_id = "00111" OR f150_id = "00121" OR f150_id = "00124" OR f150_id = "00208" OR f150_id = "00408" OR f150_id = "00508")
		AND t350_co_docto_contable.f350_fecha &gt;= "2021-01-25" AND f124_referencia LIKE "UNI%"

        ) AS a' . $cadenaWhere ;

        $sqlPrincipalConteo = $sqlPrincipal;
        if($paginar){
            Log::info("========aca entreaaaa========");
            $sqlPrincipal=$sqlPrincipal.' **paginacion** ';
            Log::info("========sql con **paginacion**========");
            Log::info($sqlPrincipal);
            $sqlPrincipal=$this->reemplazarParametros($paramPaginacion, $sqlPrincipal);
            Log::info("=======sql con paginacion====");
            Log::info($sqlPrincipal);
        }
        
        $conteoSqlPrincipal=$this->conteoSqlPrincipal($sqlPrincipalConteo);   

        Log::info($sqlPrincipal);
        Log::info($conteoSqlPrincipal);

        return [
            'sqlPrincipal'=>$this->aplicarIdentificador($sqlPrincipal),
            'conteoSqlPrincipal'=>$conteoSqlPrincipal
        ];

    }

    public function reemplazarParametros($paramPaginacion, $consultaSql)
    {
        $seccionPaginacion="ORDER BY (SELECT NULL) OFFSET **desde** ROWS FETCH NEXT **hasta** ROWS ONLY";

        if (is_null($consultaSql)) {
            Log::error("El parametro consultasql es obligatoria. Por favor revise este campo en tabla conexion");
        }

        $nuevaConsultaSql = $consultaSql;
        foreach ($paramPaginacion as $key => $parametro) {
            
            foreach ($parametro as $param => $valor) {
                
                $nuevaConsultaSql = str_replace('**'.$param.'**',$valor , $nuevaConsultaSql);
                if($param=='desde' || $param=='hasta'){
                    $seccionPaginacion = str_replace('**'.$param.'**',$valor , $seccionPaginacion);                    
                }

            }

        }

        
        $nuevaConsultaSql = str_replace('**paginacion**',$seccionPaginacion , $nuevaConsultaSql);
        
        
        // $nuevaConsultaSql="SET QUOTED_IDENTIFIER OFF; \n".$nuevaConsultaSql." \n SET QUOTED_IDENTIFIER ON;";

        Log::info("=============nueva consulta=====");
        Log::info($nuevaConsultaSql);        

        return $nuevaConsultaSql;

    }

    public function aplicarIdentificador($sql){

        $newSql="SET QUOTED_IDENTIFIER OFF; \n";
        $newSql.=$sql;
        $newSql.= "\n SET QUOTED_IDENTIFIER ON;";

        return $newSql;
    }

    public function conteoSqlPrincipal($sql){

        $newSql='select count(*) as conteo from (';
        $newSql.=$sql;
        $newSql.= ') as b ';

        return $this->aplicarIdentificador($newSql);
    }
    

    public function protegerInyeccionSql($string)
    {

        $listaNegra = ['drop', 'select', 'delete', 'truncate', 'insert', 'update', 'create'];
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
