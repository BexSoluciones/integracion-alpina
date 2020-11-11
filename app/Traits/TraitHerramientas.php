<?php

namespace App\Traits;

use Exception;
use Illuminate\Http\Request;

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

    public static function primerLetraMayuscula($array,$excepto=null){
        $newArray = [];
        foreach ($array as $key => $value) {

            if(empty($excepto)){
                $newArray[$key] = ucfirst($value);
            }else{
                if (!in_array($key, $excepto)) {
                    $newArray[$key] = ucfirst($value);                
                }else{
                    $newArray[$key] =$value;               
                }
            }
            
            
        }
        return $newArray;
    }
}
