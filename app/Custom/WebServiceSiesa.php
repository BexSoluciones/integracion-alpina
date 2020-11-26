<?php

namespace App\Custom;

use App\Models\ConexionesModel;
use Log;
use SoapClient;
use DB;

class WebServiceSiesa
{
    protected $url;
    protected $proxyHost;
    protected $proxyPort;
    protected $conexion;
    protected $idCia;
    protected $proveedor;
    protected $idConsulta;
    protected $usuario;
    protected $clave;
    protected $ConsultaSql;
    protected $cliente;

    public function __construct($idConexion)
    {
        $config = $this->getConexionesModel()->getConexionXid($idConexion);

        $this->url = $config->siesa_url;
        $this->proxyHost = $config->siesa_proxy_host;
        $this->proxyPort = $config->siesa_proxy_port;
        $this->conexion = $config->siesa_conexion;
        $this->idCia = $config->siesa_id_cia;
        $this->proveedor = $config->siesa_proveedor;
        $this->idConsulta = $config->siesa_id_consulta;
        $this->usuario = $config->siesa_usuario;
        $this->clave = $config->siesa_clave;
        $this->ConsultaSql = $config->siesa_consulta;
        $this->cliente = $config->razon_social;

    }

    public function ejecutarConsulta($parametrosSql = null)
    {
        if (is_array($parametrosSql)) {
            $parm = $this->getParametrosXml($parametrosSql);
        } else {
            $parm = $this->getParametrosXml();
        }

        try
        {
            $client = new SoapClient($this->url, $parm);
            $result = $client->EjecutarConsultaXML($parm); //llamamos al métdo que nos interesa con los parámetros
            $schema = @simplexml_load_string($result->EjecutarConsultaXMLResult->schema);
            $any = @simplexml_load_string($result->EjecutarConsultaXMLResult->any);

            if (@is_object($any->NewDataSet->Resultado)) {
                
                return $this->convertirObjetosArrays($any->NewDataSet->Resultado);
            }

            if (@$any->NewDataSet->Table) {
                foreach ($any->NewDataSet->Table as $key => $value) {

                    Log::info("\n");
                    Log::info("\n " . $this->cliente . " Error Linea:\t " . $value->F_NRO_LINEA);
                    Log::info("\n " . $this->cliente . " Error Value:\t " . $value->F_VALOR);
                    Log::info("\n " . $this->cliente . " Error Desc:\t " . $value->F_DETALLE);

                }
            }

        } catch (Exception $e) {
            $error = $e->getMessage();
            Log::info($error);
        }
    }

    public function importarXml($xml)
    {

        try
        {

            $pvstrDatos = "<?xml version='1.0' encoding='utf-8'?>
									<Importar>
									   <NombreConexion>" . $this->conexion . "</NombreConexion>
									   <IdCia>" . $this->idCia . "</IdCia>
									   <Usuario>" . $this->usuario . "</Usuario>
									   <Clave>" . $this->clave . "</Clave>
									<Datos>
									   " . $xml . "
									</Datos>
                                    </Importar>";

            $parm = array(); //parm de la llamada
            $parm['pvstrDatos'] = $pvstrDatos;
            $parm['printTipoError'] = '1';
            $parm['cache_wsdl'] = 0; //new

            $client = new SoapClient($this->url, $parm);
            $result = $client->ImportarXML($parm); //llamamos al métdo que nos interesa con los parámetros
            
            $schema = simplexml_load_string($result->ImportarXMLResult->schema);
            return $any = simplexml_load_string($result->ImportarXMLResult->any);

           

        } catch (Exception $e) {
            $error = $e->getMessage();   
            Log::error($error);         
        }

    }

    public function armarTablaInsertSql($tablaDestino)
    {

        $datos = $this->ejecutarConsulta();

        if (!empty($datos)) {

            //----se arma la tabla
            $campos = array_keys((array) $datos[0]);
            $nuevoArrayCampos = [];
            foreach ($campos as $key => $campo) {
                $nuevoArrayCampos[$key] = $campo . ' text';
            }
            $camposTabla = implode(',', $nuevoArrayCampos);

            $sqlTabla = "CREATE TABLE $tablaDestino  ( $camposTabla ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4; ";

            //-----se arma los insert
            $camposInsertSql = implode(',', $campos);
            $sqlInsert = "INSERT INTO $tablaDestino ($camposInsertSql) values ";
            $arrayValues = [];
            $acumValues = 0;
            foreach ($datos as $key => $value) {
                $arrayValuesRow = [];
                foreach ($value as $keyb => $valores) {
                    if ($keyb == 'password') {
                        $arrayValuesRow[$keyb] = "'" . password_hash($valores, 1) . "'";
                    } elseif ($keyb == 'secure_key') {
                        $arrayValuesRow[$keyb] = "'" . md5(uniqid(mt_rand(0, mt_getrandmax()), true)) . "'";
                    } else {
                        $arrayValuesRow[$keyb] = "'" . $this->eliminarNumeroCadena(trim($valores)) . "'";
                    }

                }
                $valueInsert = implode(',', $arrayValuesRow);
                $arrayValues[$acumValues] = "($valueInsert)";
                $acumValues++;
            }

            $valuesInsert = implode(',', $arrayValues);

            $resp = [
                'sqlDropTable' => "DROP TABLE IF EXISTS $tablaDestino; ",
                'sqlCreateTable' => $sqlTabla,
                'sqlInsert' => $sqlInsert .= " $valuesInsert;",
            ];
            return $resp;
        } else {

            Log::error("$this->cliente : error en la funcion " . __FUNCTION__ . " parametro datos vacío.");

        }

    }

    public function getParametrosXml($parametrosSql = null)
    {
        if (is_array($parametrosSql)) {
            $this->ConsultaSql = $this->reemplazarParametros($parametrosSql, $this->ConsultaSql);
        }

        $parm['pvstrxmlParametros'] = "<Consulta>
												<NombreConexion>" . $this->conexion . "</NombreConexion>
												<IdCia>" . $this->idCia . "</IdCia>
												<IdProveedor>" . $this->proveedor . "</IdProveedor>
												<IdConsulta>SIESA</IdConsulta>
												<Usuario>" . $this->usuario . "</Usuario>
												<Clave>" . $this->clave . "</Clave>
												<Parametros>
													<Sql>" . $this->ConsultaSql . "</Sql>
												</Parametros>
											</Consulta>";

        $parm['printTipoError'] = '1';
        $parm['cache_wsdl'] = '0';

        return $parm;
    }

    public function eliminarNumeroCadena($cadena)
    {
        return str_replace("'", "", $cadena);
    }

    public function reemplazarParametros($parametros, $consultaSql)
    {
        if (is_null($consultaSql)) {
            Log::error("El parametro consultasql es obligatoria. Por favor revise este campo en tabla conexion");
        }

        $nuevaConsultaSql = $consultaSql;
        foreach ($parametros as $key => $parametro) {

            foreach ($parametro as $param => $valor) {
                
                // $nuevaConsultaSql = str_replace('**'.$param.'**',DB::connection()->getPdo()->quote($valor) , $nuevaConsultaSql);
                $nuevaConsultaSql = str_replace('**'.$param.'**',$valor , $nuevaConsultaSql);

            }

        }

        Log::info("=============nueva consulta=====");
        Log::info($nuevaConsultaSql);
        return $nuevaConsultaSql;

    }

    public function getConexionesModel()
    {
        return new ConexionesModel();
    }

    public function convertirObjetosArrays($objetos)
    {
        $arrayValues = [];
        $acumValues = 0;
        foreach ($objetos as $key => $objeto) {
            $arrayValuesRow = [];
            foreach ($objeto as $keyb => $valores) {
                $arrayValuesRow[(String) $keyb] = (String) $valores;
            }
            $arrayValues[$acumValues] = (array) $arrayValuesRow;
            $acumValues++;
        }

        return $arrayValues;
    }

}
