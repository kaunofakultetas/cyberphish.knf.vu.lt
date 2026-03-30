@extends('layouts.main')

@section('content')

	<!-- Begin Page Content -->
                <div class="container-fluid">



                    <div class="container">

                    @include("parts.notif")

					 <div class="row">

  						<div class="col-lg-12">



 						@if($finished == 0)

 						<div class="card mb-4 border-left-info">

     						{{ Form::open(array()) }}

                                  <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between"> {{ __('main.simulation') }} </div>
                                  <div class="card-body">

    										@if(count($options)>0)

    										@if(Session::get('feedback_'.$public_id) !== null)

    										<b>{{ Session::get('feedback_'.$public_id) }}</b><br><br>

    										@endif

    										<br>

    											@foreach($options as $k => $v)

    												<label class="custom-control custom-radio text-wrap">
                                                        <input class="custom-control-input" type="radio" name="a" value="{{ $v['id'] }}" @if($loop->iteration == 1) required @endif>
                                                        <span class="custom-control-label">{{ $v['situation'] }}</span>
                                                    </label>

    											@endforeach

    										@endif

    								  <br><br>

    								  <div class="card-footer text-center">
                        					{{ Form::submit(__('main.next'), ['class' => 'btn btn-primary']) }}
                                        </div>

                                  </div>

    						  {{ Form::close() }}

    						  </div>

						  @endif

						  @if($finished == 1)

						  <div class="card mb-4 border-left-info">

						  <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between"> {{ __('main.simulation') }} </div>
                                  <div class="card-body">

										<center><br>

										@if(Session::get('feedback_'.$public_id) !== null)

    										<b>{{ Session::get('feedback_'.$public_id) }}</b><br><br>

    								    @endif

    										<br>
											<h1>{{ __('main.simulation_finished') }}</h1>
											<h3>{{ __('main.points_collected') }}: {{ $points }} </h3>
											<br>
										</center>

											@if(count($feedback_list)>0)

												@foreach($feedback_list as $kk => $vv)

													{{ $vv['situation'] }}<br>
													<b>{{ $vv['feedback'] }}</b><br><br>

												@endforeach

											@endif

										<center>



										<div class="row">


                                          <div class="col-md-12">


                                          		<a href="{{ env('APP_URL') }}/{{ $lang }}/simulation/{{ $scenario_id }}" style="font-weight:bold!important" class="btn btn-primary p-3 btn-block mb-2">{{ __('main.do_simulation_again') }}</a> <br>

    								  	 </div>

    								 </div>



											<a href="{{ env('APP_URL') }}/{{ $lang }}/simulations" class="btn btn-primary">{{ __('main.other_simulations') }}</a>

											<br><br>
										</center>

								  </div>
						  </div>
						  @endif


                        </div>

					</div>

                    </div>



                </div>
                <!-- /.container-fluid -->
  @endsection