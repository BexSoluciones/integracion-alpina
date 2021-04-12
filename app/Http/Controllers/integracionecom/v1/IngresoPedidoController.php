<?php

namespace App\Http\Controllers\integracionecom\v1;

use App\Custom\WebServiceSiesa;
use App\Http\Controllers\Controller;
use App\Models\BodegasTiposDocModel;
use App\Models\ConexionesModel;
use App\Traits\TraitHerramientas;
use Illuminate\Http\Request;
use Log;
use Storage;
use Validator;

class IngresoPedidoController extends Controller
{
    public function recibirPedidoJson(Request $request){
        
        //$this->guardarEncabezadoPedido($request);


        $this->guardarDetallePedido($request);
        

    }

    public function guardarPedido($datosPedido){

    }

    public function guardarDetallePedido($request){
        
        $pedidos = $request->input('data');
        // dump('=== pedido completo==');
        // dump($pedidos);
        
        $nuevoArray=[];            
        foreach ($pedidos as $key => $value) {
                       
            $prueba= $pedidos[$key];
            // dump('=== pedido==');
            // dump($prueba);
            
            foreach ($value as $campo => $valor) {
                //$nuevoArray[$campo]=$valor;
                if($campo=='detalle_pedido'){
                    foreach ($valor as $key2 => $value2) {
                        //$prueba2 =  $valor[$key2];
                         $nuevoArray[$key2] = $value2;
                        //dump($nuevoArray);
                    }
                       
                    
                }
            }
        //    dump('==== detalle pedido===');
        //     dump($value);
        //     dump('==== nuevo array===');
            dump($nuevoArray);
            

        }
        
        

    }

    public function guardarEncabezadoPedido($request){

        $pedidos = $request->input('data');
        
        // dd($pedidos[1]['detalle_pedido']);
        

        foreach ($pedidos as $key => $value) {
                       
            //$prueba= $pedidos[$key];
            dump('=== imprimiendo==');

            // unset($value['detalle_pedido']);
            $nuevoArray=[];            
            foreach ($value as $campo => $valor) {
                
                if($campo!='detalle_pedido'){
                    $nuevoArray[$campo]=$valor;
                }
            }
            dump('==== viejo array===');
            dump($value);
            dump('==== nuevo array===');
            dump($nuevoArray);

        }

    }

    public function armarTablaInsertSql($tablaDestino)
    {

        $datos = $this->ejecutarConsulta();
        
        if (!empty($datos)) {

            //----se arma la tabla
            $campos = array_keys((array) $datos[0]);
            //print_r($campos);
            $nuevoArrayCampos = [];
            foreach ($campos as $key => $campo) {
                $nuevoArrayCampos[$key] = $campo . ' text';
            }
            $camposTabla = implode(',', $nuevoArrayCampos);

            $sqlTabla = "CREATE TABLE $tablaDestino  ( $camposTabla ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4; ";

            

            //-----se arma los insert
            $camposInsertSql = implode(',', $campos);
			$sqlInsert = "INSERT INTO $tablaDestino ($camposInsertSql) values ";
			$arrayValues=[];
			$acumValues=0;
            foreach ($datos as $key => $value) {
                $arrayValuesRow = [];
                foreach ($value as $keyb => $valores) {
                    if($keyb=='password'){
                        $arrayValuesRow[$keyb] = "'" .password_hash($valores,1). "'";
                    }elseif($keyb=='secure_key'){
                        $arrayValuesRow[$keyb] = "'" . md5(uniqid(mt_rand(0, mt_getrandmax()), true)) . "'";
                    }else{
                        $arrayValuesRow[$keyb] = "'" . $this->eliminarNumeroCadena(trim($valores)) . "'";
                    }
                    
                }
                $valueInsert = implode(',', $arrayValuesRow);
				$arrayValues[$acumValues]= "($valueInsert)";
				$acumValues++;
			}			
			
			$valuesInsert=implode(',',$arrayValues);

			$resp =[
				'sqlDropTable'=>"DROP TABLE IF EXISTS $tablaDestino; ",
				'sqlCreateTable'=>$sqlTabla,
				'sqlInsert'=>$sqlInsert .= " $valuesInsert;"
			]; 
            return $resp; 
        } else {

            Log::error("$this->cliente : error en la funcion " . __FUNCTION__ . " parametro datos vacío.");

        }

    }
}
