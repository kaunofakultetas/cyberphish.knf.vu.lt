@extends('layouts.login')

@section('content')

        <div class="row justify-content-center">

            <div class="col-xl-10 col-lg-12 col-md-9">

                <div class="card o-hidden border-0 shadow-lg my-5">
                    <div class="card-body p-0">

                        <div class="row">
                            <div class="col-lg-6 d-none d-lg-block bg-login-image"></div>
                            <div class="col-lg-6">


                                <div class="p-5">
                                    <div class="text-center">
                                        <h1 class="h4 text-gray-900 mb-4">{{ __('main.welcome_back') }}</h1> <br>
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

									{{ Form::open(array('url' => route('login'), 'class'=>'user' )) }}

                                        <div class="form-group">
											{{ Form::email('email', '', ['class' => 'form-control form-control-user', 'placeholder'=>__('main.enter_email')]) }}
                                        </div>
                                        <div class="form-group">
                                        	{{ Form::password('password', ['class' => 'form-control form-control-user', 'minlength'=>'12', 'maxlength'=>'50', 'required' => 'required', 'placeholder'=>__('main.passw')]) }}
                                        </div>

										<br>

										{{ Form::submit(__('main.login'), ['class' => 'btn btn-primary btn-user btn-block']) }}

                                    {{ Form::close() }}
                                    <hr>
                                    <div class="text-center"> <br>
                                        <a class="small" href="{{ env('APP_URL') }}/forgot-password">{{ __('main.forgot_password') }}</a> <br>
                                    </div>
                                    <div class="text-center">
                                        <a class="small" href="{{ env('APP_URL') }}/register">{{ __('main.create_acc') }}</a>
                                    </div>
                                </div>


                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>

@endsection
