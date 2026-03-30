@extends('layouts.login')

@section('content')


        <div class="row justify-content-center">

            <div class="col-xl-10 col-lg-12 col-md-9">

                <div class="card o-hidden border-0 shadow-lg my-5">
                    <div class="card-body p-0">
                        <!-- Nested Row within Card Body -->
                        <div class="row">
                            <div class="col-lg-6 d-none d-lg-block bg-password-image"></div>
                            <div class="col-lg-6">
                                <div class="p-5">
                                    <div class="text-center">
                                        <h1 class="h4 text-gray-900 mb-2">{{ __('main.forgot_password') }}</h1>
                                        <p class="mb-4">{{ __('main.forgot_password_descr') }}</p>
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


                                    {{ Form::open(array('url' => route('forgot-password'), 'class'=>'user' )) }}
                                        <div class="form-group">
                                            {{ Form::email('email', '', ['class' => 'form-control form-control-user', 'placeholder'=>__('main.enter_email')]) }}
                                        </div>

                                        <div class="form-group{{ $errors->has('g-recaptcha-response') ? ' has-error' : '' }} row"><div class="col-12">
                                        	<center> {!! app('captcha')->display() !!} </center>
                                        </div></div>

                                        {{ Form::submit(__('main.reset_pass'), ['class' => 'btn btn-primary btn-user btn-block']) }}

                                    </form>
                                    <hr>
                                    <div class="text-center">
                                        <a class="small" href="{{ env('APP_URL') }}/register">{{ __('main.create_acc') }}</a>
                                    </div>
                                    <div class="text-center">
                                        <a class="small" href="{{ env('APP_URL') }}/login">{{ __('main.already_acc_login') }}</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>



@endsection