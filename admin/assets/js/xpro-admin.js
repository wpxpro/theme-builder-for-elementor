( function( $ ) {

	'use strict';
	$( document ).ready( function( e ) {
		var xpro_theme_builder_hide_shortcode_field = function() {
			var selected = $('#xpro_theme_builder_template_type').val() || 'none';
			$( '.xpro-theme-builder-options-table' ).removeClass().addClass( 'xpro-theme-builder-options-table widefat xpro-theme-builder-selected-template-' + selected );
		}

		$( document ).on( 'change', '#xpro_theme_builder_template_type', function( e ) {
			$('.target_rule-condition').prop("selectedIndex", 0);
			xpro_theme_builder_hide_shortcode_field();
		});

		xpro_theme_builder_hide_shortcode_field();
	});

} )( jQuery );
