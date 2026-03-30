@extends('admin-panel.layouts.main')

@section('content')

		<div class="be-content">
            <div class="main-content container-fluid">

            @include("admin-panel.parts.notif")

                  <div class="row">
                    <div class="col-lg-12">
                    <h2 class="page-head-title"><a href="{{ env('APP_URL_ADMIN') }}/lm/categories">Content Categories</a> &#187;  {{ $name }} - Self Evaluation Questions</h2> <br>
              <div class="card card-table">
				 <div class="card-body table-responsive">
                  <table class="table" id="table1">
                    <thead>
                      <tr><th colspan="4"><a href="javascript:import_selfeval({{ $uid }})" class="btn btn-primary">Import Questions</a></th></tr>
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
                            <td> {{ $value['question'] }} </td>
                            <td class="actions"><a class="icon" href="{{ env('APP_URL_ADMIN') }}/lm/self_eval/edit/{{ $value['id'] }}"><i class="mdi mdi-cogs"></i></a></td>
                            <td class="actions"><a class="icon" href="#" onClick="Confirm_Dialog('{{ env('APP_URL_ADMIN') }}/lm/self_eval/delete/{{ $value['id'] }}')"><i class="mdi mdi-delete"></i></a></td>
                          </tr>
                        @endforeach

                        <tfoot>
                        	<tr><td colspan="5">{!! $recordlist->links()->render() !!}</td></tr>
                        </tfoot>

                    @elseif(count($recordlist) == 0)
                    	<tr>
                    		<td colspan="5"><center>No self evaluation questions for {{ $name }}</center></td>
                    	</tr>
                    @endif
                    </tbody>
                  </table>
                </div>



                </div>
          </div>
    	</div>

@endsection