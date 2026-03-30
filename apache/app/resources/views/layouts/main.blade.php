<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>{{ config('app.name', 'CYBERPHISH') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link href="{{ env('APP_URL') }}/m_assets/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/css/bootstrap-select.min.css">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/flag-icon-css/2.3.1/css/flag-icon.min.css" rel="stylesheet"/>
	<link href="https://fonts.googleapis.com/css2?family=Orbitron&display=swap" rel="stylesheet">
	<link href="https://cdn.jsdelivr.net/npm/featherlight@1.7.14/release/featherlight.min.css" type="text/css" rel="stylesheet" />
	<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/cookieconsent@3/build/cookieconsent.min.css" />
    <link href="{{ env('APP_URL') }}/m_assets/css/sb-admin-2.css" rel="stylesheet">
    <link href="{{ env('APP_URL') }}/m_assets/css/custom.css?{{ time() }}" rel="stylesheet">

    @if(\Request::is('*/learn/*/*'))

    @if(count($embeded_files) > 0)
		 <script src="https://documentcloud.adobe.com/view-sdk/main.js"></script>
			 @foreach($embeded_files as $kk => $vv)

                <script type="text/javascript">
                     document.addEventListener("adobe_dc_view_sdk.ready", function(){
                             var adobeDCView = new AdobeDC.View({clientId: "{{ env('ADOBE_EMBED_API') }}", divId: "adobe-dc-view-{{ $loop->iteration }}"});
                             adobeDCView.previewFile({
                                  content:{location: {url: "{{ env('APP_URL') }}/upload/{{ $vv['file_name'] }}"}},
                                  metaData:{fileName: "{{ $content['title'] }}"}
                             }, {embedMode: "SIZED_CONTAINER", showDownloadPDF: false, showPrintPDF: false});
                     });
                </script>

            @endforeach

	  @endif


    @endif

</head>

<body id="page-top">

    <!-- Page Wrapper -->
    <div id="wrapper">


        <!-- Content Wrapper -->
        <div id="content-wrapper" class="d-flex flex-column">

            <!-- Main Content -->
            <div id="content">

<nav class="bg-light navbar navbar-expand-lg navbar-light bg-white topbar mb-4 static-top shadow " id="myNavbar">

 <div class="position-absolute zindex1111">
 <a href="{{ env('APP_URL') }}/" class="navbar-brand"><img src="{{ env('APP_URL') }}/m_assets/img/navbar-brand.png" style="width:200px!important;"></a>
 <button class="cs1 navbar-toggler btn btn-link d-md-none mr-3" type="button" data-toggle="collapse" data-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
   <i class="fa fa-bars"></i>
 </button>
 </div>

 <div class="collapse navbar-collapse" id="mainNav">
  <ul class="navbar-nav ml-auto">

   <li class="nav-item px-4 animated--grow-in">
    <a href="{{ env('APP_URL') }}/" class="nav-link"><span class="mr-2 d-lg-inline text-gray-600 small"><span class="cs1"> {{ __('main.home') }} <span class="sr-only">(current)</span></span></span></a>
   </li>

   <div class="topbar-divider d-none d-sm-inline d-sm-block"></div>
   <div class="dropdown-divider"></div>

   <li class="nav-item px-4 dropdown dropdown-menu-right animated--grow-in no-arrow">
    <a class="nav-link dropdown-toggle" href="#" id="learningmaterialDropdown"
    role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
    <span class="d-lg-inline text-gray-600 small"><span class="cs1">{{ __('main.learning_material') }}</span></span>
    </a>
    <div class="dropdown-menu dropdown-menu-right " aria-labelledby="learningmaterialDropdown">
     <div class="d-md-flex align-items-start justify-content-start m-3 "  >

     @if(count($category_content_list) > 0)

         @foreach ($category_content_list as $cats)

			@if($loop->iteration == 1)<div>@endif

				<div class="dropdown-header cs1">{{ $cats['category_name'] }}</div>

				@if(count($cats['items']) > 0)

					@foreach ($cats['items'] as $kk => $cont)

						<a class="dropdown-item cs2" href="{{ env('APP_URL') }}/{{ Session::get('lang') }}/learn/{{ Str::slug($cont) }}/{{ $kk }}">{{ $cont }}</a>

					@endforeach

					<br>

				@endif

				@if($loop->iteration == 4)

					<div class="dropdown-header cs1"><a href="{{ env('APP_URL') }}/{{ $lang }}/simulations">{{ __('main.simulations') }}</a></div>

				@endif


 			@if($loop->iteration == 1)

 			@else
				</div><div>
			@endif


         @endforeach

     @endif

     </div>
    </div>
   </li>

   <div class="dropdown-divider"></div>
   <div class="topbar-divider d-none d-sm-block"></div>
   <li class="nav-item px-4 animated--grow-in dropdown no-arrow">
   <a class="nav-link dropdown-toggle" href="#" id="rankDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
    <span class="mr-2 d-lg-inline text-gray-600 small" style="color:#6EA6D5!important">{{ __('main.ranks') }}</span>
   </a>
   <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="rankDropdown" style="min-width:200px!important;">
      <a class="dropdown-item" href="{{ env('APP_URL') }}/{{ Session::get('lang') }}/ranks/self-evaluation">
       <i class="fas fa-trophy fa-sm fa-fw mr-2 text-gray-400"></i>
         {{ __('main.self_eval') }}
      </a>
		<div class="dropdown-divider"></div>
      <a class="dropdown-item" href="{{ env('APP_URL') }}/{{ Session::get('lang') }}/ranks/simulations">
       <i class="fas fa-trophy fa-sm fa-fw mr-2 text-gray-400"></i>
         {{ __('main.simulations') }}
      </a>
   </div>
   </li>


   @if(Auth::guard('mem')->check())

   <div class="dropdown-divider"></div>
   <div class="topbar-divider d-none d-sm-block"></div>
   <li class="nav-item px-4 animated--grow-in dropdown no-arrow">
   <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
    <span class="mr-2 d-lg-inline text-gray-600 small">{{ $user_email ?? '' }}</span>
   </a>

   <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">

     <a class="dropdown-item" href="{{ env('APP_URL') }}/{{ Session::get('lang') }}/cp/learning-material">

     <h4 class="small font-weight-bold">{{ __('main.course_progress') }} <span class="float-right">{{ $course }}%</span></h4>
     <div class="progress">
     <div class="progress-bar bg-info" role="progressbar" style="width: {{ $course }}%" aria-valuenow="{{ $course }}" aria-valuemin="0" aria-valuemax="100"></div>
     </div>

     </a>

     <div class="dropdown-divider"></div>
     <a class="dropdown-item" href="{{ env('APP_URL') }}/cp/dashboard">
     <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
           {{ __('main.my_info') }}
     </a>

     <div class="dropdown-divider"></div>
     <a class="dropdown-item" href="{{ env('APP_URL') }}/cp/badges">
     <i class="fas fa-trophy fa-sm fa-fw mr-2 text-gray-400"></i>
           {{ __('main.my_badges') }}
     </a>

      <div class="dropdown-divider"></div>
      <a class="dropdown-item" href="{{ env('APP_URL') }}/cp/logout">
       <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
        {{ __('main.logout') }}
      </a>
   </div>
   </li>

   @else

   <div class="dropdown-divider"></div>
   <div class="topbar-divider d-none d-sm-block"></div>
   <li class="nav-item px-4 animated--grow-in">
    <a href="{{ env('APP_URL') }}/login" class="nav-link"><span class="mr-2 d-lg-inline text-gray-600 small"><span class="cs1">{{ __('main.sign_in') }}</span></span></a>
   </li>

   <div class="dropdown-divider"></div>
   <div class="topbar-divider d-none d-sm-block"></div>
   <li class="nav-item px-4 animated--grow-in">
    <a href="{{ env('APP_URL') }}/register" class="nav-link"><span class="mr-2 d-lg-inline text-gray-600 small"><span class="cs1">{{ __('main.sign_up') }}</span></span></a>
   </li>

   @endif



   <div class="dropdown-divider"></div>
   <div class="topbar-divider d-none d-sm-block"></div>
   <li class="nav-item px-4 animated--grow-in">


<div class="btn-group">
   <div class="btn-group pt-3 pb-4 pb-md-0">
      <button class="btn btn-sm" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        {{ __('main.language') }}
      </button>
      <button type="button" class="btn btn-sm dropdown-toggle dropdown-toggle-split" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <span class="sr-only">Toggle Dropdown</span>
      </button>
      <div class="dropdown-menu dropdown-menu-right">
        <a class="dropdown-item" href="{{ env('APP_URL') }}/lang/en"><span class="flag-icon flag-icon-us"></span> &nbsp; English</a>
    	<a class="dropdown-item" href="{{ env('APP_URL') }}/lang/lt"><span class="flag-icon flag-icon-lt"></span> &nbsp; Lietuvių</a>
    	<a class="dropdown-item" href="{{ env('APP_URL') }}/lang/ee"><span class="flag-icon flag-icon-ee"></span> &nbsp; Eesti</a>
    	<a class="dropdown-item" href="{{ env('APP_URL') }}/lang/lv"><span class="flag-icon flag-icon-lv"></span> &nbsp; Latviski</a>
    	<a class="dropdown-item" href="{{ env('APP_URL') }}/lang/gr"><span class="flag-icon flag-icon-gr"></span> &nbsp; Ελληνικά</a>
      </div>
  </div>
</div>

   </li>


  </ul>
 </div>
</nav>


      @yield('content')


            </div>
            <!-- End of Main Content -->

            <!-- Footer -->
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>Copyright &copy; CyberPhish.eu - <a href="{{ env('APP_URL') }}/{{ Session::get('lang') }}/page/contact/6">{{ __('main.contact') }}</a></span>
                    </div>
                </div>

                <div class="container my-auto">
                    <div class="copyright text-left my-auto pt-3 col-md-8 offset-lg-2" style="text-align:justify!important;"><br>
                    <img src="{{ env('APP_URL') }}/m_assets/img/erasmus.png" style="max-width:220px;float:left">
                        <span style="line-height:20px!important;font-size:11px!important;">{{ __('main.erasmus') }}</span>
                    </div>
                </div>
            </footer>
            <!-- End of Footer -->

        </div>
        <!-- End of Content Wrapper -->

    </div>
    <!-- End of Page Wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- Bootstrap core JavaScript-->
    <script src="{{ env('APP_URL') }}/m_assets/vendor/jquery/jquery.min.js"></script>
    <script src="{{ env('APP_URL') }}/m_assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap-select@1.13.14/dist/js/bootstrap-select.min.js"></script>
    <!-- Core plugin JavaScript-->
    <script src="{{ env('APP_URL') }}/m_assets/vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="{{ env('APP_URL') }}/m_assets/js/sb-admin-2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/featherlight@1.7.14/release/featherlight.min.js" type="text/javascript" charset="utf-8"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.matchHeight/0.7.2/jquery.matchHeight-min.js" integrity="sha512-/bOVV1DV1AQXcypckRwsR9ThoCj7FqTV2/0Bm79bL3YSyLkVideFLE3MIZkq1u5t28ke1c0n31WYCOrO01dsUg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="{{ env('APP_URL') }}/m_assets/js/scripts.js"></script>

    @if(\Request::is('cp/se/*/*'))
    <script>

    $(document).ready(function(){

         $('#checkBtn').click(function() {
          checked = $("input[type=checkbox]:checked").length;

          if(!checked) {
            alert("{{ __('main.at_least_one') }}");
            return false;
          }

        });

    });

    </script>
    @endif


    <script src="https://cdn.jsdelivr.net/npm/cookieconsent@3/build/cookieconsent.min.js" data-cfasync="false"></script>
	<script>
	window.cookieconsent.initialise({
	  "palette": {
	    "popup": {
	      "background": "#71A4D7"
	    },
	    "button": {
	      "background": "#001e84"
	    }
	  },
	  "theme": "classic",
	  "content": {
	    "message": "This website uses\ncookies. Cookies are used for this website functioning. For more information, see our ",
	    "link": "Cookie Policy",
	    "href": "{{ env('APP_URL') }}/en/page/cookie-policy/19"
	  }
	});
	</script>



</body>

</html>
