<?php

namespace App\Http\Controllers\integracionecom\v1;

use App\Custom\WebServiceSiesa;
use App\Http\Controllers\Controller;
use App\Models\ConexionesModel;
use App\Traits\TraitHerramientas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Log;

$currentPath = Route::getFacadeRoot()->current()->uri();

class CompraDevolucionCompraController extends Controller
{
    use TraitHerramientas;

    public function getComprasDevolucionesCompra(Request $request)
    {

        // $this->validarFiltros($request->input('filter'));

        // exit();
        // $parametrosValidos=$this->validacionParametros();

        Log::info($request->all());
        $idConexion = 29; //id conexion get compras y devoluciones de compra
        $idConexionConteo = 31; //conteo get compras y devoluciones
        $pagina = $request->input('page') && is_numeric($request->input('page')) ? $request->input('page') : null;
        $rutaActual = Route::getFacadeRoot()->current()->uri();

        if (!is_null($pagina)) {
            $filasXpagina = $request->input('per_page') && ctype_digit($request->input('per_page')) ? $request->input('per_page') : 1000;
            $desde = (((int) ($pagina) - 1) * (int) ($filasXpagina));
            $hasta = (int) ($filasXpagina);
            $anterior = $pagina > 2 ? $pagina - 1 : 1;
            $siguiente = $pagina + 1;

            $parametros = [
                ['desde' => $desde],
                ['hasta' => $hasta],
            ];

            Log::info($parametros);

            $objConteo = $this->getWebServiceSiesa($idConexionConteo);
            $datosConteo = $objConteo->ejecutarConsulta($parametros, false);

            $objWebserviceSiesa = $this->getWebServiceSiesa($idConexion);
            $datos = $objWebserviceSiesa->ejecutarConsulta($parametros, true);

            Log::info("============datos conteo===========");
            Log::info($datosConteo);

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
            $datos = $objWebserviceSiesa->ejecutarConsulta([], false);

            $respuesta = [
                'code' => 200,
                'data' => $datos,
            ];
        }

        return response()->json($respuesta, 200);

    }

    public function validarFiltros($param)
    {

        $contador = 0;
        $errores = [];
        foreach ($param as $key => $value) {

            $value=$this->protegerInyeccionSql($value);

            switch ($key) {
                case 'tipo_doc':

                    if ($this->noEmpty($value) === false || $this->validarTipoDoc($value) == false) {
                        $errores[$contador]['tipo_doct'] = 'El campo Tipo de Documento no valido, debe ser de tipo EMC o DP';
                        $errores++;
                    }

                    break;
                case 'bodega':

                    if ($this->noEmpty($value) === false ) {
                        $errores[$contador]['bodega'] = 'El campo Bodega no es valido';
                        $errores++;
                    }

                    break;
                case 'consec_doc':

                    if ($this->noEmpty($value) === false || $this->validarTipoDoc($value) == false) {
                        $errores[$contador]['consec_doc'] = 'Tipo de documento no valido, debe ser de tipo EMC o DP';
                        $errores++;
                    }

                    break;
                case 'fecha_doc':

                    if ($this->noEmpty($value) === false || $this->validarTipoDoc($value) == false) {
                        $errores[$contador]['tipo_doc'] = 'Tipo de documento no valido, debe ser de tipo EMC o DP';
                        $errores++;
                    }

                    break;

                default:
                    # code...
                    break;
            }

        }

        return $errores;

    }

    public function validarTipoDoc($param)
    {

        if ($param == 'EMC' || $param == 'DP') {
            return true;
        }
        return false;
    }

    public function validarBodega($param)
    {
        if(ctype_digit($param)){
            return true;
        }
        return false;
    }

    public function validarConsecDoc($param)
    {

    }

    public function validarFechaDocumento($param)
    {

    }

    public function noEmpty($data)
    {

        if (empty($data)) {
            return false;
        }
        return true;
    }

    public function protegerInyeccionSql($string){

        $listaNegra=['drop','select','delete','truncate','insert','update','create' ];
        $string = str_replace(
            $listaNegra,
            '',
            $string
        );

        return $string;

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
