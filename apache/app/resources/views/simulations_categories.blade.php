@extends('layouts.main')

@section('content')

	<!-- Begin Page Content -->
                <div class="container-fluid">



                    <div class="container">

					 <div class="row">
                    	<div class="col-lg-3 bg-light">

 							@include("parts.lm_left_sidebar")

                    	</div>

  						<div class="col-lg-9">

 						<div class="card mb-4 border-left-info">

                              <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between"> {{ __('main.simulations') }} </div>
                              <div class="card-body">

									@if(count($recordlist) > 0)

										<div class="row">

										@foreach($recordlist as $k => $v)

											<div class="col-md-4"><a href="{{ env('APP_URL') }}/{{ $lang }}/simulations/{{ Str::slug($v['name']) }}/{{ Str::slug($v['id']) }}" class="btn btn-primary btn-block mb-2 p-3">{{ $v['name'] }}</a></div>

										@endforeach

										</div>

									@endif

                              </div>
                        </div>


                        </div>

					</div>

                    </div>



                </div>
                <!-- /.container-fluid -->
  @endsection