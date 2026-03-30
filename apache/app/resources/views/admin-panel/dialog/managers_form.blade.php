@if($main == 1)

{{ Form::open(array('url' => $url )) }}

    {{ Form::label('email', __('Email')) }}<br>
    {{ Form::email('email', $email ?? '', ['class' => 'form-control', 'required' => 'required']) }}<br>

    {{ Form::label('pass', __('Password')) }}<br>
    {{ Form::password('pass', ['class' => 'form-control', 'required' => 'required', 'minlength' => '6', 'maxlength' => '50']) }}<br>

    {{ Form::label('repass', __('Repeat password')) }}<br>
    {{ Form::password('repass', ['class' => 'form-control', 'required' => 'required', 'minlength' => '6', 'maxlength' => '50']) }}<br>

	{{ Form::label('country', __('Country')) }}<br>
    {{ Form::select('country', [1 => 'Lithuania', 2 => 'Estonia', 3 => 'Greece', 4 => 'Latvia', 5 => 'Malta', 0 => 'Other'], '', ['class' => 'form-control', 'required'=>'required', 'placeholder'=>'Country']) }}
    <br>

    {{ Form::hidden('uid', $uid ?? '') }}

    <center>
    	{{ Form::submit(__('Submit'), ['class' => 'btn btn-primary']) }}
    </center>

{{ Form::close() }}

@endif