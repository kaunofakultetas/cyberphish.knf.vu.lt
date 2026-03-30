@extends('admin-panel.layouts.main')

@section('content')

		<div class="be-content">
            <div class="main-content container-fluid">

            @include("admin-panel.parts.notif")

                  <div class="row">
                    <div class="col-lg-12">
                    <h2 class="page-head-title">Simulations History - {{ $email }} - {{ $language }}</h2> <br>
              <div class="card card-table">
				 <div class="card-body table-responsive">
                  <table class="table" id="table1">

                  <tr>
                  	<td colspan="5"><b>{{ $descr }}</b></td>
                  </tr>

                  		<tr>
						 	<td width="90"><b>Level</b></td>
						 	<td><b>Situation</b></td>
						 	<td><b>Feedback</b></td>
						 	<td><b>Option type</b></td>
						 	<td><b>Points</b></td>
						</tr>

					@foreach($options as $k => $v)

						<tr @if(in_array($v['id'], $selected_options)) class="bg-success text-white" @endif>
						 	<td width="90">{{ $v['level'] }}</td>
						 	<td><div style="display:inline-block;width:{{ $v['level'] * 50 }}px;height:50px;float:left;"></div>{{ $v['situation'] }}</td>
						 	<td>{{ $v['feedback'] }}</td>
						 	<td>{{ $v['option_type'] }}</td>
						 	<td>{{ $v['points'] }}</td>
						</tr>

					@endforeach

					<tr>
                  	<td colspan="5"><b>Total points: {{ $points }}</b></td>
                  </tr>

                  </table>
                </div>



                </div>
          </div>
    	</div>

@endsection