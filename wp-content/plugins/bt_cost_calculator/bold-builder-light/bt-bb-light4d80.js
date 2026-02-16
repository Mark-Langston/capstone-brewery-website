(function( $ ) {
	
	var set_responsive = function() {
		var width = Math.max( document.documentElement.clientWidth, window.innerWidth || 0 );
		var res = 'xxl';
		if ( width <= 1400 ) res = 'xl';
		if ( width <= 1200 ) res = 'lg';
		if ( width <= 992 ) res = 'md';
		if ( width <= 768 ) res = 'sm';
		if ( width <= 480 ) res = 'xs';
		
		$( '[data-btbbl-override-class]' ).each(function() {
			var override_classes = $( this ).data( 'btbbl-override-class' );
			for ( var prefix in override_classes ) {
				if ( override_classes[ prefix ][ res ] !== undefined ) {
					var new_class = prefix + override_classes[ prefix ][ res ];
				} else {
					var new_class = prefix + override_classes[ prefix ][ 'xxl' ];
				}
				$( this ).removeClass( override_classes[ prefix ][ 'current_class' ] );
				$( this ).addClass( new_class );
				override_classes[ prefix ][ 'current_class' ] = new_class;
				//$( this ).data( 'override_classes', override_classes );
			};
		});
		
		$( '[data-btbbl-override-style-var]' ).each(function() {
			var override_styles = $( this ).data( 'btbbl-override-style-var' );
			for ( var prefix in override_styles ) {
				if ( override_styles[ prefix ][ res ] !== undefined ) {
					var new_style = override_styles[ prefix ][ res ];
				} else {
					var new_style = override_styles[ prefix ][ 'xxl' ];
				}
				$( this ).css( '--' + prefix, new_style );
			};
		});		
		
	}		

	document.addEventListener( 'readystatechange', function() {
		if ( document.readyState === 'interactive' || document.readyState === 'complete' ) {
			set_responsive();
		}
	});
	
	$( window ).on( 'resize', function(e) {
		set_responsive();
	});
	
}( jQuery ));