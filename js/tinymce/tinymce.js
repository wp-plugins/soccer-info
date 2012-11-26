function init() {
	tinyMCEPopup.resizeToInnerSize();
}

function insertSoccerInfo() {
	var tagtext;
	var tables 		 = document.getElementById('tables_panel');
	var fixtures	 = document.getElementById('fixtures_panel');
	var results		 = document.getElementById('results_panel');

	// Table mode
	if (tables.className.indexOf('current') != -1) {
		
		var type = 'table';
		
		var columns	 = document.getElementById('columns').value;
		
		var width		 = document.getElementById('width' +'_'+ type +'s').value;
		var limit		 = document.getElementById('limit' +'_'+ type +'s').value;
		var title		 = document.getElementById('title' +'_'+ type +'s').value;
		var highlight	 = document.getElementById('highlight' +'_'+ type +'s').value;
		var team 		 = document.getElementById('team' +'_'+ type +'s').value;
		var leagueId	 = document.getElementById('league_id' +'_'+ type +'s').value;
	}
	
	// Fixtures mode
	if (fixtures.className.indexOf('current') != -1) {
		
		var type = 'fixtures';
		
		var columns = '';
		
		var width		 = document.getElementById('width' +'_'+ type).value;
		var limit		 = document.getElementById('limit' +'_'+ type).value;
		var title		 = document.getElementById('title' +'_'+ type).value;
		var highlight	 = document.getElementById('highlight' +'_'+ type).value;
		var team 		 = document.getElementById('team' +'_'+ type).value;
		var leagueId	 = document.getElementById('league_id' +'_'+ type).value;
	}
	
	// Results mode
	if (results.className.indexOf('current') != -1) {
		
		var type = 'results';
		
		var columns = '';
		
		var width		 = document.getElementById('width' +'_'+ type).value;
		var limit		 = document.getElementById('limit' +'_'+ type).value;
		var title		 = document.getElementById('title' +'_'+ type).value;
		var highlight	 = document.getElementById('highlight' +'_'+ type).value;
		var team 		 = document.getElementById('team' +'_'+ type).value;
		var leagueId	 = document.getElementById('league_id' +'_'+ type).value;
	}
	
	if ( columns != '' ) columns = " columns='" + columns + "'";
	if ( width != '' ) width = " width='" + width + "'";
	if ( limit != '' ) limit = " limit='" + limit + "'";
	if ( title != '' ) title = " title='" + title + "'";
	if ( highlight != '' ) highlight = " highlight='" + highlight + "'";
	if ( team != '' ) team = " team='" + team + "'";
	
	if (leagueId != 0)
		tagtext = "[soccer-info id='" + leagueId + "' type='" + type + "'" + columns + highlight + team + title + limit + width + " /]";
	else
		tinyMCEPopup.close();
	
	if (window.tinyMCE) {
		window.tinyMCE.execInstanceCommand('content', 'mceInsertContent', false, tagtext);
		tinyMCEPopup.editor.execCommand('mceRepaint');
		tinyMCEPopup.close();
	}
	return;
}