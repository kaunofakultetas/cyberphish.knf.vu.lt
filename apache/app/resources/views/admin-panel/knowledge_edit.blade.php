@extends('admin-panel.layouts.main')

@section('content')

		<div class="be-content">
            <div class="main-content container-fluid">

            @include("admin-panel.parts.notif")

                  <div class="row">
                    <div class="col-lg-12">
                    <h2 class="page-head-title">Update knowledge test question</h2> <br>


            <div class="col-12 col-lg-12">

            <div class="card card-table">
				 <div class="card-body">

								{{ Form::open(array('url' => env('APP_URL_ADMIN').'/lm/knowledge_test/update/'.$uid )) }}



								{{ Form::label('q', __('Question')) }}<br>
								<div class="row">

								<div class="col-md-12">
    							{{ Form::text("q", $question, ['class' => 'form-control', 'required' => 'required']) }}
    							</div>
    							</div>
    							<hr>


                        			@foreach ($answers as $key1 => $value1)

                        			<div class="row">

                        			<div class="col-md-10">

    									{{ Form::text("option[".$value1['id']."]", $value1['option'] ?? '', ['class' => 'form-control', 'required' => 'required']) }}<br>

    								</div>

    								<div class="col-md-2">
        							{{ Form::select("correct[".$value1['id']."]", [0=>'incorrect',1=>'correct'], $value1['correct'] ?? '', ['class'=>'form-control', 'placeholder'=>'- Question type -', 'required'=>'required']) }}
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