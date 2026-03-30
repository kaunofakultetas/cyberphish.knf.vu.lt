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
                    <thead>
                      <tr>
                        <th>ID</th>
                        <th>Description</th>
                        <th>Started</th>
                        <th>Ended</th>
                        <th>Points</th>
                      </tr>
                    </thead>
                    <tbody>

                    @if(count($recordlist) > 0)

                        @foreach ($recordlist as $value)
                            <tr>
                            <td>{{ $value['id'] }}</td>
                            <td><a href="{{ env('APP_URL_ADMIN') }}/user/simulation/report/{{ $value['public_id'] }}">{{ $value['descr'] }}</a></td>
                            <td align="center">{{ $value['started'] }}</td>
                            <td align="center">{{ $value['ended'] }}</td>
                            <td align="center">{{ $value['points'] }}</td>
                          </tr>
                        @endforeach

                        <tfoot>
                        	<tr><td colspan="5">{!! $recordlist->links()->render() !!}</td></tr>
                        </tfoot>

                    @elseif(count($recordlist) == 0)
                    	<tr>
                    		<td colspan="5"><center>No simulations</center></td>
                    	</tr>
                    @endif
                    </tbody>
                  </table>
                </div>



                </div>
          </div>
    	</div>

@endsection