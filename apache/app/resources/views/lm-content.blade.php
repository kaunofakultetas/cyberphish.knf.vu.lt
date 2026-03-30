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

  						@if($show_self_eval == true)
 							   <div class="card mb-4 border-left-info">
                                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between text-center">

                                    	<center>
                                    		<a href="{{ env('APP_URL') }}/{{ $lang }}/self-evaluation-test/{{ $cat_id }}" class="btn buttonscd2 btn-block" style="width:100%!important"> <b> {{ __('main.self_evaluation_test') }} </b> </a>
                                    	</center>

                                    </div>
                               </div>
 						@endif

 						<div class="card mb-4 border-left-info">

                              <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">

                               {{ $content['title'] }}

                               <div class="dropdown no-arrow">
								@if(Auth::guard('mem')->check())

									@if(!in_array($uid, $user_progress))

    									{{ Form::open(array('url' => env('APP_URL')."/cp/mark_completed" )) }}

    									<button class="btn btn-default pull-right buttonscd2" type="submit">
                                   		<span class="fas fa-check" aria-hidden="true"></span> &nbsp; {{ __('main.mark_completed') }}</button>

                                   		{{ Form::hidden('ued', MD5(MD5(env('APP_SALT') . $uid))) }}

                                   		{{ Form::close() }}

                               		@else

                                   		<button class="btn btn-default pull-right buttonscd" type="button">
                                   		<span class="fas fa-check" aria-hidden="true"></span> &nbsp; {{ __('main.completed') }}</button>

                               		@endif

								@endif


                               </div>

                              </div>
                              <div class="card-body">

 									{!! $content['content'] !!}

 									@if(count($embeded_files) > 0)

										<div class="pl-3">
										@foreach($embeded_files as $kk => $vv)
										<center>
										<div id="adobe-dc-view-{{ $loop->iteration }}" style="min-height: 300px; height: 500px; max-width: 700px;"></div>

										<a href="{{ env('APP_URL') }}/upload/{{ $vv['file_name'] }}" target="_blank">{{ __('main.download_slides') }}</a>
										<br><br>

										@endforeach
										</div>
										</center>
										<br><br>
 									@endif

 									@if(count($additional_files) > 0)

 										<br><br>

 										<h4 class="cs_heading">{{ __('main.additional_files') }}:</h4><br>
										<div class="pl-3">
										@foreach($additional_files as $k => $v)

											<a href="{{ env('APP_URL') }}/upload/{{ $v['file_name'] }}" target="_blank"><i class="fa fa-download" aria-hidden="true"></i> &nbsp; {{ $v['name'] }}</a>  <br><br>

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