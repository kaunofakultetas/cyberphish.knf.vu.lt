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
	
	



function Confirm_Dialog(URL){
	
	if (confirm("You sure?")) {
			
		Post_To(URL);
				
	}	 
	   
}



// Sends a POST to the URL with the page's CSRF token, from the
// csrf-token meta tag of the panel's layout: the panel's
// deletes and status changes refuse a plain GET
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
