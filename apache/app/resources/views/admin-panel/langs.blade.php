@extends('admin-panel.layouts.main')

@section('content')

		<div class="be-content">
            <div class="main-content container-fluid">

            @include("admin-panel.parts.notif")

                  <div class="row">
                    <div class="col-lg-12">
                    <h2 class="page-head-title">Languages</h2> <br>
              <div class="card card-table">
				 <div class="card-body table-responsive">
                  <table class="table" id="table1">
                    <thead>
                      <tr>
                        <th><a href="#" onClick="lang_form('new', '')" class="btn btn-primary">Create Language</a></th>
                        <th class="actions"></th>
                        <th class="actions"></th>
                      </tr>
                    </thead>
                    <tbody>

                    @if(count($recordlist) > 0)

                        @foreach ($recordlist as $value)
                            <tr>
                            <td>{{ $value['name'] }}</td>
                            <td class="actions"><a class="icon" href="#" onClick="lang_form('edit', '{{ $value['id'] }}')"><i class="mdi mdi-settings"></i></a></td>
                            <td class="actions"><a class="icon" href="#" onClick="Confirm_Dialog('{{ env('APP_URL_ADMIN') }}/settings/languages/delete/{{ $value['id'] }}')"><i class="mdi mdi-delete"></i></a></td>
                          </tr>
                        @endforeach


                    @elseif(count($recordlist) == 0)
                    	<tr>
                    		<td colspan="5"><center>No Records</center></td>
                    	</tr>
                    @endif
                    </tbody>
                  </table>
                </div>



                </div>
          </div>
    	</div>

@endsection