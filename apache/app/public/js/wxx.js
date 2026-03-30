function change_pass(){
	 
	$('.modal-body').fadeIn(500,function() {
		
		$(this).load("/cp/change-pass");
		 
	});
	
	$('#mod').modal('show');
	
} 