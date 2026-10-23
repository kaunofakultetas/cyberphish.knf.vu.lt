// -----------------------------------------------------------
//  [*] window.js — the admin panel's dialogs
//
//  Loaded on every signed-in page of the admin panel. Each
//  function opens one dialog: it loads the dialog's form
//  from the panel into the body of the layout's #mod modal
//  and shows the modal; the form then posts on its own. A
//  function that opens both a new and an edit dialog takes
//  the type (new or edit) and an id: for a new item the
//  language or the lesson it goes under, where it has one,
//  for an edit the item's own.
//
//  Split into:
//
//    change_pass                 — the admin's own password
//    lang_form                   — a site language
//    managers_form               — a manager
//    lm_categories_form          — a category of lessons
//    lm_files_form               — a lesson's additional file
//    import_selfeval             — self-evaluation import
//    simulations_categories_form — a simulation category
//    simulations_attributes_form — a simulation attribute
//    simulation_import           — importing a simulation
//    import_knowledge            — knowledge test import
//    simulation_categories       — a simulation's categories
//
//  Used by:
//    - admin-panel/layouts/main.blade.php — every page
//      behind the login
// -----------------------------------------------------------







// -----------------------------------------------------------
// change_pass
// -----------------------------------------------------------
//
// Opens the signed-in admin's password dialog, loaded from
// /admin-panel/change-pass, whose form posts back to the
// same path. Both routes are for main admins only: a
// country manager gets a 404 and no form in the dialog.
//
// Used by:
//   - admin-panel/layouts/main.blade.php — the Change
//     password item of the user menu
// -----------------------------------------------------------

function change_pass(){
	 
	$('.modal-body').fadeIn(500,function() {
		
		$(this).load("/admin-panel/change-pass");
		 
	});
	
	$('#mod').modal('show');
	
} 







// -----------------------------------------------------------
// lang_form
// -----------------------------------------------------------
//
// Opens the dialog that adds a site language, loaded from
// /admin-panel/dialog/lang/new, or the one that edits the
// language with the given id, from
// /admin-panel/dialog/lang/edit/{uid}.
//
// Used by:
//   - admin-panel/langs.blade.php — the Create Language
//     button and each language's settings icon
// -----------------------------------------------------------

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







// -----------------------------------------------------------
// managers_form
// -----------------------------------------------------------
//
// Opens the dialog that adds a manager, loaded from
// /admin-panel/dialog/manager/new, or the one that edits
// the manager with the given id, from
// /admin-panel/dialog/manager/edit/{uid}.
//
// Used by:
//   - admin-panel/manager_settings.blade.php — the Create
//     Manager button and each manager's edit icon
// -----------------------------------------------------------

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







// -----------------------------------------------------------
// lm_categories_form
// -----------------------------------------------------------
//
// Opens the dialog that adds a category of lessons to the
// language with the given id, loaded from
// /admin-panel/dialog/lm/categories/new/{uid}, or the one
// that edits the category with that id, from
// /admin-panel/dialog/lm/categories/edit/{uid}.
//
// Used by:
//   - admin-panel/lm_categories.blade.php — each language
//     tab's Create Category button and each category's
//     settings icon
// -----------------------------------------------------------

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







// -----------------------------------------------------------
// lm_files_form
// -----------------------------------------------------------
//
// Opens the dialog that adds an additional file to the
// lesson with the given id, loaded from
// /admin-panel/dialog/lm/file/new/{uid}, or the one that
// edits the file with that id, from
// /admin-panel/dialog/lm/file/edit/{uid}.
//
// Used by:
//   - admin-panel/lm_additional_files.blade.php — the Add
//     file button and each file's settings icon
// -----------------------------------------------------------

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







// -----------------------------------------------------------
// import_selfeval
// -----------------------------------------------------------
//
// Opens the dialog that imports self-evaluation questions
// from an uploaded text file into the category with the
// given id, loaded from
// /admin-panel/dialog/lm/self_eval/import/{uid}.
//
// Used by:
//   - admin-panel/self_eval.blade.php — the Import
//     Questions button
// -----------------------------------------------------------

function import_selfeval(cat_id){
	 
	$('.modal-body').fadeIn(500,function() {
		
		$(this).load("/admin-panel/dialog/lm/self_eval/import/"+cat_id);
		 
	});
	
	$('#mod').modal('show');
	
} 







// -----------------------------------------------------------
// simulations_categories_form
// -----------------------------------------------------------
//
// Opens the dialog that adds a simulation category to the
// language with the given id, loaded from
// /admin-panel/dialog/simulations/categories/new/{uid}, or
// the one that edits the category with that id, from
// /admin-panel/dialog/simulations/categories/edit/{uid}.
//
// Used by:
//   - admin-panel/simulations_categories.blade.php — each
//     language tab's Create Category button and each
//     category's settings icon
// -----------------------------------------------------------

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







// -----------------------------------------------------------
// simulations_attributes_form
// -----------------------------------------------------------
//
// Opens the dialog that adds a simulation attribute to the
// language with the given id, loaded from
// /admin-panel/dialog/simulations/attributes/new/{uid}, or
// the one that edits the attribute with that id, from
// /admin-panel/dialog/simulations/attributes/edit/{uid}.
//
// Used by:
//   - admin-panel/simulations_attributes.blade.php — each
//     language tab's Create Attribute button and each
//     attribute's settings icon
// -----------------------------------------------------------

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







// -----------------------------------------------------------
// simulation_import
// -----------------------------------------------------------
//
// Opens the dialog that imports a simulation from an
// uploaded spreadsheet into the language with the given
// id, loaded from
// /admin-panel/dialog/simulation/import/{lang_id}.
//
// Used by:
//   - admin-panel/simulations.blade.php — each language
//     tab's Import Simulation button
// -----------------------------------------------------------

function simulation_import(lang_id){
	 
	$('.modal-body').fadeIn(500,function() {
		
		$(this).load("/admin-panel/dialog/simulation/import/"+lang_id);
		 
	});
	
	$('#mod').modal('show');
	
} 







// -----------------------------------------------------------
// import_knowledge
// -----------------------------------------------------------
//
// Opens the dialog that imports knowledge test questions
// from an uploaded text file into the category with the
// given id, loaded from
// /admin-panel/dialog/lm/knowledge_test/import/{uid}.
//
// Used by:
//   - admin-panel/knowledge.blade.php — the Import
//     Questions button
// -----------------------------------------------------------

function import_knowledge(cat_id){
	 
	$('.modal-body').fadeIn(500,function() {
		
		$(this).load("/admin-panel/dialog/lm/knowledge_test/import/"+cat_id);
		 
	});
	
	$('#mod').modal('show');
	
} 







// -----------------------------------------------------------
// simulation_categories
// -----------------------------------------------------------
//
// Opens the dialog that files the simulation with the
// given id under categories of its language, loaded from
// web_admin.php's dialog/simulation/cat route, which takes
// the language id and the simulation id; its form posts
// back to the same path.
//
// Used by:
//   - admin-panel/simulations.blade.php — each
//     simulation's Categories link
// -----------------------------------------------------------

function simulation_categories(lang_id, simulation_id){
	 
	$('.modal-body').fadeIn(500,function() {
		
		$(this).load("/admin-panel/dialog/simulation/cat/"+lang_id+"/"+simulation_id);
		 
	});
	
	$('#mod').modal('show');
	
} 


