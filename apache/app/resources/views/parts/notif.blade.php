@if(Auth::guard('mem')->check())

     @if($badge_finished_course == true && $country_id > 0)

         <div class="alert alert-info" role="alert">
                   <a href="{{ $pq_link }}" target="_blank"><u>{{ __('main.pq_questionare') }}</u></a>
         </div>

     @endif

@endif 
                        <div class="row">

                      @if($errors->any())
                      <div class="alert alert-danger alert-dismissible col-lg-12" role="alert">
                        <button class="close" type="button" data-dismiss="alert" aria-label="Close"><span class="mdi mdi-close" aria-hidden="true"></span></button>
                        <div class="icon"> <span class="mdi mdi-close-circle-o"></span></div>
                        <div class="message">
                         <ul>
                        	@foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        </div>
                      </div>
               		 @endif

               		 @if(session()->has('success'))
                      <div class="alert alert-success alert-dismissible col-lg-12" role="alert">
                        <button class="close" type="button" data-dismiss="alert" aria-label="Close"><span class="mdi mdi-close" aria-hidden="true"></span></button>
                        <div class="icon"> <span class="mdi mdi-close-circle-o"></span></div>
                        <div class="message">
                          	{!! session()->get('success') !!}
                        </div>
                      </div>
               		 @endif

                </div>
                
                @if(Auth::guard('mem')->check())
                <div class="row">
                 
                 	<div class="col-lg-12">
                 	
                 	<h4 class="small font-weight-bold">{{ __('main.course_progress') }} <span class="float-right">{{ $course }}%</span></h4>
                     <div class="progress">
                     <div class="progress-bar bg-info" role="progressbar" style="width: {{ $course }}%" aria-valuenow="{{ $course }}" aria-valuemin="0" aria-valuemax="100"></div>
                     </div>
                     <br>
                 	
                 	</div>
                  
                 </div>
                 @endif 