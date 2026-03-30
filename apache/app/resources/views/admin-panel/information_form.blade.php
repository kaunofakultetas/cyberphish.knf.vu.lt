@extends('admin-panel.layouts.main')

@section('content')

		<div class="be-content">
            <div class="main-content container-fluid">

				@include('admin-panel.parts.notif')

                 <div class="row">

                 	<div class="col-md-12">

						<div class="user-info-list card">
                  <div class="card-header card-header-divider" style="text-transform:uppercase;">
					<a href="{{ env('APP_URL') }}/admin-panel/information">Information</a> &#187; Information form </div>
                  <div class="card-body">

                  {{ Form::open(array('url' => $url )) }}
                    <table class="no-border skills table-striped">
                      <tbody class="no-border-x no-border-y">
                         <tr>
                          <td width="200" class="p-5">{{ Form::label('title', __('Title:')) }} </td>
                          <td class="p-5" colspan="3"> {{ Form::text('title', $title ?? '', ['class' => 'form-control', 'required'=>'required']) }} </td>
                        </tr>
						<tr>
                          <td width="200" class="p-5">{{ Form::label('content', __('Content:')) }} </td>
                          <td class="p-5" colspan="3"> {{ Form::textarea('content', $content ?? '', ['class' => 'form-control', 'required'=>'required', 'id'=>'summernote']) }} </td>
                        </tr>
						<tr>
                          <td width="200" class="p-5">{{ Form::label('lang_id', __('Language:')) }} </td>
                          <td class="p-5" colspan="3">{{ Form::select("lang_id", $langlist ?? array(), $lang_id ?? 0, ['class'=>'form-control', 'placeholder'=>'- Select Language -', 'required'=>'required']) }}</td>
                        </tr>
                        <tr>
                          <td colspan="4">
								{{ Form::submit(__('Submit'), ['class' => 'btn btn-primary btn-lg']) }}
						  </td>
                        </tr>


                      </tbody>
                    </table>
                    {{ Form::hidden('uid', $uid ?? '') }}
                    {{ Form::close() }}


                  </div>
                </div>

                 	</div>

                 </div>

          </div>
    	</div>

@endsection