@if($main == 1)

@extends('admin-panel.layouts.main')

@section('content')

		<div class="be-content">
            <div class="main-content container-fluid">

                  @include('admin-panel.parts.notif')

                 <div class="row">

                 	<div class="col-md-12">

						<h1>Settings - Managers</h1>
                	      <br><br>

              <div class="card card-table">
				 <div class="card-body table-responsive">
                  <table class="table table-hover">
                    <thead>
                      <tr>
                      	<th colspan="10">
                      		<a href="#" onClick="managers_form('new', '')" class="btn btn-primary">Create Manager</a>
                      	</th>
                      </tr>
                      <tr>
                      	<th>Email</th>
                      	<th width="200">Status</th>
                      	<th width="200">Last Login</th>
                      	<th width="200">Last Login IP</th>
                        <th width="80" class="actions"></th>
                        <th width="80" class="actions"></th>
                      </tr>
                    </thead>
                    <tbody>

                    @if(count($managerlist) > 0)

                        @foreach ($managerlist as $value)
                            <tr>
                            <td>{{ $value['email'] }}</td>
                            <td>
                            @if($value['status'] == 1)
                            <a href="{{ env('APP_URL') }}/admin-panel/settings/manager/status/2/{{ $value['id'] }}"><span class="badge badge-success">Enabled</span></a>
                            @else
                            <a href="{{ env('APP_URL') }}/admin-panel/settings/manager/status/1/{{ $value['id'] }}"><span class="badge badge-danger">Disabled</span></a>
                            @endif
                            </td>

                            <td>@if(date('Y-m-d H:i:s', strtotime($value['last_login'])) != '1970-01-01 00:00:00'){{ date('Y-m-d H:i:s', strtotime($value['last_login'])) }}@endif</td>
                            <td>{{ $value['last_ip'] }}</td>

                            <td class="actions"><a class="icon" href="#" onClick="managers_form('edit', {{ $value['id'] }})"><i class="mdi mdi-border-color"></i></a></td>
                            <td class="actions"><a class="icon" href="#" onClick="Confirm_Dialog('{{ env('APP_URL') }}/admin-panel/settings/manager/delete/{{ $value['id'] }}')"><i class="mdi mdi-delete"></i></a></td>
                          </tr>
                        @endforeach


                    @elseif(count($managerlist) == 0)
                    	<tr>
                    		<td colspan="8"><center>No managers found</center></td>
                    	</tr>
                    @endif
                    </tbody>
                  </table>
                </div>



                </div>

                 	</div>

                 </div>

          </div>
    	</div>

@endsection

@endif