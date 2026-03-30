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

  						@include("parts.notif")

  						@include("parts.change_username")

  						@include("parts.change_password")


<br><br><br><br><br><br><br><br>

                        </div>

					</div>

                    </div>



                </div>
                <!-- /.container-fluid -->
  @endsection