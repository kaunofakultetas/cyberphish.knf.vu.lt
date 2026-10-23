// -----------------------------------------------------------
//  [*] admn.js — the admin panel's page behaviour
//
//  Loaded on every signed-in page of the admin panel,
//  after jQuery, Bootstrap and Summernote. When a page
//  loads, the content field of the lesson, news and
//  information forms becomes the Summernote editor, the
//  open tab of a tabbed page is kept in the URL's hash —
//  so a reload, or a save that comes back to an anchor,
//  opens the same tab, and the page never scrolls for it —
//  and the sidebar link of the current page is marked
//  active. Two functions send the panel's deletes and
//  status changes, which are POST routes, so a link opened
//  from a mail changes nothing, and the page they return to
//  comes back where it stood; the last is the quick search
//  for a member on the dashboard and the users page.
//
//  Split into:
//
//    Page setup      — the editor, the tabs, the sidebar,
//                      the quick search, the way back
//    Confirm_Dialog  — asks, then posts a delete
//    Post_To         — posts to a URL with the CSRF token
//    Return_To_Place — puts the page back after Post_To
//    User_Search     — finds members in a page's rows
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
// link; each tab that opens puts its link's href in the
// URL's hash, and the tab the hash names on arrival opens
// at once — many of the panel's saves and deletes come
// back with their language's tab in the hash. The page
// stays where it is either way: the hash is written with
// history.replaceState, which neither scrolls nor adds a
// history entry, and no element has the hash for its id —
// each link finds its pane through data-target, the
// pane's id being tab- and the hash — because the browser
// scrolls an element the hash names to the top of the
// window, under the top bar. On the dashboard and the
// users page, the search box gets User_Search, and
// Return_To_Place puts a page a delete or a status change
// came back to where it stood. Last, the sidebar link whose
// address is this page's, hash left out, gets the active
// mark.
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
	  history.replaceState(null, "", $(e.target).attr("href"));
	});
	 
	var hash = window.location.hash;
	$('#TTAB a[href="' + hash + '"]').tab('show');

	$('#user-search').each(function() {
	  User_Search(this);
	});

	Return_To_Place();
 
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
// First it notes, in the tab's sessionStorage, the page's
// path, how far it is scrolled and the quick search's text,
// for Return_To_Place to put back when the answer lands on
// the same page — every such answer redirects to a page
// that opens at the top. A browser that keeps no storage
// simply returns to the top.
//
// Used by:
//   - Confirm_Dialog (above)
//   - the status badges of admin-panel/users.blade.php and
//     manager_settings.blade.php — switching a member or a
//     manager on or off
// -----------------------------------------------------------

function Post_To(URL){

	try {
		sessionStorage.setItem('panel_place', JSON.stringify({
			path: window.location.pathname,
			y: window.scrollY,
			search: $('#user-search').val() || '',
			at: Date.now()
		}));
	} catch (e) {}

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







// -----------------------------------------------------------
// Return_To_Place
// -----------------------------------------------------------
//
// Puts the page back where it stood when Post_To sent a
// delete or a status change from it: when the answer lands
// on the same path within a minute, the quick search gets
// its text back — the rows filter as before — and the
// window scrolls to where it was, instead of the top. The
// note is read once and dropped, also when it belongs to
// another page or is older, so a failed post leaves nothing
// behind to act on later.
//
// Used by:
//   - Page setup (above), on every page
// -----------------------------------------------------------

function Return_To_Place(){

	var place = null;
	try {
		place = JSON.parse(sessionStorage.getItem('panel_place'));
		sessionStorage.removeItem('panel_place');
	} catch (e) {}

	if(!place || place.path !== window.location.pathname || Date.now() - place.at > 60000){
		return;
	}

	if(place.search){
		$('#user-search').val(place.search).trigger('input');
	}
	window.scrollTo(0, place.y);

}






// -----------------------------------------------------------
// User_Search
// -----------------------------------------------------------
//
// The quick search of the dashboard and the users page.
// Every change in the box keeps, in every tab — or, on the
// users page, which has none, in its one list — only the
// rows whose data-search, the member's user name and email,
// holds the text, letter case and accents aside, so that
// zydrune finds Žydrūnė. On the dashboard a member stands
// in each language tab they have begun, so a search can
// find people in several tabs: each tab with matches is
// marked and given a badge counting them, each tab without
// fades, and a tab with members but no match shows its "No
// users match the search" row. When the open tab holds no
// match, the first tab that does opens — the only one, or
// of several the busiest, as the tabs run busiest first. An
// empty box shows every row again and takes the marks away;
// the open tab stays. The clear button beside the box, shown
// while it holds text, empties it and puts the cursor back.
// A text already in the box when the page opens filters at
// once.
//
// Used by:
//   - Page setup (above) — for #user-search, the box of
//     admin-panel/dashboard.blade.php and users.blade.php
// -----------------------------------------------------------

function User_Search(box){

	// Lower case without accents, for the text and the rows
	var fold = function(text){
		return text.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
	};

	// The searchable rows of a part of the page
	var rows = function(scope){
		return scope.find('tr[data-search]').map(function(){
			return { row: $(this), text: fold($(this).attr('data-search')) };
		}).get();
	};

	var tabs = $('#TTAB a[data-toggle="tab"]').map(function(){
		var pane = $($(this).attr('data-target'));
		return { link: $(this), none: pane.find('tr.search-none'), rows: rows(pane) };
	}).get();

	// A page without tabs is one list, with no tab to mark
	if(tabs.length === 0){
		tabs = [{ link: $(), none: $('tr.search-none'), rows: rows($(document)) }];
	}

	var clear = $(box).siblings('.user-search-clear');
	clear.on('click', function(){
		$(box).val('').trigger('input').focus();
	});

	$(box).on('input', function(){

		var query = fold($(box).val().trim());
		var hits = [];
		clear.toggle($(box).val() !== '');

		$.each(tabs, function(i, tab){

			var found = 0;
			$.each(tab.rows, function(j, entry){
				var match = entry.text.indexOf(query) !== -1;
				entry.row.toggle(match);
				if(match){
					found++;
				}
			});

			tab.link.find('.search-count').remove();
			tab.link.toggleClass('search-hit', query !== '' && found > 0);
			tab.link.toggleClass('search-miss', query !== '' && found === 0);
			tab.none.toggle(query !== '' && tab.rows.length > 0 && found === 0);

			if(query !== '' && found > 0 && tab.link.length > 0){
				tab.link.append($('<span class="badge badge-primary search-count"></span>').text(found));
				hits.push(tab.link);
			}

		});

		var open = hits.filter(function(link){
			return link.hasClass('active');
		});
		if(hits.length > 0 && open.length === 0){
			hits[0].tab('show');
		}

	});

	// A text the browser put back, after Back or a reload,
	// filters at once
	$(box).trigger('input');

}
