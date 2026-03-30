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
                          <div class="card-header">{{ __('main.self_eval_history') }}</div>
                          <div class="card-body">

							@foreach($my_tests as $k => $v)

								@if($v['finished'] == 1)
									<a href="{{ env('APP_URL') }}/cp/se_r/{{ $v['public_id'] }}"><b>{{ $v['category_name'] }}</b></a>
								@else
									<a href="{{ env('APP_URL') }}/cp/se/{{ $v['public_id'] }}/{{ $v['cat_id'] }}"><b>{{ $v['category_name'] }}</b></a>
								@endif
								<br>
								<small>{{ __('main.started') }}: {{ $v['started'] }} <br>
								@if($v['finished'] == 1)
									{{ __('main.ended') }}: {{ $v['ended'] }} <br>
									{{ __('main.points') }}: <b> {{ $v['points'] }} </b> <br>
								@endif</small>
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