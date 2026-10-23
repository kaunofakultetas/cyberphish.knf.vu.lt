@extends('admin-panel.layouts.main')

@section('content')

		<div class="be-content">
            <div class="main-content container-fluid">

            @include("admin-panel.parts.notif")

                  <div class="row">
                    <div class="col-lg-12">
                    <h2 class="page-head-title">Users</h2> <br>
              <div class="user-search">
                <input type="search" id="user-search" class="form-control" placeholder="Search by user name or email" autocomplete="off">
                <button type="button" class="user-search-clear" title="Clear the search" aria-label="Clear the search" style="display:none;"><span class="mdi mdi-close"></span></button>
              </div>
              <div class="card card-table">
				 <div class="card-body table-responsive">
                  <table class="table" id="table1">
                    <thead>
                      <tr>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Country</th>
                        <th>Last login</th>
                        <th>Last IP</th>
                        <th><center>Status</center></th>
                        <th class="actions"></th>
                      </tr>
                    </thead>
                    <tbody>

                    @if(count($userlist) > 0)

                        @foreach ($userlist as $value)
                            <tr data-search="{{ $value['username'] }} {{ $value['email'] }}">
                            <td>{{ $value['username'] }}</td>
                            <td>{{ $value['email'] }}</td>
                            <td>{{ $countries[$value['country']] ?? '' }}</td>
                            <td>{{ \App\Http\Controllers\SettingsController::local_time($value['last_login']) }}</td>
                            @if ($value['last_ip'] > 0)
                            <td>{{ $value['last_ip'] }}</td>
                            @else
                            <td> </td>
                            @endif
                            <td><center>
                            	@if ($value['status'] == 1)
                            		<a href="#" onClick="Post_To('{{ env('APP_URL_ADMIN') }}/user/status/2/{{ $value['id'] }}'); return false;"><span class="badge badge-success">Active</span></a>
                            	@else
                            		<a href="#" onClick="Post_To('{{ env('APP_URL_ADMIN') }}/user/status/1/{{ $value['id'] }}'); return false;"><span class="badge badge-danger">Not active</span></a>
                            	@endif
                            </center></td>
                            <td class="actions"><a class="icon" href="#" onClick="Confirm_Dialog('{{ env('APP_URL_ADMIN') }}/user/delete/{{ $value['id'] }}'); return false;"><i class="mdi mdi-delete"></i></a></td>
                          </tr>
                        @endforeach

                        <tr class="search-none" style="display:none;">
                            <td colspan="7"><center>No users match the search</center></td>
                        </tr>

                        <tr>
                            <td style="font-weight:bold;">Total users:</td>
                            <td style="font-weight:bold;" colspan="6">{{ count($userlist) }}</td>
                        </tr>

                    @else
                    	<tr>
                    		<td colspan="7"><center>No Users</center></td>
                    	</tr>
                    @endif
                    </tbody>
                  </table>
                </div>



                </div>
          </div>
    	</div>

@endsection
