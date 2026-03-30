@extends('admin-panel.layouts.main')

@section('content')

		<div class="be-content">
            <div class="main-content container-fluid">

               @include("admin-panel.parts.notif")

                 <div class="row">
                    <div class="col-lg-12">
                    <h2 class="page-head-title">Users</h2> <br>


            <div class="col-12 col-lg-12">
              <div class="tab-container">
                <ul class="nav nav-tabs nav-tabs-classic" role="tablist" id="TTAB">
                  @if(count($langlist) > 0)

                        @foreach ($langlist as $key => $value)
                  <li class="nav-item"><a class="nav-link {{ $loop->iteration === 1 ? 'active' : '' }}" href="#a{{ $key }}" data-toggle="tab" role="tab">In {{ $value }}</a></li>
                  		@endforeach

                  @endif
                </ul>
                <div class="tab-content">

                @if(count($langlist) > 0)

                        @foreach ($langlist as $key1 => $value1)
                       		<div class="tab-pane {{ $loop->iteration === 1 ? 'active' : '' }}" id="a{{ $key1 }}" role="tabpanel">

								<br>
                				<div class="card-body table-responsive">
                                  <table class="table table-bordered" id="table1">
                                    <thead>
                                      <tr>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Course Progress</th>
                                        <th>Self Evaluation</th>
                                        <th>Simulations</th>
                                        <th>Knowledge results</th>
                                        <th>Last Login</th>
                                      </tr>
                                    </thead>
                                    <tbody>

                                    @if(count($recordlist)>0)

										@foreach($recordlist[$key1] as $k => $v)
										<tr>
											<td>{{ $v['username'] }}</td>
											<td>{{ $v['email'] }}</td>
											<td align="center">{{ $v['course_progress'] }}%</td>
											<td align="center"><a href="{{ env('APP_URL') }}/admin-panel/user/self_evaluation_history/{{ $key1 }}/{{ $v['id'] }}">Self Evaluation History</a></td>
											<td align="center"><a href="{{ env('APP_URL') }}/admin-panel/user/simulations_history/{{ $key1 }}/{{ $v['id'] }}">Simulations History ({{ $v['simulations'] }})</a></td>
											<td align="center">{!! $v['knowledge_results'] !!}</td>
											<td align="center">@if(date("Y-m-d H:i:s", strtotime($v['last_login'])) != '1970-01-01 00:00:00') {{ date("Y-m-d H:i:s", strtotime($v['last_login'])) }} @endif</td>
										</tr>
										@endforeach

								   @else

								   <tr>
								   	<td align="center" colspan="8">No users</td>
								   </tr>


								   @endif

										<tr>
											<td style="font-weight:bold;">Total users:</td>
											<td style="font-weight:bold;" colspan="6">{{ $total_users }}</td>
										</tr>

                                    </tbody>
                                  </table>
                                </div>



                           </div>
                  		@endforeach

                  @endif

                </div>
              </div>
			</div>


          </div>
    	</div>



          </div>
    	</div>

@endsection