<div class="be-left-sidebar">
        <div class="left-sidebar-wrapper"><a class="left-sidebar-toggle" href="#">Meniu</a>
          <div class="left-sidebar-spacer">
            <div class="left-sidebar-scroll">
              <div class="left-sidebar-content">
                <ul class="sidebar-elements">
                  <li class="divider">{{ __('main.menu') }}</li>
                  	 <li><a href="{{ route('home') }}/cp/dashboard"><i class="icon mdi mdi-home"></i><span>Pradžia</span></a> </li>
                  	 <li class="parent"><a href="#"><i class="icon mdi mdi-message-text-outline"></i><span>Meniu 1</span></a>
    					<ul class="sub-menu">
                          <li><a href="{{ route('home') }}/cp/inbox">Submeniu 1</a> </li>
                          <li><a href="{{ route('home') }}/cp/outbox">Submeniu 2</a> </li>
                        </ul>
                    </li>
                  	 <li><a href="{{ route('home') }}/cp/meniu-2"><i class="icon mdi mdi-account-multiple"></i><span>Meniu 2</span></a> </li>

                </ul>

                <a href="" class="btn btn-info btn-block mb-2">{{ __('main.simulations') }}</a>

              </div>
            </div>
          </div>

        </div>
      </div>