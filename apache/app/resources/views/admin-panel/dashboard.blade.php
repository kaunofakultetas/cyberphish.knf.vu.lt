@extends('admin-panel.layouts.main')

@section('content')

		<div class="be-content">
            <div class="main-content container-fluid">

               @include("admin-panel.parts.notif")

                 <div class="row">
                    <div class="col-lg-12">
                    <h2 class="page-head-title">Users</h2> <br>


            <div class="col-12 col-lg-12">
              <div class="user-search">
                <input type="search" id="user-search" class="form-control" placeholder="Search by user name or email" autocomplete="off">
                <button type="button" class="user-search-clear" title="Clear the search" aria-label="Clear the search" style="display:none;"><span class="mdi mdi-close"></span></button>
              </div>
              <div class="tab-container">
                <ul class="nav nav-tabs nav-tabs-classic" role="tablist" id="TTAB">
                        @foreach ($recordlist as $key => $rows)
                  <li class="nav-item"><a class="nav-link {{ $loop->first ? 'active' : '' }}" href="#a{{ $key }}" data-target="#tab-a{{ $key }}" data-toggle="tab" role="tab">In {{ $langlist[$key] }} ({{ count($rows) }})</a></li>
                  		@endforeach
                  <li class="nav-item"><a class="nav-link {{ count($recordlist) == 0 ? 'active' : '' }}" href="#not-started" data-target="#tab-not-started" data-toggle="tab" role="tab">Not started ({{ count($not_started) }})</a></li>
                </ul>
                <div class="tab-content">

                        @foreach ($recordlist as $key1 => $rows)
                       		<div class="tab-pane {{ $loop->first ? 'active' : '' }}" id="tab-a{{ $key1 }}" role="tabpanel">

								<br>
                				<div class="card-body table-responsive">
                                  <table class="table table-bordered" id="table1">
                                    <thead>
                                      <tr>
                                        <th>Username</th>
                                        <th>Email</th>
                                        @if($countries)<th>Country</th>@endif
                                        <th>Course Progress</th>
                                        <th>Self Evaluation</th>
                                        <th>Simulations</th>
                                        <th>Knowledge results</th>
                                        <th>Last Login</th>
                                      </tr>
                                    </thead>
                                    <tbody>

										@foreach($rows as $v)
										<tr data-search="{{ $v['username'] }} {{ $v['email'] }}">
											<td>{{ $v['username'] }}</td>
											<td>{{ $v['email'] }}</td>
											@if($countries)<td>{{ $countries[$v['country']] ?? '' }}</td>@endif
											<td align="center">{{ $v['course_progress'] }}%</td>
											<td align="center"><a href="{{ env('APP_URL') }}/admin-panel/user/self_evaluation_history/{{ $key1 }}/{{ $v['id'] }}">Self Evaluation History</a></td>
											<td align="center"><a href="{{ env('APP_URL') }}/admin-panel/user/simulations_history/{{ $key1 }}/{{ $v['id'] }}">Simulations History ({{ $v['simulations'] }})</a></td>
											<td align="center">{!! $v['knowledge_results'] !!}</td>
											<td align="center">{{ \App\Http\Controllers\SettingsController::local_time($v['last_login']) }}</td>
										</tr>
										@endforeach

										<tr class="search-none" style="display:none;">
											<td align="center" colspan="{{ $countries ? 8 : 7 }}">No users match the search</td>
										</tr>

										<tr>
											<td style="font-weight:bold;">Total users:</td>
											<td style="font-weight:bold;" colspan="{{ $countries ? 7 : 6 }}">{{ $total_users }}</td>
										</tr>

                                    </tbody>
                                  </table>
                                </div>

                           </div>
                  		@endforeach

                       		<div class="tab-pane {{ count($recordlist) == 0 ? 'active' : '' }}" id="tab-not-started" role="tabpanel">

								<br>
                				<div class="card-body table-responsive">
                                  <table class="table table-bordered">
                                    <thead>
                                      <tr>
                                        <th>Username</th>
                                        <th>Email</th>
                                        @if($countries)<th>Country</th>@endif
                                        <th>Last Login</th>
                                      </tr>
                                    </thead>
                                    <tbody>

										@forelse($not_started as $v)
										<tr data-search="{{ $v['username'] }} {{ $v['email'] }}">
											<td>{{ $v['username'] }}</td>
											<td>{{ $v['email'] }}</td>
											@if($countries)<td>{{ $countries[$v['country']] ?? '' }}</td>@endif
											<td align="center">{{ \App\Http\Controllers\SettingsController::local_time($v['last_login']) }}</td>
										</tr>
										@empty
										<tr>
											<td align="center" colspan="{{ $countries ? 4 : 3 }}">No users</td>
										</tr>
										@endforelse

										<tr class="search-none" style="display:none;">
											<td align="center" colspan="{{ $countries ? 4 : 3 }}">No users match the search</td>
										</tr>

										<tr>
											<td style="font-weight:bold;">Total users:</td>
											<td style="font-weight:bold;" colspan="{{ $countries ? 3 : 2 }}">{{ $total_users }}</td>
										</tr>

                                    </tbody>
                                  </table>
                                </div>

                           </div>

                </div>
              </div>
			</div>


          </div>
    	</div>



          </div>
    	</div>

@endsection
