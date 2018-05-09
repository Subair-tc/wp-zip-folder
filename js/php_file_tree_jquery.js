jQuery(document).ready( function() {
	
	// Hide all subfolders at startup
	jQuery(".php-file-tree").find("UL").hide();
	
	// Expand/collapse on click
	jQuery(".pft-directory A").click( function() {
		jQuery(this).parent().find("UL:first").slideToggle("medium");
		if( jQuery(this).parent().attr('className') == "pft-directory" ) return false;
	});

});
