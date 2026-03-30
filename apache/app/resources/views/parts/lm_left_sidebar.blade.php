{{--
@if(Auth::guard('mem')->check())
<br>
<h4 class="small font-weight-bold">{{ __('main.course_progress') }} <span class="float-right">{{ $course }}%</span></h4>
     <div class="progress">
     <div class="progress-bar bg-info" role="progressbar" style="width: {{ $course }}%" aria-valuenow="{{ $course }}" aria-valuemin="0" aria-valuemax="100"></div>
     </div>
     <br>
@endif
--}}
 <a href="{{ env('APP_URL') }}/{{ $lang }}/simulations" class="btn btn-info btn-block mb-2">{{ __('main.simulations') }}</a>

 @if(Auth::guard('mem')->check() && $badge_all_presentations == true)

 <a href="{{ env('APP_URL') }}/{{ $lang }}/knowledge_test" class="btn btn-info btn-block mb-2">{{ __('main.knowledge_test') }}</a>

 @endif

<div class="border border-x rounded lm_sidebar">

@if(count($category_content_list) > 0)

         @foreach ($category_content_list as $cats)

				<div class="csla">{{ $cats['category_name'] }}</div>

				@if(count($cats['items']) > 0)

					@foreach ($cats['items'] as $kk => $cont)

						@if(!in_array($kk, $user_progress))
							<a class="csll" href="{{ env('APP_URL') }}/{{ Session::get('lang') }}/learn/{{ Str::slug($cont) }}/{{ $kk }}">{{ $cont }}</a>
						@else
							<a class="cslt" href="{{ env('APP_URL') }}/{{ Session::get('lang') }}/learn/{{ Str::slug($cont) }}/{{ $kk }}">{{ $cont }}</a>
						@endif

					@endforeach

					<br>

				@endif



         @endforeach

@endif


</div>
 <br><br>