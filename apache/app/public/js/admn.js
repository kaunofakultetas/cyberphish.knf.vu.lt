// -----------------------------------------------------------
//  [*] admn.js — the admin panel's page behaviour
//
//  Loaded on every signed-in page of the admin panel,
//  after jQuery, Bootstrap and Summernote. When a page
//  loads, the content field of the lesson, news and
//  information forms becomes the Summernote editor, the
//  open tab of a tabbed page is kept in the URL's hash —
//  so a reload, or a save that comes back to an anchor,
//  opens the same tab — and the sidebar link of the
//  current page is marked active. The two functions send
//  the panel's deletes and status changes, which are POST
//  routes, so a link opened from a mail changes nothing.
//
//  Split into:
//
//    Page setup     — the editor, the tabs, the sidebar
//    Confirm_Dialog — asks, then posts a delete
//    Post_To        — posts to a URL with the CSRF token
//
//  Used by:
//    - admin-panel/layouts/main.blade.php — every page
//      behind the login
// -----------------------------------------------------------







// -----------------------------------------------------------
// Page setup
// -----------------------------------------------------------
//
// Runs once the document is ready. Summernote takes over
// the #summernote textarea at 500 pixels high. A click on
// a tab of #TTAB opens the tab instead of following its
// link; each tab that opens writes its pane's id into the
// URL's hash, and the tab the hash names on arrival opens
// at once — many of the panel's saves and deletes come
// back with their language's tab in the hash. Last, the
// sidebar link whose address is this page's, hash left
// out, gets the active mark.
//
// Used by:
//   - jQuery, once the document is ready
// -----------------------------------------------------------

$(document).ready(function(){
 
	 $('#summernote').summernote({
			height: 500
     });

	$('#TTAB a').click(function(e) {
	  e.preventDefault();
	  $(this).tab('show');
	});
 
	$("ul.nav-tabs > li > a").on("shown.bs.tab", function(e) {
	  var id = $(e.target).attr("href").substr(1);
	  window.location.hash = id;
	});
	 
	var hash = window.location.hash;
	$('#TTAB a[href="' + hash + '"]').tab('show');
 
	var current_url = String(window.location);
 
	$('.sidebar-elements a[href="'+current_url.replace(hash, "")+'"]').parent().addClass('active');
	 
 
});







// -----------------------------------------------------------
// Confirm_Dialog
// -----------------------------------------------------------
//
// Asks for a confirmation in the browser's own dialog and,
// on OK, posts to the URL through Post_To; Cancel sends
// nothing.
//
// Used by:
//   - the delete icons of admin-panel/users, langs,
//     manager_settings, news, information, lm_categories,
//     lm_content, lm_additional_files, self_eval, knowledge,
//     simulations, simulations_categories and
//     simulations_attributes (.blade.php)
//   - admin-panel/news_form.blade.php — the picture's
//     delete icon
// -----------------------------------------------------------

function Confirm_Dialog(URL){
	
	if (confirm("You sure?")) {
			
		Post_To(URL);
				
	}	 
	   
}







// -----------------------------------------------------------
// Post_To
// -----------------------------------------------------------
//
// Sends a POST to the URL with the page's CSRF token, from
// the csrf-token meta tag of the panel's layout: the panel's
// deletes and status changes refuse a plain GET. A hidden
// form carries the token and is submitted at once, so the
// browser follows the answer's redirect as after any form.
//
// Used by:
//   - Confirm_Dialog (above)
//   - the status badges of admin-panel/users.blade.php and
//     manager_settings.blade.php — switching a member or a
//     manager on or off
// -----------------------------------------------------------

function Post_To(URL){

	var form = document.createElement('form');
	form.method = 'POST';
	form.action = URL;

	var token = document.createElement('input');
	token.type = 'hidden';
	token.name = '_token';
	token.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
	form.appendChild(token);

	document.body.appendChild(form);
	form.submit();

}
