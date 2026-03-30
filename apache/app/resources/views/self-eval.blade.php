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


						@if(count($q) > 0)
  						{{ Form::open(array()) }}

 							<div class="card mb-4 border-left-info">
                                  <div class="card-header"><b>{{ $category_name }}</b> {{ __('main.self_evaluation_test') }}</div>
                                  <div class="card-body">
										<b>{{ $q['question'] }}</b>
										<br><br>

										<div class="form-group row">
                                          <div class="col-12">

                                            @if(count($a) > 0)

                                          	@if($q['q_type'] == 1)

                                              	@foreach ($a as $value)

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

                                          	@elseif($q['q_type'] == 2)

                        						@foreach ($a as $k => $value)

                                                  	<div class="custom-control custom-checkbox">
                                                      <input class="custom-control-input"
                                                      type="checkbox"
                                                      id="a{{ $k }}"
                                                      name="a[]"
                                                      value="{{ $value['id'] }}" id="check3"
                                                      data-parsley-multiple="a[]">
                                                      <label class="custom-control-label" for="a{{ $k }}">{{ $value['option'] }}</label>
                                                    </div>

                                                @endforeach

                                                <br><br>

                                                  </div>
                                                </div>

                                              <div class="card-footer text-center">
                            					{{ Form::submit(__('main.next'), ['class' => 'btn btn-primary', 'id'=>'checkBtn']) }}
                                              	<br><br>

                                                      	<div class="progress mb-4">
                                                	<div class="progress-bar bg-primary" role="progressbar" style="width: {{ $progress }}%;" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100">{{ $progress }}%</div>
                                                </div>

                                            </div>

                                          	@endif



                                          	@endif




                                </div>


                            </div>

							{{ Form::hidden('pbid', md5(md5($q['id']))) }}
		  					{{ Form::close() }}

		  					@else

		  						<center><br><br><br><br><br><br><br><br><br>{{ __('main.no_questions') }}</center>

							@endif


                        </div>

					</div>

                    </div>



                </div>
                <!-- /.container-fluid -->
  @endsection