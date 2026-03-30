<div class="card mb-4 border-left-info">
      <div class="card-header">{{ __('main.download_certificate') }}</div>
      <div class="card-body">
               {{ Form::open(array('url' => env('APP_URL')."/cp/change-fullname" )) }}

                {{ Form::label('fullname', __('main.fullname')) }}:<br>
                {{ Form::text('fullname', $fullname ?? '', ['class' => 'form-control', 'required' => 'required', 'minlength'=>6, 'maxlength'=>100]) }}<br>

               <center> {{ Form::submit(__('main.change_fullname'), ['class' => 'btn btn-primary']) }}

               @if($fullname != '' && $fullname != NULL)

               		<br><br>
               		<a href="{{ env('APP_URL') }}/cp/{{ $lang }}/certificate" target="_blank" class="btn btn-secondary">{{ __('main.download_certificate') }}</a>

               @endif

               </center>

                {{ Form::close() }}
      </div>
</div>