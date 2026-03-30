@extends('admin-panel.layouts.main')

@section('content')

		<div class="be-content">
            <div class="main-content container-fluid">

            @include("admin-panel.parts.notif")

                  <div class="row">
                    <div class="col-lg-12">
                    <h2 class="page-head-title">Settings - Other Settings</h2> <br>


            <div class="col-12 col-lg-12">
              <div class="tab-container">
                <ul class="nav nav-tabs nav-tabs-classic" role="tablist" id="TTAB">

                  <li class="nav-item"><a class="nav-link active" href="#a1" data-toggle="tab" role="tab">Main page</a></li>
                  <li class="nav-item"><a class="nav-link" href="#a2" data-toggle="tab" role="tab">Self Evaluation</a></li>
                  <li class="nav-item"><a class="nav-link" href="#a3" data-toggle="tab" role="tab">Knowledge Test</a></li>
                  <li class="nav-item"><a class="nav-link" href="#a4" data-toggle="tab" role="tab">Download Simulations</a></li>

                </ul>
                <div class="tab-content">

                       		<div class="tab-pane active" id="a1" role="tabpanel">

								<br> <br>
								{{ Form::open(array('url' => env('APP_URL_ADMIN').'/settings/about_us_save' )) }}

								@if(count($langlist) > 0)

                        			@foreach ($langlist as $key1 => $value1)
										<br>
										{{ Form::label('about_descr', __('Home page About Description ('.$value1.')')) }}<br>
    									{{ Form::text("about[".$key1."][descr]", $about[$key1]['descr'] ?? '', ['class' => 'form-control', 'required' => 'required']) }}<br><br>

    									{{ Form::label('about_link', __('Home page About Link (with http://) ('.$value1.')')) }}<br>
    									{{ Form::text("about[".$key1."][link]", $about[$key1]['link'] ?? '', ['class' => 'form-control', 'required' => 'required']) }}<br>

    									<hr>

                        			@endforeach

                        		@endif

                        		{{ Form::submit(__('Submit'), ['class' => 'btn btn-primary']) }}<br><br>

								{{ Form::close() }}
								<br> <br>


                            </div>
                            <div class="tab-pane" id="a2" role="tabpanel">
								<br> <br>
								{{ Form::open(array('url' => env('APP_URL_ADMIN').'/settings/self_eval_save' )) }}

								{{ Form::label('self_eval', __('Self evaluation questions:')) }}<br>
    							{{ Form::number('self_eval', $self_eval ?? 0, ['class' => 'form-control', 'required' => 'required']) }}<br><br>

								{{ Form::submit(__('Submit'), ['class' => 'btn btn-primary']) }}<br><br>

								{{ Form::close() }}
								<br> <br>
                            </div>
                            <div class="tab-pane" id="a3" role="tabpanel">
								<br> <br>
								{{ Form::open(array('url' => env('APP_URL_ADMIN').'/settings/knowledge_test_settings_save' )) }}

								@if(count($langlist) > 0)



                        			@foreach ($langlist as $key1 => $value1)

                        			<div class="row">

									 <div class="col-md-12">

									 	<b>{{ Form::label('d'.$key1, $value1) }}</b><br><br>

									 </div>

									 	@foreach($categories[$key1] as $kk => $vv)

									 		<div class="col-md-3">
									 		{{ Form::label('d'.$kk, $vv['name']) }}<br>
									 		{{ Form::number("knowledge[$key1][".$vv['id']."]", $cat_values[$key1][$vv['id']] ?? 0, ['class' => 'form-control']) }}

									 		</div>

									 	@endforeach

									 	<div class="col-md-12"> <br><hr> </div>



									 </div>

                        			@endforeach



                        		@endif

                        		<div class="col-md-12"><br><br>
                        		{{ Form::submit(__('Submit'), ['class' => 'btn btn-primary']) }}<br><br></div>


								{{ Form::close() }}
								<br> <br>
                            </div>
                            
                            
                            
                            <div class="tab-pane" id="a4" role="tabpanel">
								<br> <br> 
                        			<div class="row">
  
                        		<div class="col-md-12"><br><br><center> 
                        		
                        			<a href="{{ env('APP_URL_ADMIN') }}/settings/simulations_download_pdf" class="btn btn-primary" target="_blank">Download (PDF)</a>
                        		
                        		</center>
                        	 
								<br> <br>
                            </div>
                            
                            
                            



                </div>
              </div>
			</div>


          </div>
    	</div>

@endsection