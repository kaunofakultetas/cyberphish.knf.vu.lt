@extends('admin-panel.layouts.login')

@section('content')
<div class="main-content container-fluid">
          <div class="splash-container">
            <div class="card card-border-color card-border-color-primary">
                <div class="card-header"> <img class="logo-img" src="{{ route('home') }}/assets/img/logo-xx.png" alt="logo">  <span class="splash-description">ADMIN PANEL</span></div>
              <div class="card-body">

               @if($errors->any())
                   @foreach ($errors->all() as $error)
                       <div class="alert alert-danger alert-dismissible" role="alert">
                         <button class="close" type="button" data-dismiss="alert" aria-label="Close"><span class="mdi mdi-close" aria-hidden="true"></span></button>
                            <div class="icon"> <span class="mdi mdi-close-circle-o"></span></div>
                            <div class="message">{{ $error }}</div>
                       </div>
                   @endforeach
               @endif

                <form method="POST" action="{{ route('admin_login') }}">
                        @csrf
                  <div class="login-form">
                    <div class="form-group">
                      <input id="email" type="email" placeholder="{{ __('Email') }}" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="off" autofocus>

                    	@error('email')
                           <span class="invalid-feedback" role="alert">
                               <strong>{{ $message }}</strong>
                           </span>
                        @enderror

                    </div>
                    <div class="form-group">
                      <input id="password" type="password" placeholder="{{ __('Password') }}" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password">

                      @error('password')
                         <span class="invalid-feedback" role="alert">
                               <strong>{{ $message }}</strong>
                         </span>
                      @enderror
                    </div>

                    <div class="form-group row login-submit">
                      <div class="col-12">
                      	<button type="submit" class="btn btn-primary btn-xl" data-dismiss="modal">
                                    {{ __('Login') }}
                        </button>
                    </div>
                  </div>
              </form>
          </div>
      </div>
   </div>
</div>
@endsection
