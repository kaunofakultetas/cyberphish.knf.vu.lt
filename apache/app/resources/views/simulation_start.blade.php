@extends('layouts.main')

@section('content')

	<!-- Begin Page Content -->
                <div class="container-fluid">



                    <div class="container">

                    @include("parts.notif")

					 <div class="row">

  						<div class="col-lg-12">

 						<div class="card mb-4 border-left-info">

                              <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between"> ID: {{ $id }} </div>
                              <div class="card-body">

								 @if($image !== null && $image !== '')
								 	<center>
								 	<img style="max-width:600px;" alt="{{ $descr }}" src="{{ env('APP_URL') }}/upload/simulations_pictures/{{ $image }}.jpg">
								 	<br><br>
								 	</center>
								 @endif

								 {{ $descr }} <br><br>

								  <div class="row">

    								  <div class="col-md-4">

        								  {{ __('main.goal') }}: <b>{{ $goal }}</b><br>
        								  {{ __('main.actors') }}: <b>{{ $actors }}</b><br>
        								  {{ __('main.choose_type') }}: <b>{{ $choose_type }}</b><br>
        								  {{ __('main.attack_type') }}: <b>{{ $attack_type }}</b><br>
        								  @if(isset($source))
        								  		<a href="{{ $source }}" target="_blank">{{ __('main.source') }}</a>
        								  @endif

    								 </div>
    								 <div class="col-md-4">
    								 <b><u>{{ __('main.simulation_categories') }}</u></b><br>

    								 @if(count($categories) > 0)

										@foreach($categories as $k => $v)

											 &nbsp;&nbsp;&nbsp; - {{ $v }} <br>

										@endforeach


									@endif


    								 </div>
    								 <div class="col-md-4">
    								 <b><u>{{ __('main.simulation_attributes') }}</u></b><br>

    								 @if(isset($attributes) && count($attributes) > 0)

										@foreach($attributes as $k => $v)

											 &nbsp;&nbsp;&nbsp; - {{ $v }} <br>

										@endforeach


									@endif


    								 </div>


								  </div>

								  <br><br>

								  {{ Form::open(['url'=>env('APP_URL').'/'.$lang.'/simulation_progress/'.$id]) }}

								  <div class="row">

								  <div class="col-md-12">

								  <div class="p-4 m-3 ml-0 mr-0" style="background-color:#CCEFF2!important;">

								  <label class="custom-control custom-radio text-wrap">
                                           <input class="custom-control-input" type="radio" name="simulation_type" value="1" required>
                                           <span class="custom-control-label">{{ __('main.simulation_po_kiekvieno') }}</span>
                                  </label>

                                  <label class="custom-control custom-radio text-wrap">
                                           <input class="custom-control-input" type="radio" name="simulation_type" value="2" required>
                                           <span class="custom-control-label">{{ __('main.simulation_pabaigoje') }}</span>
                                  </label>

                                  </div>

                                  </div>
                                  <div class="col-md-12">

								  		{{ Form::submit(__('main.start'), ['class' => 'btn btn-primary p-3 btn-block', 'style'=>'font-weight:bold!important']) }}

								  </div>

								 </div>

								  {{ Form::close() }}

								  <br><br><br><br>

                              </div>
                        </div>


                        </div>

					</div>

                    </div>



                </div>
                <!-- /.container-fluid -->
  @endsection