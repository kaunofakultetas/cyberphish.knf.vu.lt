@extends('layouts.main')

@section('content')

	<!-- Begin Page Content -->
                <div class="container-fluid">



                    <div class="container">

					 <div class="row">
  						<div class="col-lg-12">

  						<h2 class="heading cs_heading">{{ $content['title'] }}</h2>
  						<small class="text-muted">{{ date("Y-m-d H:i", strtotime($content['created'])) }}</small>
  						<br><br>

  						 @if(isset($content['feat_img']))

							<img src="{{ env('APP_URL') }}/upload/{{ $content['feat_img'] }}" style="float:left; min-width:250px; max-width:450px; padding:10px;">

  						 @endif

  						 {!! $content['content'] !!}

  						 <br><br><br><br><br>


                        </div>

					</div>

                    </div>



                </div>
                <!-- /.container-fluid -->
  @endsection