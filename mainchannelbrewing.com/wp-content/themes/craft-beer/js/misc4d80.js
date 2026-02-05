'use strict';

(function( $ ) {
	
	window.confirmAndCloseOverlay = function(e){
		setOverlayCookie('btOverlay', 'true', $('#btOverlay').data('btoverlay_expiry'), $('#btOverlay').data('btoverlay_site_path') );
		$( 'body' ).addClass('btOverlayHide');
		if( e.preventDefault ) e.preventDefault();
		return false;
	}
	
	function setOverlayCookie( cname, cvalue, exdays, cpath ) {
	  var d = new Date();
	  d.setTime( d.getTime() + ( exdays*24*60*60*1000 ) );
	  var expires = "expires="+ d.toUTCString();
	  document.cookie = cname + "=" + cvalue + ";" + expires + ";path=" + cpath;
	}
	
	function getOverlayCookie( name ) {
		var nameEQ = name + "=";
		var ca = document.cookie.split(';');
		for(var i = 0; i < ca.length; i++) {
			var c = ca[i];
			while ( c.charAt(0) == ' ' ) c = c.substring( 1, c.length );
			if ( c.indexOf(nameEQ) == 0 ) return c.substring( nameEQ.length, c.length );
		}
		return null;
	}
	
	function initBtOverlay() {
		if( window.btOverlayContent !== undefined && window.btOverlayContent != '' && getOverlayCookie('btOverlay') != 'true' ) $('body').append( decodeEntities( window.btOverlayContent ) );
	}
	
	function decodeEntities( encodedString ) {
		var translate_re = /&(nbsp|amp|quot|lt|gt);/g;
		var translate = {
			"nbsp":" ",
			"amp" : "&",
			"quot": "\"",
			"lt"  : "<",
			"gt"  : ">"
		};
		return encodedString.replace( translate_re, function( match, entity ) {
			return translate[entity];
		}).replace(/&#(\d+);/gi, function(match, numStr) {
			var num = parseInt(numStr, 10);
			return String.fromCharCode(num);
		});
	}
	
	initBtOverlay();
	
	$(document).on( 'click', '.confirmAndCloseOverlayButton', function ( e ) {
		confirmAndCloseOverlay( e );		
	});	

	
  
})( jQuery );

