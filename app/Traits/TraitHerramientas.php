<?php

namespace App\Traits;
use DateTime;

trait TraitHerramientas
{
    public static function decodificarArray($array)
    {
        $newArray = [];
        foreach ($array as $key => $value) {
            $newArray[$key] = urldecode($value);
        }
        return $newArray;
    }

    public static function primerLetraMayuscula($array, $excepto = null)
    {
        $newArray = [];
        foreach ($array as $key => $value) {

            if (empty($excepto)) {
                $newArray[$key] = ucfirst($value);
            } else {
                if (!in_array($key, $excepto)) {
                    $newArray[$key] = ucfirst($value);
                } else {
                    $newArray[$key] = $value;
                }
            }

        }
        return $newArray;
    }

    public function convertirArrayMayuscula($array){
        
        $newArray = [];
        foreach ($array as $key => $value) {
            $newArray[$key] = strtoupper($value);
        }
        return $newArray;

    }

    public function quitarSaltosLinea($string)
    {
        //se normaliza saltos de linea
        $string = str_replace(array("\r\n", "\r"), "\n", $string);
        //reemplaza saltos de linea por espacios
        $string = str_replace("\n", ' ', $string);
        return $string;
    }

    public function sanear_string($string)
    {

        $string = trim($string);

        $string = str_replace(
            array('á', 'à', 'ä', 'â', 'ª', 'Á', 'À', 'Â', 'Ä'),
            array('a', 'a', 'a', 'a', 'a', 'A', 'A', 'A', 'A'),
            $string
        );

        $string = str_replace(
            array('é', 'è', 'ë', 'ê', 'É', 'È', 'Ê', 'Ë'),
            array('e', 'e', 'e', 'e', 'E', 'E', 'E', 'E'),
            $string
        );

        $string = str_replace(
            array('í', 'ì', 'ï', 'î', 'Í', 'Ì', 'Ï', 'Î'),
            array('i', 'i', 'i', 'i', 'I', 'I', 'I', 'I'),
            $string
        );

        $string = str_replace(
            array('ó', 'ò', 'ö', 'ô', 'Ó', 'Ò', 'Ö', 'Ô'),
            array('o', 'o', 'o', 'o', 'O', 'O', 'O', 'O'),
            $string
        );

        $string = str_replace(
            array('ú', 'ù', 'ü', 'û', 'Ú', 'Ù', 'Û', 'Ü'),
            array('u', 'u', 'u', 'u', 'U', 'U', 'U', 'U'),
            $string
        );

        $string = str_replace(
            array('ñ', 'Ñ', 'ç', 'Ç'),
            array('n', 'N', 'c', 'C'),
            $string
        );

        //Esta parte se encarga de eliminar cualquier caracter extraño
        $string = str_replace(
            array("¨", "º", "-", "~",
                "", "@", "|", "!",
                "·", "$", "%", "&", "/",
                "(", ")", "?", "'", "¡",
                "¿", "[", "^", "<code>", "]",
                "+", "}", "{", "¨", "",
                ">", "< ", ";", ",", ":",
                ".", "#"),
            '',
            $string
        );

        return $string;
    }

    public function sumarDias($fecha, $cantidad, $formato = 'Ymd')
    {
        $nuevafecha = date($formato, (strtotime('+' . $cantidad . ' day', strtotime($fecha))));
        return $nuevafecha;
    }

    public function getIpCliente()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return $ip;
    }

    //fuente: https://www.php.net/manual/es/function.checkdate.php
    public function validateDate($date, $format = 'Y-m-d')
    {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) == $date;
    }

    public function groupArray($array, $groupkey, $otrosCampoAgrupamiento = [], $nomCampDetalle = "detalle")
    {
        if (count($array) > 0) {
            $otrosCampoAgrupamiento[]=$groupkey;
            $keys = array_keys($array[0]);
            
            foreach ($otrosCampoAgrupamiento as $key => $keyName) {
                $removekey = array_search($keyName, $keys);
                if ($removekey !== false) {
                    unset($keys[$removekey]);
                }
            }

            $groupcriteria = array();
            $return = array();
            foreach ($array as $value) {
                $item = null;
                foreach ($keys as $key) {
                    $item[$key] = $value[$key];
                }
                $busca = array_search($value[$groupkey], $groupcriteria);
                if ($busca === false) {
                    $groupcriteria[] = $value[$groupkey];
                    $encabezado = [
                        $groupkey => $value[$groupkey],
                    ];
                    foreach ($otrosCampoAgrupamiento as $campoGroup) {
                        $encabezado[$campoGroup] = $value[$campoGroup];
                    }
                    $encabezado[$nomCampDetalle] = array();
                    $return[] = $encabezado;
                    $busca = count($return) - 1;
                }
                $return[$busca][$nomCampDetalle][] = $item;
            }
            return $return;
        } else {
            return array();
        }

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
