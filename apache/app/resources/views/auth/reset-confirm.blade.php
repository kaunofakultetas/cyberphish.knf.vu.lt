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
                                        <h1 class="h4 text-gray-900 mb-2">{{ __('main.reset_pass') }}</h1>
                                        <p class="mb-4">{{ __('main.reset_pass_descr') }}</p>
                                    </div>

                                    {{ Form::open(array('url' => env('APP_URL').'/reset/'.$reset_token, 'class'=>'user' )) }}

                                        {{ Form::submit(__('main.reset_pass'), ['class' => 'btn btn-primary btn-user btn-block']) }}

                                    {{ Form::close() }}
                                    <hr>
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
