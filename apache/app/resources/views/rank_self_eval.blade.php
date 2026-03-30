@extends('layouts.main')

@section('content')

	<!-- Begin Page Content -->
                <div class="container-fluid">



                    <div class="container">

					 <div class="row">

  						<div class="col-lg-12">

  						<div class="card mb-4 border-left-info">
                          <div class="card-header">{{ __('main.self_eval_ranks') }}</div>
                          <div class="card-body">


								<div class="table-responsive">
                                  <table class="table table-sm">
                                      <tr>
                                      	<th width="100">{{ __('main.position') }}</th>
                                      	<th>{{ __('main.username') }}</th>
                                      	<th></th>
                                      	<th>{{ __('main.points') }}</th>
                                      </tr>

                                      @if(count($ranking_table)>0)

										@foreach($ranking_table as $k => $v)
    									  <tr>
    									    <td>{{ $loop->iteration }}</td>
                                          	<td>{{ $v['username'] }} </td>
											<td>
                                          	@if(count($v['badges']) > 0)

                                          		@foreach($v['badges'] as $kk => $vv)

													<a href="{{ env('APP_URL') }}/upload/badges/{{ $vv['name'] }}.png" data-featherlight="image"><img data-toggle="tooltip" data-placement="bottom" title="{{ __('main.'.$vv['name']) }}" src="{{ env('APP_URL') }}/upload/badges/{{ $vv['name'] }}.png" style="max-width:30px;"></a> &nbsp;

                                          		@endforeach

                                          	@endif

                                          	</td>
                                          	<td>{{ $v['points'] }}</td>
                                          </tr>
                                       @endforeach

                                      @else

                                      <tr>
                                      	<td colspan="5"><center>{{ __('main.nobody') }}</center></td>
                                      </tr>

                                      @endif

                                  </table>
                                </div>


							</div>
						</div>

                        </div>

					</div>

                    </div>



                </div>
                <!-- /.container-fluid -->
  @endsection