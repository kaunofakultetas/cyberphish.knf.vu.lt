@extends('layouts.register')

@section('content')

<div class="card o-hidden border-0 shadow-lg my-5">
            <div class="card-body p-0">
                <!-- Nested Row within Card Body -->
                <div class="row">
                    <div class="col-lg-5 d-none d-lg-block bg-register-image"></div>
                    <div class="col-lg-7">
                        <div class="p-5">
                            <div class="text-center">
                                <h1 class="h4 text-gray-900 mb-4">{{ __('main.create_acc') }}</h1>
                            </div>

                            @if($errors->any())
                               @foreach ($errors->all() as $error)
                                   <div class="alert alert-danger alert-dismissible" role="alert">
                                     <button class="close" type="button" data-dismiss="alert" aria-label="Close"><span class="mdi mdi-close" aria-hidden="true"></span></button>
                                        <div class="icon"> <span class="mdi mdi-close-circle-o"></span></div>
                                        <div class="message">{{ $error }}</div>
                                   </div>
                               @endforeach
                           @endif

                           @if(session()->has('success'))

                           <div class="alert alert-contrast alert-success alert-dismissible" role="alert">
                                <div class="icon"><span class="mdi mdi-check"></span></div>
                                <div class="message">
                                  <button class="close" type="button" data-dismiss="alert" aria-label="Close"><span class="mdi mdi-close" aria-hidden="true"></span></button>
                                  {{ session()->get('success') }}
                                </div>
                           </div>

                           @endif

                            {{ Form::open(array('url' => route('register'), 'class'=>'user' )) }}
                                <div class="form-group">
                                    {{ Form::email('email', '', ['class' => 'form-control form-control-user', 'placeholder'=>__('main.enter_email'), 'minlength'=>'6', 'maxlength'=>'254', 'required' => 'required']) }}
                                </div>
                                <div class="form-group row">
                                    <div class="col-sm-12">
                                        {{ Form::password('password', ['class' => 'form-control form-control-user', 'required' => 'required', 'minlength'=>'12', 'maxlength'=>'50', 'placeholder'=>__('main.passw')]) }}
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <div class="col-sm-12">
                                        {{ Form::password('password2', ['class' => 'form-control form-control-user', 'required' => 'required', 'minlength'=>'12', 'maxlength'=>'50', 'placeholder'=>__('main.repassw')]) }}
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <div class="col-sm-12">
										{{ Form::select('country', [1 => 'Lithuania', 2 => 'Estonia', 3 => 'Greece / Cyprus', 4 => 'Latvia', 5 => 'Malta', 0 => 'Other'], '', ['class' => 'form-control', 'required'=>'required', 'placeholder'=>__('main.country'), 'style'=>'border-radius:30px!important;font-size:13px;']) }}
                                    </div>
                                </div>

                                <div class="form-group{{ $errors->has('g-recaptcha-response') ? ' has-error' : '' }} row">
                                        <div class="col-md-12"><center>
                                            {!! app('captcha')->display() !!}
                                        </center></div>
                                </div>

                                <div class="form-group row">
                                    <div class="col-sm-12">
										{{ Form::checkbox('terms', 1, 0, ['required'=>'required']) }} <a href="{{ env('APP_URL') }}/privacy_policy.pdf" target="_blank">{{ __('main.agree_terms') }}</a>
                                    </div>
                                </div>



                                {{ Form::submit(__('main.register'), ['class' => 'btn btn-primary btn-user btn-block']) }}


                            {{ Form::close() }}
                            <hr>
                            <div class="text-center">
                                <a class="small" href="{{ env('APP_URL') }}/forgot-password">{{ __('main.forgot_password') }}</a>
                            </div>
                            <div class="text-center">
                                <a class="small" href="{{ env('APP_URL') }}/login">{{ __('main.already_acc_login') }}</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
@endsection