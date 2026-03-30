@extends('layouts.main')

@section('content')

<div class="jumbotron p-0 shadow d-none d-md-block" style="margin-top:-23px!important;">
  		<img src="{{ env('APP_URL') }}/m_assets/img/header.jpg" class="img-fluid" alt="Cyberphish">
	</div>

	<!-- Begin Page Content -->
                <div class="container-fluid">




                    <div class="row">

  						<div class="col-lg-12 p-0">

<section class="jumbotron text-center bg-white">
        <div class="container">
          <h1 class="jumbotron-heading cs_heading">{{ __('main.about_cyberphish') }}</h1><br>
          <p class="lead text-muted">{{ $about[$lang_id]['descr'] }}</p>
          <p>
            <a href="{{ $about[$lang_id]['link'] }}" class="btn my-3 cs3 col-lg-2 col-md-6">{{ __('main.more_about_us') }}</a>
          </p>
        </div>
      </section>


                        </div>

                    </div>


					{{--
                    <div class="container">

                    <center>
                    <h1 class="heading cs_heading">{{ __('main.project_news') }}</h1><br>
                    </center>

                    <div class="row">

                    @if(count($latest_news) > 0)

                        @foreach ($latest_news as $value)

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

            	@endif


                    </div>
                     <div class="row">
                    <div class="col-md-12 text-center">
                    <a href="{{ env('APP_URL') }}/{{ Session::get('lang') }}/news" class="btn my-3 cs3 col-lg-2 col-md-6">{{ __('main.more_news') }}</a> <br><br><br>
                    </div>
                    </div>


                </div>
                <!-- /.container-fluid -->
                --}}



				<section class="jumbotron text-center bg-white">

				<div class="row">
                  <div class="col-lg-12">
                  <center>
                    <h1 class="heading cs_heading">{{ __('main.partners') }}</h1><br><br>
                    </center>
                   </div>
                 </div>
                  <div class="row">



  						<div class="col-lg-2">

 							<center><img src="{{ env('APP_URL') }}/assets/img/vuknf.png" alt="vuknf"></center>

                        </div>

                        <div class="col-lg-2">

 							<center><img src="{{ env('APP_URL') }}/assets/img/tartu.png" alt="tartu"></center>

                        </div>

                        <div class="col-lg-2">

 							<center><img src="{{ env('APP_URL') }}/assets/img/mecb.png" alt="mecb"></center>

                        </div>

                        <div class="col-lg-2">

 							<center><img src="{{ env('APP_URL') }}/assets/img/ecdl.png" alt="ecdl"></center>

                        </div>

                        <div class="col-lg-2">

 							<center><img src="{{ env('APP_URL') }}/assets/img/dorea.png" alt="dorea"></center>

                        </div>

                        <div class="col-lg-2">

 							<center><img src="{{ env('APP_URL') }}/assets/img/altacom.png" alt="altacom"></center>

                        </div>

                    </div>
                 </section>


  @endsection