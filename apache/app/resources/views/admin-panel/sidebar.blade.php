<div class="be-left-sidebar">
        <div class="left-sidebar-wrapper"><a class="left-sidebar-toggle" href="#">MENU</a>
          <div class="left-sidebar-spacer">
            <div class="left-sidebar-scroll">
              <div class="left-sidebar-content">
                <ul class="sidebar-elements">
                  <li class="divider">{{ __('MENU') }}</li>
                  	 <li><a href="{{ env('APP_URL') }}/admin-panel/dashboard"><i class="icon mdi mdi-home"></i><span>Home</span></a> </li>

                  	 @if($main == 1)
                  	 <li><a href="{{ env('APP_URL') }}/admin-panel/users"><i class="icon mdi mdi-account-multiple"></i><span>Users</span></a> </li>
                  	 <li class="parent"><a href="#"><i class="icon mdi mdi-school"></i><span>Learning Material</span></a>
    					<ul class="sub-menu">
                          <li><a href="{{ env('APP_URL') }}/admin-panel/lm/categories">Categories</a> </li>
                          <li><a href="{{ env('APP_URL') }}/admin-panel/lm/content">Content</a> </li>
                        </ul>
                     </li>

                     <li class="parent"><a href="#"><i class="icon mdi mdi-checkbox-multiple-blank-circle"></i><span>Simulations</span></a>
    					<ul class="sub-menu">
    					  <li><a href="{{ env('APP_URL') }}/admin-panel/admin_simulations">Simulations</a> </li>
                          <li><a href="{{ env('APP_URL') }}/admin-panel/admin_simulations/categories">Categories</a> </li>
                          <li><a href="{{ env('APP_URL') }}/admin-panel/admin_simulations/attributes">Attributes</a> </li>
                        </ul>
                     </li>

                  	 <li><a href="{{ env('APP_URL') }}/admin-panel/news"><i class="icon mdi mdi-newspaper-variant-multiple-outline"></i><span>News</span></a> </li>
                  	 <li><a href="{{ env('APP_URL') }}/admin-panel/information"><i class="icon mdi mdi-information"></i><span>Information</span></a> </li>
					  <li class="parent"><a href="#"><i class="icon mdi mdi-settings"></i><span>Settings</span></a>
    					<ul class="sub-menu">
                          <li><a href="{{ env('APP_URL') }}/admin-panel/settings/languages">Languages</a> </li>
                          @if($main == 1)<li><a href="{{ env('APP_URL') }}/admin-panel/settings/managers">Managers</a> </li>@endif
                          <li><a href="{{ env('APP_URL') }}/admin-panel/settings/other">Other Settings</a> </li>
                        </ul>
                     </li>
                     @endif
                </ul>
              </div>
            </div>
          </div>

        </div>
      </div>