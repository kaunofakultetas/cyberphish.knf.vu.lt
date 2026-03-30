<div class="card mb-4 border-left-info">
      <div class="card-header">{{ __('main.change_username') }}</div>
      <div class="card-body">
               {{ Form::open(array('url' => env('APP_URL')."/cp/change-username" )) }}

                {{ Form::label('username', __('main.change_username')) }}:<br>
                {{ Form::text('username', $username ?? '', ['class' => 'form-control', 'required' => 'required', 'minlength'=>3, 'maxlength'=>20]) }}<br>

                {{ Form::submit(__('main.change_username2'), ['class' => 'btn btn-primary']) }}

                {{ Form::close() }}
      </div>
</div>