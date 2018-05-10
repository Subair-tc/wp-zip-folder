jQuery(document).ready( function() {
	
	// Hide all subfolders at startup
	jQuery(".php-file-tree").find("UL").hide();
	
	// Expand/collapse on click
	jQuery(".pft-directory A").click( function() {
		jQuery(this).parent().find("UL:first").slideToggle("medium");

		if( jQuery(this).parent().attr('className') == "pft-directory" ) return false;
	});

	jQuery('.pft-file a').on('click',function(e) {
		e.preventDefault();
		var filename = jQuery(this).attr('file');
		if( filename == '' ) {
			return;
		}
		jQuery('#sub_directories').val( filename );
		jQuery('#download-folder').submit();
		
	});
	jQuery('.pft-directory a').on('dblclick',function(e) {
		e.preventDefault();
		var path = jQuery(this).attr('path');
		if( path == '' ) {
			return;
		}

		jQuery('#sub_directories').val( path );
		jQuery('#download-folder').submit();
	});

	

});
