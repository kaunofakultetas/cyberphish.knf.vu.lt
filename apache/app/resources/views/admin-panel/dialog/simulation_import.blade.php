    {{ Form::open(array('url' => env('APP_URL_ADMIN').'/admin_simulations/import', 'files'=>true )) }}

    {{ Form::label('name', __('Select file')) }}<br>
    {{ Form::file("doc", ['required' => 'required']) }}<br><br>

    {{ Form::hidden('lang_id', $lang_id ?? '') }}
    <center>
    	{{ Form::submit(__('Import'), ['class' => 'btn btn-primary']) }}
    </center>

    {{ Form::close() }}
