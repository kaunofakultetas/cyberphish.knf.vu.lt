@extends('layouts.main')

@section('content')

	<!-- Begin Page Content -->
                <div class="container-fluid">



                    <div class="container">

					 <div class="row">
                    	<div class="col-lg-3 bg-light">

 							@include("parts.left-user-links")

 							@include("parts.lm_left_sidebar")

                    	</div>

  						<div class="col-lg-9">

  						<div class="card mb-4 border-left-info">
                          <div class="card-header">{{ __('main.simulations_history') }}</div>
                          <div class="card-body">

                          <div class="row">

                          @if(count($my_simulations)>0)

							@foreach($my_simulations as $k => $v)

						       <div class="col-md-4">


						       <a href="{{ env('APP_URL') }}/{{ $lang }}/simulation/progress/{{ $v['public_id'] }}" class="btn btn-primary btn-block mb-2 p-3 text-left itemx">
											ID: <b>{{ $v['id'] }}</b><br>
											{{ __('main.actors') }}: <b>{{ $v['actors'] }}</b><br>
											{{ __('main.choose_type') }}: <b>{{ $v['choose_type'] }}</b><br>
											{{ __('main.attack_type') }}: <b>{{ $v['attack_type'] }}</b><br>
							   </a>

							   <div>
							   <small>{{ __('main.started') }}: {{ $v['started'] }} <br>
								@if($v['finished'] == 1)
									{{ __('main.ended') }}: {{ $v['ended'] }} <br>
									{{ __('main.points') }}: <b> {{ $v['points'] }} </b> <br>
								@endif</small>
								</div>

								<br>

						       </div>

							@endforeach

					      @endif

					      </div>



							</div>
						</div>

                        </div>

					</div>

                    </div>



                </div>
                <!-- /.container-fluid -->
  @endsection