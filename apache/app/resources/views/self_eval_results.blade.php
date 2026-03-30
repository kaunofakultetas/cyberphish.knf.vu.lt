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
                                  <div class="card-header"><b>{{ $category_name }}</b> {{ __('main.self_evaluation_test') }}

                                  	<a href="{{ env('APP_URL') }}/{{ $lang }}/self-evaluation-test/{{ $cat_id }}" class="btn btn-primary btn-sm" style="float:right">{{ __('main.do_it_again') }}</a>

                                  </div>
                                  <div class="card-body">

                                  		<div class="row">
                                  		<div class="col-md-6">

                                  			<i class="fa fa-check-circle" aria-hidden="true" style="color:green"></i> - {{ __('main.correct_answer') }} <br>
                                  			<i class="fa fa-times-circle" aria-hidden="true" style="color:red"></i> - {{ __('main.wrong_answer') }} <br>
                                  			<span style="display:inline-block;width:16px;height:16px;background-color:lightgreen"></span> - {{ __('main.selected_answer') }}

                                  		</div>
                                  		<div class="col-md-6 text-right">

                                  			{{ __('main.started') }}: <b> {{ $started }} </b><br>
											{{ __('main.ended') }}: <b> {{ $ended }} </b><br>
											{{ __('main.points') }}: <b> {{ $points_collected }} </b>

										</div>
										</div><br>

										@foreach($results as $key => $val)

											<b><u>{{ $val['question'] }}</u></b><br>

											@foreach($val['answers'] as $k => $v)

												@if($v['correct'] == 1)
												&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <i class="fa fa-check-circle" aria-hidden="true" style="color:green"></i>
												@else
												&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <i class="fa fa-times-circle" aria-hidden="true" style="color:red"></i>
												@endif

 												@if($v['selected'] == 1)
 													<span style="background-color:lightgreen">
 												@else
 													<span>
 												@endif
											    {{ $v['option'] }} </span>
											   <br>

											@endforeach

											<br>

										@endforeach



                                  </div>


                            </div>




                        </div>

					</div>

                    </div>



                </div>
                <!-- /.container-fluid -->
  @endsection