// -----------------------------------------------------------
//  [*] wxx.js — a member-side password dialog, unused
//
//  The member side's twin of change_pass in window.js. No
//  page loads this script, the member layout has no #mod
//  modal for it to fill, and /cp/change-pass answers only
//  a POST: the member's password form sits on the
//  dashboard (parts/change_password.blade.php) and posts
//  there itself.
//
//  Used by:
//    - nothing loads it at the moment
// -----------------------------------------------------------







// -----------------------------------------------------------
// change_pass
// -----------------------------------------------------------
//
// Would load /cp/change-pass into the body of a #mod modal
// and show the modal; the route takes no GET, so the
// request would be refused.
//
// Used by:
//   - nothing calls this at the moment
// -----------------------------------------------------------

function change_pass(){
	 
	$('.modal-body').fadeIn(500,function() {
		
		$(this).load("/cp/change-pass");
		 
	});
	
	$('#mod').modal('show');
	
} 