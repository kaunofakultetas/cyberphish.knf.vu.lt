@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('main.verify_email') }}</div>

                <div class="card-body">
                    @if (session('resent'))
                        <div class="alert alert-success" role="alert">
                            {{ __('main.link_sent') }}
                        </div>
                    @endif

                    {{ __('main.link_sent2') }}
                    {{ __('main.link_sent3')  }},
                    <form class="d-inline" method="POST" action="{{ route('verification.resend') }}">
                        @csrf
                        <button type="submit" class="btn btn-link p-0 m-0 align-baseline">{{ __('main.request_another_link') }}</button>.
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
