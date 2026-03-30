{{ Form::open(array('url' => env('APP_URL')."/admin-panel/change-pass" )) }}

{{ Form::label('password', __('Current password')) }}<br>
{{ Form::password('password', ['class' => 'form-control', 'required' => 'required']) }}<br><br>

{{ Form::label('newpass', __('New password')) }}<br>
{{ Form::password('newpass', ['class' => 'form-control', 'required' => 'required']) }}<br><br>

{{ Form::label('renewpass', __('Repeat new password')) }}<br>
{{ Form::password('renewpass', ['class' => 'form-control', 'required' => 'required']) }}<br><br>
<center>
	{{ Form::submit(__('Change'), ['class' => 'btn btn-primary']) }}
</center>

{{ Form::close() }}