function change_pass(){
	 
	$('.modal-body').fadeIn(500,function() {
		
		$(this).load("/admin-panel/change-pass");
		 
	});
	
	$('#mod').modal('show');
	
} 
 
function lang_form(type, uid){
	 
	$('.modal-body').fadeIn(500,function() {
		
		if(type == 'new'){
			$(this).load("/admin-panel/dialog/lang/new");
		} else {
			$(this).load("/admin-panel/dialog/lang/edit/"+uid);
		}
		
		 
		 
	});
	
	$('#mod').modal('show');
	
} 
 
function managers_form(type, uid){
	 
	$('.modal-body').fadeIn(500,function() {
		
		if(type == 'new'){
			$(this).load("/admin-panel/dialog/manager/new");
		} else {
			$(this).load("/admin-panel/dialog/manager/edit/"+uid);
		}
		
		 
		 
	});
	
	$('#mod').modal('show');
	
}
 
function lm_categories_form(type, uid){
	 
	$('.modal-body').fadeIn(500,function() {
		
		if(type == 'new'){
			// uid lang_id
			$(this).load("/admin-panel/dialog/lm/categories/new/"+uid);
		} else {
			// uid id
			$(this).load("/admin-panel/dialog/lm/categories/edit/"+uid);
		}
		
		 
		 
	});
	
	$('#mod').modal('show');
	
}
 
function lm_files_form(type, uid){
	 
	$('.modal-body').fadeIn(500,function() {
		
		if(type == 'new'){
			// uid content_id
			$(this).load("/admin-panel/dialog/lm/file/new/"+uid);
		} else {
			// uid id
			$(this).load("/admin-panel/dialog/lm/file/edit/"+uid);
		}
		
		 
		 
	});
	
	$('#mod').modal('show');
	
}

function import_selfeval(cat_id){
	 
	$('.modal-body').fadeIn(500,function() {
		
		$(this).load("/admin-panel/dialog/lm/self_eval/import/"+cat_id);
		 
	});
	
	$('#mod').modal('show');
	
} 
 
function simulations_categories_form(type, uid){
	 
	$('.modal-body').fadeIn(500,function() {
		
		if(type == 'new'){
			// uid lang_id
			$(this).load("/admin-panel/dialog/simulations/categories/new/"+uid);
		} else {
			// uid id
			$(this).load("/admin-panel/dialog/simulations/categories/edit/"+uid);
		}
		
		 
		 
	});
	
	$('#mod').modal('show');
	
}

function simulations_attributes_form(type, uid){
	 
	$('.modal-body').fadeIn(500,function() {
		
		if(type == 'new'){
			// uid lang_id
			$(this).load("/admin-panel/dialog/simulations/attributes/new/"+uid);
		} else {
			// uid id
			$(this).load("/admin-panel/dialog/simulations/attributes/edit/"+uid);
		}
		
		 
		 
	});
	
	$('#mod').modal('show');
	
}

function simulation_import(lang_id){
	 
	$('.modal-body').fadeIn(500,function() {
		
		$(this).load("/admin-panel/dialog/simulation/import/"+lang_id);
		 
	});
	
	$('#mod').modal('show');
	
} 


function import_knowledge(cat_id){
	 
	$('.modal-body').fadeIn(500,function() {
		
		$(this).load("/admin-panel/dialog/lm/knowledge_test/import/"+cat_id);
		 
	});
	
	$('#mod').modal('show');
	
} 




function simulation_categories(lang_id, simulation_id){
	 
	$('.modal-body').fadeIn(500,function() {
		
		$(this).load("/admin-panel/dialog/simulation/cat/"+lang_id+"/"+simulation_id);
		 
	});
	
	$('#mod').modal('show');
	
} 


