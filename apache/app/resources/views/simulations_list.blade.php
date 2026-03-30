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

                              <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between"> {{ $name }} </div>
                              <div class="card-body">

								@if(count($recordlist) > 0)

										<div class="row">

										@foreach($recordlist as $k => $v)

											<div class="col-md-4"><a href="{{ env('APP_URL') }}/{{ $lang }}/simulation/{{ $v['id'] }}" class="btn btn-primary btn-block mb-2 p-3 text-left itemx">
											ID: <b>{{ $v['id'] }}</b><br>
											{{ __('main.actors') }}: <b>{{ $v['actors'] }}</b><br>
											{{ __('main.choose_type') }}: <b>{{ $v['choose_type'] }}</b><br>
											{{ __('main.attack_type') }}: <b>{{ $v['attack_type'] }}</b><br>
											</a>

												@if(count($v['history'])>0)
													<center> <small> {{ __('main.last_ended') }}: {{ $v['history']['ended'] }} </small> </center><br>
												@endif

											</div>

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