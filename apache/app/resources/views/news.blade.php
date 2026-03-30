@extends('layouts.main')

@section('content')

	<!-- Begin Page Content -->
                <div class="container-fluid">



                    <div class="container">

					 <div class="row">
  						<div class="col-lg-12">

  						<h2 class="heading cs_heading">{{ __('main.news') }}</h2>

  						<br><br>

						<div class="row rowzz">


						@if(count($recordlist) > 0)

                        @foreach ($recordlist as $value)

                           <div class="col-md-4">
                              <div class="card mb-4 box-shadow column">
                                <img class="card-img-top" src="{{ env('APP_URL') }}/upload/{{ $value['feat_img'] }}" alt="{{ $value['title'] }}">
                                <div class="card-body">
                                  <p class="card-text">{{ $value['title'] }}</p>
                                  <div class="d-flex justify-content-between align-items-center">
                                    <div class="btn-group">
                                      <a href="{{ env('APP_URL') }}/{{ Session::get('lang') }}/news/{{ $value['alias'] }}/{{ $value['id'] }}" class="btn btn-sm btn-outline-secondary">{{ __('main.read') }}</a>
                                    </div>
                                    <small class="text-muted">{{ date("Y-m-d H:i", strtotime($value['created'])) }}</small>
                                  </div>
                                </div>
                              </div>
                            </div>

                        @endforeach

                        <div class="col-md-12">
                        	  {!! $recordlist->links()->render() !!}
                        </div>

                    @elseif(count($recordlist) == 0)
                    <div class="col-md-12">
                   				<br><br>
                    		 <center>{{ __('main.no_news') }}</center>
                     </div>
                    @endif



						</div>


                        </div>

					</div>

                    </div>



                </div>
                <!-- /.container-fluid -->
  @endsection