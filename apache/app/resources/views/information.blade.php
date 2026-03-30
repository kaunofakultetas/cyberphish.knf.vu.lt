@extends('layouts.main')

@section('content')

	<!-- Begin Page Content -->
                <div class="container-fluid">



                    <div class="container">

					 <div class="row">
  						<div class="col-lg-12">

  						<h2 class="heading cs_heading">{{ $content['title'] }}</h2>
  						<br><br>

  						 {!! $content['content'] !!}

  						 <br><br><br><br><br>


                        </div>

					</div>

                    </div>



                </div>
                <!-- /.container-fluid -->
  @endsection