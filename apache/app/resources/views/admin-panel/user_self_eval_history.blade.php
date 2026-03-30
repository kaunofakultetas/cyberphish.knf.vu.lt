@extends('admin-panel.layouts.main')

@section('content')

		<div class="be-content">
            <div class="main-content container-fluid">

            @include("admin-panel.parts.notif")

                  <div class="row">
                    <div class="col-lg-12">
                    <h2 class="page-head-title">Self Evaluation History - {{ $email }} - {{ $language }}</h2> <br>
              <div class="card card-table">
				 <div class="card-body table-responsive">
                  <table class="table" id="table1">
                    <thead>
                      <tr>
                        <th>Category</th>
                        <th>Correct answers</th>
                        <th>Started</th>
                        <th>Ended</th>
                        <th>Points</th>
                      </tr>
                    </thead>
                    <tbody>

                    @if(count($recordlist) > 0)

                        @foreach ($recordlist as $value)
                            <tr>
                            <td>{{ $value['category_name'] }}</td>
                            <td>{{ $value['correct'] }}/5</td>
                            <td>{{ $value['started'] }}</td>
                            <td>{{ $value['ended'] }}</td>
                            <td>{{ $value['points'] }}</td>
                          </tr>
                        @endforeach

                        <tfoot>
                        	<tr><td colspan="5">{!! $recordlist->links()->render() !!}</td></tr>
                        </tfoot>

                    @elseif(count($recordlist) == 0)
                    	<tr>
                    		<td colspan="5"><center>No self evaluation tests</center></td>
                    	</tr>
                    @endif
                    </tbody>
                  </table>
                </div>



                </div>
          </div>
    	</div>

@endsection