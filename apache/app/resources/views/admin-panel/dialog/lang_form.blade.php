    {{ Form::open(array('url' => $url )) }}

    {{ Form::label('name', __('Language name')) }}<br>
    {{ Form::text('name', $name ?? '', ['class' => 'form-control', 'required' => 'required']) }}<br><br>
    {{ Form::label('locale', __('Locale')) }}<br>
    {{ Form::text('locale', $locale ?? '', ['class' => 'form-control', 'required' => 'required']) }}<br><br>
    {{ Form::hidden('uid', $uid ?? '') }}
    <center>
    	{{ Form::submit(__('Submit'), ['class' => 'btn btn-primary']) }}
    </center>

    {{ Form::close() }}
