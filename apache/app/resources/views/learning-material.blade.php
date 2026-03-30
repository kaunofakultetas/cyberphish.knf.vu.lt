@extends('layouts.main')

@section('content')

	<!-- Begin Page Content -->
                <div class="container-fluid">



                    <div class="container">

					 <div class="row">

  						<div class="col-lg-12">

                        <div class="card mb-4 border-left-info">
                              <div class="card-header">{{ __('main.learning_material') }}</div>
                              <div class="card-body ml-4 mt-4">


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
                        </div>




                        </div>

					</div>

                    </div>



                </div>
                <!-- /.container-fluid -->
  @endsection