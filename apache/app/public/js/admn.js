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
			
		window.location.href = URL;
				
	}	 
	   
}