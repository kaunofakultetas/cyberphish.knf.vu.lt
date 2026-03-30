@extends('admin-panel.layouts.main')

@section('content')

		<div class="be-content">
            <div class="main-content container-fluid">

            @include("admin-panel.parts.notif")

                  <div class="row">
                    <div class="col-lg-12">
                    <h2 class="page-head-title">Update Simulation</h2> <br>


            <div class="col-12 col-lg-12">

            <div class="card card-table">
				 <div class="card-body">

								{{ Form::open(array('url' => env('APP_URL_ADMIN').'/admin_simulations/update/'.$uid )) }}



								{{ Form::label('q', __('Situation')) }}<br>
								<div class="row">

								<div class="col-md-12">
    							{{ Form::textarea("situation", $situation, ['class' => 'form-control', 'required' => 'required']) }}
    							</div>
    							</div>
    							<hr>


                        			@foreach ($options as $key1 => $value1)

                        			<div class="row">

									<div class="col-md-1" align="center"> </div>
                        			<div class="col-md-11">
                        			{{ Form::label('o', __('Option:')) }}<br>
                        			</div>

                        				<div class="col-md-1" align="center"><br><br>Level: {{ $value1['level'] }}</div>

                            			<div class="col-md-11">

        									{{ Form::textarea("option[".$value1['id']."]", $value1['situation'] ?? '', ['class' => 'form-control', 'required' => 'required', 'rows'=>2]) }} <br>

        								</div>

										<div class="col-md-1" align="center"><br><br></div>
                            			<div class="col-md-11">
											{{ Form::label('f', __('Feedback:')) }}<br>
        									{{ Form::textarea("feedback[".$value1['id']."]", $value1['feedback'] ?? '', ['class' => 'form-control', 'rows'=>2]) }} <br>

        								</div>

        								<div class="col-md-12">
        									<hr>
        								</div>


    								</div>


                        			@endforeach


                        		{{ Form::submit(__('Submit'), ['class' => 'btn btn-primary']) }}<br><br>
								{{ Form::hidden('uid', $uid ?? '') }}
								{{ Form::close() }}
								<br> <br>


			</div>
			</div>
			</div>


          </div>
    	</div>

@endsection