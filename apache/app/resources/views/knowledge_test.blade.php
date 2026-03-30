@extends('layouts.main')

@section('content')

	<!-- Begin Page Content -->
                <div class="container-fluid">



                    <div class="container">

                    @include("parts.notif")

					 <div class="row">

  						<div class="col-lg-12">

 						<div class="card mb-4 border-left-info">

                              <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between"> {{ __('main.knowledge_test') }} </div>
                              <div class="card-body">


								  <br><br>

								  <center><br> {{ __('main.total_tries') }}: {{ $total_tried }}/3</center>


								  @if($count_questions > 0)

    								  @if($total_tried < 3)

    								  {{ Form::open(['url'=>env('APP_URL').'/'.$lang.'/knowledge_start']) }}

    								  <div class="row">

    								  <div class="col-md-12">


                                      </div>
                                      <div class="col-md-12"> <br><br>

    								  		{{ Form::submit(__('main.start'), ['class' => 'btn btn-primary p-3 btn-block', 'style'=>'font-weight:bold!important']) }}

    								  </div>

    								 </div>

    								  {{ Form::close() }}

    								  @else


    								  	<center><br><br>{{-- __('main.not_anymore') --}}</center>


    								  @endif

    						      @else

    						      	<center><br><br>{{ __('main.no_questions') }}</center>

								  @endif

								  <br><br>

								  	<center> <b>{{ $results }}</b> </center>

								  <br><br>

                              </div>
                        </div>

                        @if($passed > 0)

                        	@include("parts.change_fullname")

                        @endif


                        </div>

					</div>

                    </div>



                </div>
                <!-- /.container-fluid -->
  @endsection