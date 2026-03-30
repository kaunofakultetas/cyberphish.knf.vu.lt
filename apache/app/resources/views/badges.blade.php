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
                          <div class="card-header">{{ __('main.my_badges') }}</div>
                          <div class="card-body">

                          <div class="row">

								<div class="col-md-3"> <img data-toggle="tooltip" data-placement="right" title="{{ __('main.badge_topic') }}" src="{{ env('APP_URL') }}/upload/badges/{{ $badge_topic == 0 ? 'tr_' : '' }}badge_topic.png" style="max-width:150px;"> </div>
								<div class="col-md-3"> <img data-toggle="tooltip" data-placement="right" title="{{ __('main.badge_self_evaluation_test') }}" src="{{ env('APP_URL') }}/upload/badges/{{ $badge_self_evaluation_test == 0 ? 'tr_' : '' }}badge_self_evaluation_test.png" style="max-width:150px;"> </div>
								<div class="col-md-3"> <img data-toggle="tooltip" data-placement="right" title="{{ __('main.badge_category') }}" src="{{ env('APP_URL') }}/upload/badges/{{ $badge_category == 0 ? 'tr_' : '' }}badge_category.png" style="max-width:150px;"> </div>
								<div class="col-md-3"> <img data-toggle="tooltip" data-placement="right" title="{{ __('main.badge_all_presentations') }}" src="{{ env('APP_URL') }}/upload/badges/{{ $badge_all_presentations == 0 ? 'tr_' : '' }}badge_all_presentations.png" style="max-width:150px;"> </div>
								<div class="col-md-3"> <img data-toggle="tooltip" data-placement="right" title="{{ __('main.badge_all_simulations') }}" src="{{ env('APP_URL') }}/upload/badges/{{ $badge_all_simulations == 0 ? 'tr_' : '' }}badge_all_simulations.png" style="max-width:150px;"> </div>
								<div class="col-md-3"> <img data-toggle="tooltip" data-placement="right" title="{{ __('main.badge_finished_course') }}" src="{{ env('APP_URL') }}/upload/badges/{{ $badge_finished_course == 0 ? 'tr_' : '' }}badge_finished_course.png" style="max-width:150px;"> </div>
								<div class="col-md-3"> <img data-toggle="tooltip" data-placement="right" title="{{ __('main.badge_final_test') }}" src="{{ env('APP_URL') }}/upload/badges/{{ $badge_final_test == 0 ? 'tr_' : '' }}badge_final_test.png" style="max-width:150px;"> </div>
								<div class="col-md-3"> <img data-toggle="tooltip" data-placement="right" title="{{ __('main.badge_login_10') }}" src="{{ env('APP_URL') }}/upload/badges/{{ $badge_login_10 == 0 ? 'tr_' : '' }}badge_login_10.png" style="max-width:150px;"> </div>

					      </div>

							</div>
						</div>

                        </div>

					</div>

                    </div>



                </div>
                <!-- /.container-fluid -->
  @endsection