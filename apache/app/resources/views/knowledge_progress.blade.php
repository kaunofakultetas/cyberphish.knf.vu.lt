@extends('layouts.main')

@section('content')

	<!-- Begin Page Content -->
                <div class="container-fluid">



                    <div class="container">

                    @include("parts.notif")

					 <div class="row">

					 	<div class="col-lg-3 bg-light">

 							@include("parts.lm_left_sidebar")

                    	</div>

  						<div class="col-lg-9">



 							<div class="card mb-4 border-left-info">
                                  <div class="card-header"><b> {{ __('main.knowledge_test') }}</div>
                                  <div class="card-body">
										<b>{{ $question }}</b>
										<br><br>

										<div class="form-group row">
                                          <div class="col-12">

                                          @if($finished == 0)

                                          {{ Form::open(array()) }}

                                            @if(count($options) > 0)

                                              	@foreach ($options as $value)

                                              	<label class="custom-control custom-radio text-wrap">
                                                    <input class="custom-control-input" type="radio" name="a[]" value="{{ $value['id'] }}" @if($loop->iteration == 1) required @endif>
                                                    <span class="custom-control-label">{{ $value['option'] }}</span>
                                                </label>

                                              	@endforeach

                                              	<br><br>

                                                  </div>
                                                </div>
                                              <div class="card-footer text-center">
                            					{{ Form::submit(__('main.next'), ['class' => 'btn btn-primary']) }}
                                              	<br><br>

                                                      	<div class="progress mb-4">
                                                	<div class="progress-bar bg-primary" role="progressbar" style="width: {{ $progress }}%;" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100">{{ $progress }}%</div>
                                                </div>

                                            </div>

                                            {{ Form::hidden('pbid', md5(md5($question_id))) }}
		  									{{ Form::close() }}

                                         @endif

										@elseif($finished == 1)

											<center>

											<br>
											<h1>{{ __('main.test_finished') }}</h1>

											@if($results < 75)
												<h2 style="color:red">{{ __('main.you_failed') }}</h2>
											@else
												<h2 style="color:green">{{ __('main.you_passed') }}</h2>
											@endif

											<h3>{{ __('main.results') }}: {{ $results }}% </h3>
											<br>
										</center>

											@if($results > 74)

												@include("parts.change_fullname")

											@endif


										@endif


                                </div>


                            </div>



                        </div>

					</div>

                    </div>



                </div>
                <!-- /.container-fluid -->
  @endsection