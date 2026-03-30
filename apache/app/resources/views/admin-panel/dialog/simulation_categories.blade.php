    {{ Form::open(array('url' => env('APP_URL_ADMIN').'/dialog/simulation/cat/'.$lang_id.'/'.$scenario_id, 'files'=>false )) }}

    <center>

	@if(is_array($categories) && count($categories)>0)

	<div style="width:300px;text-align:left;">

		@foreach($categories as $k => $v)

			@if(in_array($v['id'], $in_cat))

				{{ Form::checkbox('cat[]', $v['id'], true) }} &nbsp; {{ $v['name'] }} <br><br>

			@else

				{{ Form::checkbox('cat[]', $v['id'], false) }} &nbsp; {{ $v['name'] }} <br><br>

			@endif

		@endforeach

	@endif

	</div> <br>

    {{ Form::hidden('scenario_id', $scenario_id) }}
    {{ Form::hidden('lang_id', $lang_id) }}

    	{{ Form::submit(__('Update'), ['class' => 'btn btn-primary']) }}
    </center>

    {{ Form::close() }}
