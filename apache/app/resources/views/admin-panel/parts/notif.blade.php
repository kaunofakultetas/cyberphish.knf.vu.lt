<div class="row">

                      @if($errors->any())
                      <div class="alert alert-danger alert-dismissible col-lg-12" role="alert">
                        <button class="close" type="button" data-dismiss="alert" aria-label="Close"><span class="mdi mdi-close" aria-hidden="true"></span></button>
                        <div class="icon"> <span class="mdi mdi-close-circle-o"></span></div>
                        <div class="message">
                         <ul>
                        	@foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        </div>
                      </div>
               		 @endif

               		 @if(session()->has('success'))
                      <div class="alert alert-success alert-dismissible col-lg-12" role="alert">
                        <button class="close" type="button" data-dismiss="alert" aria-label="Close"><span class="mdi mdi-close" aria-hidden="true"></span></button>
                        <div class="icon"> <span class="mdi mdi-close-circle-o"></span></div>
                        <div class="message">
                          	{{ session()->get('success') }}
                        </div>
                      </div>
               		 @endif

                </div>