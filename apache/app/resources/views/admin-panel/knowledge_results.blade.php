@extends('admin-panel.layouts.main')

@section('content')

		<div class="be-content">
            <div class="main-content container-fluid">

            @include("admin-panel.parts.notif")

                  <div class="row">
                    <div class="col-lg-12">
                    <h2 class="page-head-title">Knowledge test results - {{ $email }}</h2> <br>
              <div class="card card-table">
				 <div class="card-body table-responsive">
                  <table class="table" id="table1">


                  		<tr>
						 	<td><b> </b></td>
						 	<td><b> </b></td>
						 	<td><b> </b></td>
						 	<td><b> </b></td>
						 	<td><b> </b></td>
						</tr>


					<tr>
                  	<td colspan="5"><b>---</b></td>
                  </tr>

                  </table>
                </div>



                </div>
          </div>
    	</div>

@endsection