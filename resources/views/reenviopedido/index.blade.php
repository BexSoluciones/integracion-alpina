@extends('layouts.app')

@section('content')
<div class="container-fluid">


    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">Reenvío pedido</div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    <form class="form-inline my-2 my-lg-0" method="get" action="{{ route('reenviopedido') }}">
                        @csrf
                        <input class="form-control mr-sm-2" type="search" placeholder="Número de pedido" aria-label="Search" name="buscar" id="buscar" value="{{$buscar}}">
                        <button class="btn btn-primary my-4 my-sm-0" type="submit">Buscar</button>
                      </form>
                      <hr>
                        <table class="table table-condensed table-striped" style="font-size:0.7rem;">
                            <thead class="thead-dark">
                              <tr>
                                <th scope="col">Número pedido</th>
                                <th scope="col">Tipo doc.</th>
                                <th scope="col">Cent. Opera</th>
                                <th scope="col">Fecha pedido</th>
                                <th scope="col">Cód. estado</th>
                                <th scope="col" >Mensaje error</th>
                                <th scope="col" >Acción</th>
                              </tr>
                            </thead>
                            <tbody>
                                @foreach ($pedidosError as $pedido)


                                <tr id="fila_{{ $pedido->numero_pedido }}">
                                    <th scope="row">{{ $pedido->numero_pedido }}</th>
                                    <td>{{ $pedido->tipo_documento }}</td>
                                    <td>{{ $pedido->centro_operacion }}</td>
                                    <td>{{ date_format(date_create($pedido->fecha_pedido),"Y-m-d") }}</td>
                                    <td><h5><span class="badge badge-danger ">{{ $pedido->estadoenviows }}</span></h5></td>
                                    <td >{{ $pedido->msmovws }}</td>
                                    <td >
                                        <input type="hidden" name="pedido" id="pedido" value="{{ $pedido->numero_pedido.'|'.$pedido->tipo_documento.'|'.$pedido->centro_operacion.'|'.$pedido->bodega }}">
                                        <button type="button" class="btn btn-primary reenviar" id="{{ $pedido->numero_pedido }}">Reenviar</button>
                                    </td>
                                  </tr>
                                @endforeach


                            </tbody>
                          </table>

                          {{ $pedidosError->links() }}


                </div>
            </div>
        </div>
    </div>
</div>


<script>

// $('#fila_452548').css('background', 'red');

    $(".reenviar").click(function(){

    //    alert($(this).attr("id")) ;
         //declarando objetos
         $textPedido= $(this).siblings('#pedido');
         $trPedido= $(this).parent().parent();
         
         //declarando variables
         pedido = $textPedido.val();
         
        //  $trPedido.css("background", "#e3342f");
        
        $.ajax({
        async: true,
        cache: false,
        type: 'get',
        url: base_path+'/reenviar-pedido',
        data:{
            pedido: pedido
        },
        beforeSend: function () {
            $(this).attr('value', 'Cargando....');
            console.log("cargando...");
        }
    })
        .done(function (respuesta) {

            console.log(respuesta);

            if(respuesta.renviado==true){

                $trPedido.remove();  
                alert(respuesta.mensaje);
                              
                

            }

        })
        .fail(function (jqXHR, ajaxOptions, thrownError) {
            alert("El servidor no responde");
        });

     });


    </script>
@endsection
