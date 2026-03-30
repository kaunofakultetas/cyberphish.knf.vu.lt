@extends('admin-panel.layouts.main')

@section('content')

		<div class="be-content">
            <div class="main-content container-fluid">

            @include("admin-panel.parts.notif")

                  <div class="row">
                    <div class="col-lg-12">
                    <h2 class="page-head-title">{{ $name }} - Additional files</h2> <br>
              <div class="card card-table">
				 <div class="card-body table-responsive">
                  <table class="table" id="table1">
                    <thead>
                      <tr><th colspan="4"><a href="#" onClick="lm_files_form('new', {{ $uid }})" class="btn btn-primary">Add file</a></th></tr>
                      <tr>
                        <th>Name</th>
                        <th width="90" class="actions"></th>
                        <th width="90" class="actions"></th>
                      </tr>
                    </thead>
                    <tbody>

                    @if(count($recordlist) > 0)

                        @foreach ($recordlist as $value)
                            <tr>
                            <td><a href="{{ env('APP_URL') }}/upload/{{ $value['file_name'] }}" target="_blank">{{ $value['name'] }}</a></td>
							<td class="actions"><a class="icon" href="#" onClick="lm_files_form('edit', {{ $value['id'] }})"><i class="mdi mdi-settings"></i></a></td>
                            <td class="actions"><a class="icon" href="#" onClick="Confirm_Dialog('{{ env('APP_URL_ADMIN') }}/lm/files/delete/{{ $value['id'] }}')"><i class="mdi mdi-delete"></i></a></td>
                          </tr>
                        @endforeach

                        <tfoot>
                        	<tr><td colspan="5">{!! $recordlist->links()->render() !!}</td></tr>
                        </tfoot>

                    @elseif(count($recordlist) == 0)
                    	<tr>
                    		<td colspan="5"><center>No additional files for {{ $name }}</center></td>
                    	</tr>
                    @endif
                    </tbody>
                  </table>
                </div>



                </div>
          </div>
    	</div>

@endsection