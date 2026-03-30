@extends('admin-panel.layouts.main')

@section('content')

		<div class="be-content">
            <div class="main-content container-fluid">

            @include("admin-panel.parts.notif")

                  <div class="row">
                    <div class="col-lg-12">
                    <h2 class="page-head-title">Learning Material - Content</h2> <br>


            <div class="col-12 col-lg-12">
              <div class="tab-container">
                <ul class="nav nav-tabs nav-tabs-classic" role="tablist" id="TTAB">
                  @if(count($langlist) > 0)

                        @foreach ($langlist as $key => $value)
                  <li class="nav-item"><a class="nav-link {{ $loop->iteration === 1 ? 'active' : '' }}" href="#a{{ $key }}" data-toggle="tab" role="tab">{{ $value }}</a></li>
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
                                        <th><a href="{{ env('APP_URL_ADMIN') }}/lm/content/new/{{ $key1 }}" class="btn btn-primary">Create Content</a></th>
                                        <th>Hours</th>
                                        <th>Category</th>
                                        <th width="200">Additional files</th>
                                        <th width="90" class="actions"></th>
                                        <th width="90" class="actions"></th>
                                      </tr>
                                    </thead>
                                    <tbody>

                                    @if(count($recordlist[$key1] ?? array()) > 0)

                                        @foreach ($recordlist[$key1] as $value)
                                            <tr>
                                            <td>{{ $value['title'] }}</td>
                                            <td>{{ $value['hours'] }}</td>
                                            <td>{{ $value['category_name'] }}</td>
                                            <td><center> <a href="{{ env('APP_URL_ADMIN') }}/lm/files/{{ $value['id'] }}"><i class="mdi mdi-file-document"></i> Additional files ({{ $value['additional_files'] }})</a> </center></td>
                                            <td class="actions"><a class="icon" href="{{ env('APP_URL_ADMIN') }}/lm/content/edit/{{ $value['id'] }}"><i class="mdi mdi-settings"></i></a></td>
                                            <td class="actions"><a class="icon" href="#" onClick="Confirm_Dialog('{{ env('APP_URL_ADMIN') }}/lm/content_delete/{{ $value['id'] }}')"><i class="mdi mdi-delete"></i></a></td>
                                          </tr>
                                        @endforeach

                                    @elseif(count($recordlist[$key1] ?? array()) == 0)
                                    	<tr>
                                    		<td colspan="5"><center>No Records</center></td>
                                    	</tr>
                                    @endif
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

@endsection