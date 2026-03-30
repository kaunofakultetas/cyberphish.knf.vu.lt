    {{ Form::open(array('url' => $url, 'files'=>true )) }}

    {{ Form::label('name', __('File name')) }}<br>
    {{ Form::text('name', $name ?? '', ['class' => 'form-control', 'required' => 'required']) }}<br><br>

	@if($type =='new')
    {{ Form::label('name', __('Select file')) }}<br>
    {{ Form::file("doc", ['required' => 'required']) }}<br><br>
    @endif

    {{ Form::checkbox('embed', 1, $embed ?? 0) }} Embed

    {{ Form::hidden('uid', $uid ?? '') }}
    <center>
    	{{ Form::submit(__('Submit'), ['class' => 'btn btn-primary']) }}
    </center>

    {{ Form::close() }}
