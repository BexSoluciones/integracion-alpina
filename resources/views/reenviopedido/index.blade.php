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
                        <input class="form-control mr-sm-2" type="search" placeholder="Pedido" aria-label="Search" name="buscar" id="buscar">
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
                                

                                <tr>
                                    <th scope="row">{{ $pedido->numero_pedido }}</th>
                                    <td>{{ $pedido->tipo_documento }}</td>
                                    <td>{{ $pedido->centro_operacion }}</td>
                                    <td>{{ date_format(date_create($pedido->fecha_pedido),"Y-m-d") }}</td>
                                    <td>{{ $pedido->estadoenviows }}</td>
                                    <td >{{ $pedido->msmovws }}</td>
                                    <td ><button type="button" class="btn btn-primary">Reenviar</button></td>
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
@endsection
