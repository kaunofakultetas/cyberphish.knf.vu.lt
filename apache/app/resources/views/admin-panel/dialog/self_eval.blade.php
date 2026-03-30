    {{ Form::open(array('url' => env('APP_URL_ADMIN').'/lm/self_eval/import', 'files'=>true )) }}

    {{ Form::label('name', __('Select file')) }}<br>
    {{ Form::file("doc", ['required' => 'required']) }}<br><br>

    {{ Form::hidden('cat_id', $cat_id ?? '') }}
    <center>
    	{{ Form::submit(__('Import'), ['class' => 'btn btn-primary']) }}
    </center>

    {{ Form::close() }}
