@extends('admin-panel.layouts.main')

@section('content')

		<div class="be-content">
            <div class="main-content container-fluid">

            @include("admin-panel.parts.notif")

                  <div class="row">
                    <div class="col-lg-12">
                    <h2 class="page-head-title">Users</h2> <br>
              <div class="card card-table">
				 <div class="card-body table-responsive">
                  <table class="table" id="table1">
                    <thead>
                      <tr>
                        <th>Email</th>
                        <th>Last login</th>
                        <th>Last IP</th>
                        <th><center>Status</center></th>
                        <th class="actions"></th>
                      </tr>
                    </thead>
                    <tbody>

                    @if(count($userlist) > 0)

                        @foreach ($userlist as $value)
                            <tr>
                            <td>{{ $value['email'] }}</td>
                            @if ($value['last_login'] > 0)
                            <td>{{ date('Y-m-d H:i:s', strtotime($value['last_login'])) }}</td>
                            @else
                            <td> </td>
                            @endif
                            @if ($value['last_ip'] > 0)
                            <td>{{ $value['last_ip'] }}</td>
                            @else
                            <td> </td>
                            @endif
                            <td><center>
                            	@if ($value['status'] == 1)
                            		<a href="{{ env('APP_URL_ADMIN') }}/user/status/2/{{ $value['id'] }}"><span class="badge badge-success">Active</span></a>
                            	@else
                            		<a href="{{ env('APP_URL_ADMIN') }}/user/status/1/{{ $value['id'] }}"><span class="badge badge-danger">Not active</span></a>
                            	@endif
                            </center></td>
                            <td class="actions"><a class="icon" href="#" onClick="Confirm_Dialog('{{ env('APP_URL_ADMIN') }}/user/delete/{{ $value['id'] }}')"><i class="mdi mdi-delete"></i></a></td>
                          </tr>
                        @endforeach

                        <tfoot>
                        	<tr><td colspan="5">{!! $userlist->links()->render() !!}</td></tr>
                        </tfoot>

                    @elseif(count($userlist) == 0)
                    	<tr>
                    		<td colspan="5"><center>No Users</center></td>
                    	</tr>
                    @endif
                    </tbody>
                  </table>
                </div>



                </div>
          </div>
    	</div>

@endsection