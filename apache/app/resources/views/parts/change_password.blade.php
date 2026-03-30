<div class="card mb-4 border-left-info">
      <div class="card-header">{{ __('main.change_password') }}</div>
      <div class="card-body">
               {{ Form::open(array('url' => env('APP_URL')."/cp/change-pass" )) }}

                {{ Form::label('password', __('main.curr_password')) }}:<br>
                {{ Form::password('password', ['class' => 'form-control', 'required' => 'required', 'minlength'=>'12', 'maxlength'=>'50']) }}<br>

                {{ Form::label('newpass', __('main.new_password')) }}:<br>
                {{ Form::password('newpass', ['class' => 'form-control', 'required' => 'required', 'minlength'=>'12', 'maxlength'=>'50']) }}<br>

                {{ Form::label('renewpass', __('main.repeat_password')) }}:<br>
                {{ Form::password('renewpass', ['class' => 'form-control', 'required' => 'required', 'minlength'=>'12', 'maxlength'=>'50']) }}<br>

                	{{ Form::submit(__('main.change_password'), ['class' => 'btn btn-primary']) }}


                {{ Form::close() }}
      </div>
</div>