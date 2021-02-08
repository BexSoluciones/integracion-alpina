<?php

namespace App\Http\Controllers\integracionecom\v1;

use App\Custom\WebServiceSiesa;
use App\Http\Controllers\Controller;
use App\Traits\TraitHerramientas;
use App\Models\ConexionesModel;
use App\Models\BodegasTiposDocModel;
use Illuminate\Http\Request;
use Log;
use Storage;
use Validator;

class ClienteController extends Controller
{
    use TraitHerramientas;

    public function saveCliente(Request $request){


        dd('hola');


    }
}
