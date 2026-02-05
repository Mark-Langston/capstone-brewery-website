'use strict';

(function( $ ) {
	
	var total;
	
	var is_rtl = false;
	var rtl_direction = 1;
	
	// ---------------
	// Functions start
	// ---------------
	
	window.bt_cc_subtotal = ';var setSubtotal = function( id, val, ignore_format ) {' +
	'if ( arguments.length < 2 || ! jQuery( "#btSubtotalId"+id ) ) return;' +
	'var c = jQuery( "#btSubtotalId" + id ).closest( ".btQuoteBooking" );' +
	'if ( ignore_format !== true ) val = bt_cc_currency_format( c, val );' +
	'var currency = c.data( "currency" );' +
	'var currency_after = c.data( "currency_after" );' +
	'var currency_space = c.data( "currency_space" );' +
	'var hide_subtotal = c.data( "hide_subtotal" );' +
	'if ( currency != "" && ignore_format !== true ) {' +
		'if ( currency_after == "yes" ) {' +
			'if ( currency_space == "yes" ) {' +
				'val = val + " " + currency;' +
			'} else {' +
				'val = val + currency;' +
			'}' +
		'} else {' +
			'if ( currency_space == "yes" ) {' +
				'val = currency + " " + val;' +
			'} else {' +
				'val = currency + val;' +
			'}' +
		'}' +
	'}' +
	'jQuery( "#btSubtotalId" + id ).data( "val", val );' +
	'if ( hide_subtotal == "yes" ) {' +
		'jQuery( "#btSubtotalId" + id ).hide();' +
		'return;' +
	'}' +
	'jQuery( "#btSubtotalId" + id ).find( ".btQuoteSubtotalCalc" ).html( val );' +
	'};';
	
	// Base functions 
	
	window.bt_cc_sanitize_text = function( text ) {
		return DOMPurify.sanitize( String( text ) );
	}
	 
	window.bt_parse_float = function( x ) {
		var r = parseFloat( x );
		if ( isNaN( r ) ) r = 0;
		return r;
	}
	
	$.fn.bt_cc_lock = function(){
		$(this).addClass( 'btCCLock' );
		return $(this);
	}
	
	$.fn.bt_cc_unlock = function(){
		$(this).removeClass( 'btCCLock' );
		return $(this);
	}
	
	// function to be called from element conditions 

	window.bt_cc_set_slider_value = function( elem, value, calc ){
		if ( elem.slider( 'instance' ) ) { 
			elem.slider( 'value', value );
			elem.find('.btQuoteSliderValue').html( value );	
			if ( calc ) {
				bt_quote_total( elem.closest( '.btQuoteBooking' ) );
				bt_paypal_items( elem.closest( '.btQuoteBooking' ) );
			}
		}

	}
	window.bt_cc_get_slider_value = function( elem ){
		if ( elem.slider( 'instance' ) ) {
			var val = bt_parse_float( elem.find('.btQuoteSliderValue').html() );
		} else {
			var val = bt_parse_float( elem.data( 'initial-value' ) );
		}
		return val;
		
	}
	
	$.fn.bt_cc_set_value = function( value ){
		if ( value !== null && value !== 'undefined' && value !== undefined ) {
			var initial_value = value;	
		} else {
			var initial_value = $( this ).find( '.btQuoteElement' ).data( 'initial-value' );	
		}
		if ( $( this ).find( '.btQuoteItemInput > .btQuoteSelect' ).length > 0 ) {
			var oDropdown = $( this ).find( '.btQuoteSelect' ).data("dd");
			if ( oDropdown !== undefined ) {
				bt_cc_init_dropdown( oDropdown, "#"+$( this ).attr('id'), initial_value );	
			}	
		} else if ( $( this ).find( '.btQuoteItemInput > .btQuoteSlider' ).length > 0 ) {
			if ( $( this ).find( '.btQuoteItemInput > .btQuoteSlider' ).slider( 'instance' ) ) {
				bt_cc_set_slider_value( $( this ).find( '.btQuoteItemInput > .btQuoteSlider' ), initial_value );
			} else {
				/* W3 Tiotal cache safe path */
				setTimeout( bt_cc_set_slider_value, 2000, $( this ).find( '.btQuoteItemInput > .btQuoteSlider' ), initial_value, true );
			}
			
		} else if ( $( this ).find( '.btQuoteItemInput > .btQuoteText' ).length > 0 ) {
			$( this ).find( '.btQuoteText' ).val( initial_value );
		} else if ( $( this ).find( '.btQuoteItemInput > .btQuoteSwitch' ).length > 0 ) {
			if ( 
			( $( this ).find( '.btQuoteSwitch' ).data('on') == initial_value && !$( this ).find( '.btQuoteSwitch' ).hasClass('on') ) || 
			( $( this ).find( '.btQuoteSwitch' ).data('off') == initial_value && $( this ).find( '.btQuoteSwitch' ).hasClass('on') )
			) {
				$( this ).find( '.btQuoteSwitch' ).trigger('click');
			}
		}
		bt_cc_eval_conditions( initial_value, $( this ).find( '.btQuoteElement' ).data( 'condition' ) );
		bt_quote_total( $( this ).closest( '.btQuoteBooking' ) );
		bt_paypal_items( $( this ).closest( '.btQuoteBooking' ) );
		return $(this);
	}
	
	window.bt_cc_eval_conditions = function( val, conditions ){
		// alert(conditions);
		if ( conditions == '' || conditions == undefined ) return false;
		if ( val == '' ) val = 0;	

		if ( conditions.indexOf(';') == -1 ) {
			conditions = atob( conditions );
			var bt_cc_conditions = conditions.split(/\r?\n/);
		} else {
			var bt_cc_conditions = conditions.split("#bt_cc_nl#");
		}
		$.each( bt_cc_conditions, function( index, value ) {
			if ( bt_cc_conditions[index] != undefined ) {
				var bt_cc_single_condition = bt_cc_conditions[index].split(";");
				var eval_action  = bt_cc_single_condition[2];
				if ( bt_cc_single_condition[3] == 'lock' ) {
					eval_action += '.bt_cc_lock()';	
				} else if ( bt_cc_single_condition[3] == 'unlock' ) {
					eval_action += '.bt_cc_unlock()';	
				}
				if ( bt_cc_single_condition[0] != '' ) {
					var eval_code =  'if ( ' + val + decodeURIComponent( bt_cc_single_condition[0] ) + ' ) { $(\'#' + bt_cc_single_condition[1] +  '\').' + eval_action + '; }' ;
					eval( eval_code );	
				}				
			}

		});
		return false;
	}

	// Paypal calculation
	
	window.bt_paypal_items = function( c ) {
					
		$( c ).each(function() {
		
			if ( $( this ).find( '.btPayPalButton' ).length > 0 ) {
				
				var form = $( this ).find( '.btPayPalButton' ).next();
				form.find( '.btPayPalItem' ).remove();
				
				var x = 0;
		
				// items not in multiply and group
				$( this ).find( '.btQuoteBookingForm' ).find( '.btQuoteItem' ).not( '.btQuoteMBlock .btQuoteItem' ).not( '.btQuoteGBlock .btQuoteItem' ).each(function() {
					
					var unit_price = 0;
					var val = 0;
					var offset = 0;
					
					var selected_name = '';

					$( this ).find( '.btQuoteText' ).each(function() {
						unit_price = bt_parse_float( $( this ).data( 'price' ) );
						val = bt_parse_float( $( this ).val() );
					});
					
					$( this ).find( '.btQuoteSelect' ).find( '._msddli_.selected' ).each(function() {
						unit_price = bt_parse_float( $( this ).data( 'value' ) );
						val = 1;
						selected_name = $( this ).find( '.ddlabel' )[0].innerHTML;
						if ( $( this ).is( ':first-child' ) ) {
							selected_name = '';
						}
					});
					
					var is_slider = false;
					$( this ).find( '.btQuoteSlider' ).each(function() {
						unit_price = bt_parse_float( $( this ).data( 'price' ) );
						offset = bt_parse_float( $( this ).data( 'offset' ) );
						val = bt_cc_get_slider_value ( $( this ) );
						is_slider = true;
					});
					
					$( this ).find( '.btQuoteSwitch' ).each(function() {
						if ( $( this ).hasClass( 'on' ) ) {
							unit_price = bt_parse_float( $( this ).data( 'on' ) );
						} else {
							unit_price = bt_parse_float( $( this ).data( 'off' ) );
						}
						val = 1;
					});

					var label = $( this ).find( 'label' ).html();
					
					if ( is_slider ) {
						label = label + ': ' + val;
					}
					
					if ( selected_name != '' ) {
						selected_name = selected_name.replace( '<span class="description">', '/' );
						selected_name = selected_name.replace( '</span>', '' );
						if ( label.endsWith( '?' ) || label.endsWith( ':' ) ) {
							label = label + ' ' + selected_name;
						} else {
							label = label + ': ' + selected_name;
						}
						
					}
					
					if ( is_slider ) {
						val = val * unit_price + offset;
					} else {
						val = val * unit_price;
					}
					
					if ( label !== undefined && val > 0 ) {
						x++;
						val = val.toFixed( 2 );
						form.append( '<input type="hidden" name="item_name_' + x + '" value="' + label + '" class="btPayPalItem"><input type="hidden" name="amount_' + x + '" value="' + val + '" class="btPayPalItem">' );
					}
				});
				
				// multiply
				
				$( this ).find( '.btQuoteBookingForm' ).find( '.btQuoteMBlock' ).each(function() {

					var m_total = 1;
					var m_first = true;
					var m_val = 0;
					var selected_name = '';
					var label = '';
					
					$( this ).find( '.btQuoteItem' ).each(function() {
				
						$( this ).find( '.btQuoteText' ).each(function() {
							var unit_price = bt_parse_float( $( this ).data( 'price' ) );
							var val = bt_parse_float( $( this ).val() );
							val = val * unit_price;
							if ( m_first ) {
								m_val = val;
								label = $( this ).closest( '.btQuoteItem' ).find( 'label' ).html();
							} else {
								m_total *= val;
							}
							m_first = false;
						});
						
						$( this ).find( '.btQuoteSelect' ).find( '._msddli_.selected' ).each(function() {
							var val = bt_parse_float( $( this ).data( 'value' ) );
							if ( m_first ) {
								m_val = val;
								label = $( this ).closest( '.btQuoteItem' ).find( 'label' ).html();
								selected_name = $( this ).find( '.ddlabel' )[0].innerHTML;
								selected_name = selected_name.substring( 0, selected_name.indexOf( '<span' ) );
								if ( $( this ).is( ':first-child' ) ) {
									selected_name = '';
								}
								if ( selected_name != '' ) label = label + ': ' + selected_name;											
							} else {
								m_total *= val;
							}
							m_first = false;
						});
						
						$( this ).find( '.btQuoteSlider' ).each(function() {
							var unit_price = bt_parse_float( $( this ).data( 'price' ) );
							var offset = bt_parse_float( $( this ).data( 'offset' ) );
							var val = bt_cc_get_slider_value( $( this ) );
							val = val * unit_price + offset;
							if ( m_first ) {
								m_val = val;
								label = $( this ).closest( '.btQuoteItem' ).find( 'label' ).html();
							} else {
								m_total *= val;
							}
							m_first = false;
						});
						
						$( this ).find( '.btQuoteSwitch' ).each(function() {
							if ( $( this ).hasClass( 'on' ) ) {
								var val = bt_parse_float( $( this ).data( 'on' ) );
							} else {
								var val = bt_parse_float( $( this ).data( 'off' ) );
							}							
							if ( m_first ) {
								m_val = val;
								label = $( this ).closest( '.btQuoteItem' ).find( 'label' ).html();
							} else {
								m_total *= val;
							}
							m_first = false;
						});
						
						
					});
					
					if ( m_total > 0 && m_val > 0 ) {
						x++;
						m_val = m_val.toFixed( 2 );
						form.append( '<input type="hidden" name="item_name_' + x + '" value="' + label + '" class="btPayPalItem"><input type="hidden" name="amount_' + x + '" value="' + m_val + '" class="btPayPalItem"><input type="hidden" name="quantity_' + x + '" value="' + m_total + '" class="btPayPalItem">' );
					}

				});
				
				// group
				
				$( this ).find( '.btQuoteBookingForm' ).find( '.btQuoteGBlock' ).each(function() {
					
					var paypal_label = $( this ).data( 'paypal_label' );
					
					var g_total = window.bt_cc_group_total( $( this ) );
					
					if ( paypal_label != '' && g_total > 0 ) {
						x++;
						g_total = g_total.toFixed( 2 );
						form.append( '<input type="hidden" name="item_name_' + x + '" value="' + paypal_label + '" class="btPayPalItem"><input type="hidden" name="amount_' + x + '" value="' + g_total + '" class="btPayPalItem"><input type="hidden" name="quantity_' + x + '" value="1" class="btPayPalItem">' );
					}

				});
			}
		});
	}
	
	// Currency format
	
	window.bt_cc_currency_format = function( c, n ) {
		var regex = /(\d)(?=(\d{3})+\.)/g;
		var regex_replace = '$1,';

		Number.prototype.toNumberFormat=function(t,r,e){r=r||".",e=e||"";var o=this.toFixed(t).split(".");return o[0]=o[0].replace(/\B(?=(\d{3})+(?!\d))/g,e),o.join(r)};
		
		var format = c.data( 'total_format' );
		var decimals = c.data( 'total_decimals' );

		if ( format == 'currency_1' || format == 'currency_2' || format == 'currency_3' ) {

			var ts = ',';
			var ds = '.';
			
			if ( format == 'currency_2' ) {
				var ts = '.';
				var ds = ',';
			}

			if ( format == 'currency_3' ) {
				var ts = ' ';
				var ds = ',';
			}

			n = n.toNumberFormat( decimals, ds, ts );
			
		} else if ( format == 'decimal_1' || format == 'decimal_2' ) {

			var ds = '.';
			var ts = '';

			if ( format == 'decimal_2' ) {
				var ds = ',';
			}

			n = n.toNumberFormat( decimals, ds, ts );
			
		} else if ( format == 'rounded' ) {
			
			n = Math.round( n );
			
		}
		
		return n;
	}
	
	// Total calculation
	
	window.bt_quote_total = function( c, initial = false ) {
		
		var c = $( c );
	
		var total = 0;

		c.find( '.btQuoteText' ).not( '.btQuoteMBlock .btQuoteText' ).not( '.btQuoteGBlock .btQuoteText' ).each(function() {
			var unit_price = bt_parse_float( $( this ).data( 'price' ) );
			var val = bt_parse_float( $( this ).val() );
			val = val * unit_price;
			total += val;
		});
		
		c.find( '.btQuoteSelect' ).not( '.btQuoteMBlock .btQuoteSelect' ).not( '.btQuoteGBlock .btQuoteSelect' ).find( '._msddli_.selected' ).each(function() {
			var val = bt_parse_float( $( this ).data( 'value' ) );
			total += val;
		});
		
		c.find( '.btQuoteSlider' ).not( '.btQuoteMBlock .btQuoteSlider' ).not( '.btQuoteGBlock .btQuoteSlider' ).each(function() {
			var unit_price = bt_parse_float( $( this ).data( 'price' ) );
			var offset = bt_parse_float( $( this ).data( 'offset' ) );
			var val = bt_cc_get_slider_value( $( this ) );
			val = val * unit_price + offset;
			total += val;
		});
		
		c.find( '.btQuoteSwitch' ).not( '.btQuoteMBlock .btQuoteSwitch' ).not( '.btQuoteGBlock .btQuoteSwitch' ).each(function() {
			if ( $( this ).hasClass( 'on' ) ) {
				total += bt_parse_float( $( this ).data( 'on' ) );
			} else {
				total += bt_parse_float( $( this ).data( 'off' ) );
			}
		});
							
		
		// multiply
		
		c.find( '.btQuoteMBlock' ).each(function() {
								
			var m_total = 0;
			var m_first = true;
			$( this ).find( '.btQuoteText' ).each(function() {
									   
				var unit_price = bt_parse_float( $( this ).data( 'price' ) );
				var val = bt_parse_float( $( this ).val() );
				val = val * unit_price;
				if ( m_first ) {
					m_total = val;
				} else {
					m_total *= val;
				}
				m_first = false;

			});
			
			$( this ).find( '.btQuoteSelect' ).find( '._msddli_.selected' ).each(function() {

				var val = bt_parse_float( $( this ).data( 'value' ) );

				if ( m_first ) {
					m_total = val;
				} else {
					m_total *= val;
				}
				m_first = false;

			});
			
			$( this ).find( '.btQuoteSlider' ).each(function() {

				var unit_price = bt_parse_float( $( this ).data( 'price' ) );
				var offset = bt_parse_float( $( this ).data( 'offset' ) );
				var val = bt_cc_get_slider_value( $( this ) );
				val = val * unit_price + offset;
				if ( m_first ) {
					m_total = val;
				} else {
					m_total *= val;
				}
				m_first = false;

			});
			
			$( this ).find( '.btQuoteSwitch' ).each(function() {

				if ( $( this ).hasClass( 'on' ) ) {
					var val = bt_parse_float( $( this ).data( 'on' ) );
				} else {
					var val = bt_parse_float( $( this ).data( 'off' ) );
				}							
				if ( m_first ) {
					m_total = val;
				} else {
					m_total *= val;
				}
				m_first = false;

			});
			
			total += m_total;
			
		});
		
		// group
		
		c.find( '.btQuoteGBlock' ).each(function() {
			
			var g_total = window.bt_cc_group_total( $( this ) );

			total += g_total;
			
		});
		
		var total_raw = total;
		
		total = bt_cc_currency_format( c, total );

		c.find( '.btQuoteTotalCalc' ).html( total );
		
		c.data( 'total', total );
		
		// woocommerce
		
		if ( ! initial ) {
		
			var wc_product_id = c.data( 'wc_product_id' );
			var wc_single_product = c.data( 'wc_single_product' ) === 'yes' ? true : false;
			var wc_quote_text = c.data( 'wc_quote_text' );
			
			if ( wc_product_id != '' && $( 'span[data-bt-cc-product-id="' + wc_product_id + '"]' ).length > 0 ) {
			
				var $bdi = $( 'span[data-bt-cc-product-id="' + wc_product_id + '"] bdi' );
				var $span = $( 'span[data-bt-cc-product-id="' + wc_product_id + '"]:not(:has(bdi))' ); // storefront sticky header (some other locations too?)
			
				// update product price
				$bdi.contents().filter(function() {
					return this.nodeType === 3;
				}).length > 0 
					? $bdi.contents().filter(function() {
						return this.nodeType === 3;
					}).replaceWith( total )
					: $bdi.append( total );
					
				$span.contents().filter(function() {
					return this.nodeType === 3;
				}).length > 0 
					? $span.contents().filter(function() {
						return this.nodeType === 3;
					}).replaceWith( total )
					: $span.append( total );
			
				// ajax set price
				if ( window.bt_cc_ajax_timer ) {
					clearTimeout( window.bt_cc_ajax_timer );
				}
				
				$( 'button[value="' + wc_product_id + '"]' ).attr( 'disabled', 'disabled' );
				$( 'a.add_to_cart_button[data-product_id="' + wc_product_id + '"]' ).filter(function() {
					let href = $( this ).attr( 'href' );
					return href.includes( 'add-to-cart=' + wc_product_id + '&' ) || href.endsWith( 'add-to-cart=' + wc_product_id );
				}).addClass( 'bt_cc_disabled' );

				window.bt_cc_ajax_timer = setTimeout(function() {
					
					var data = {
						'action': 'bt_cc_wc_set_price_ajax',
						'bt_cc_nonce': c.data( 'bt_cc_nonce' ),
						'product_id': wc_product_id,
						'quote': window.bt_cc_quote( c, { 'wc': true } ),
						'quote_text': wc_quote_text,
						'price': total_raw
					}
					
					$.ajax({
						type: 'POST',
						url: c.data( 'url_ajax' ),
						data: data,
						async: true,
						success: function( response ) {
							$( 'button[value="' + wc_product_id + '"]' ).removeAttr( 'disabled' );
							$( 'a.add_to_cart_button[data-product_id="' + wc_product_id + '"]' ).filter(function() {
								let href = $( this ).attr( 'href' );
								return href.includes( 'add-to-cart=' + wc_product_id + '&' ) || href.endsWith( 'add-to-cart=' + wc_product_id );
							}).removeClass( 'bt_cc_disabled' );
						},
						error: function( xhr, status, error ) {
							c.find( '.btSubmitMessage' ).hide().html( c.data( 'message_error' ) ).fadeIn();
						}
					});
				}, 300 );
			}
		}
	}
	
	// group total
	
	window.bt_cc_group_total = function( g ) {
		
		var eval_code = g.data( 'eval' );
		
		var group_array = [];
		
		var c = g.closest( '.btQuoteBooking' );
		
		g.find( '.btQuoteItem .btQuoteItemInput' ).each(function() {
			
			var val;
			
			$( this ).find( '.btQuoteText' ).each(function() {
				var unit_price = bt_parse_float( $( this ).data( 'price' ) );
				val = bt_parse_float( $( this ).val() );
				val = val * unit_price;
			});
			
			$( this ).find( '.btQuoteSelect' ).find( '._msddli_.selected' ).each(function() {
				val = window.bt_cc_sanitize_text( $( this ).data( 'value' ) );	
			});
			
			$( this ).find( '.btQuoteSlider' ).each(function() {
				var unit_price = bt_parse_float( $( this ).data( 'price' ) );
				var offset = bt_parse_float( $( this ).data( 'offset' ) );
				val = bt_cc_get_slider_value( $( this ) );
				val = val * unit_price + offset;
			});
			
			$( this ).find( '.btQuoteSwitch' ).each(function() {
				if ( $( this ).hasClass( 'on' ) ) {
					val = window.bt_cc_sanitize_text( $( this ).data( 'on' ) );
				} else {
					val = window.bt_cc_sanitize_text( $( this ).data( 'off' ) );
				}
			});
			
			if ( val === '' ) {
				val = '``';
			} else if ( isNaN( val ) ) {
				val = '`' + val + '`';
			}
			
			group_array.push( val );

		});

		var patt = /\$\d+/igm;
		var match = eval_code.match( patt );

		if ( match === null ) {
			eval_code = atob( eval_code );
			match = eval_code.match( patt );
		}

		if ( match === null ) {
			return;
		}

		for ( var i = 0; i < match.length; i++ ) {
			eval_code = eval_code.replace( match[ i ], group_array[ i ] );
		}
		
		var wc_product_price = '';
		if ( c.data( 'wc_product_price' ) != '' ) {
			wc_product_price = ';var _wc_product_price = ' + c.data( 'wc_product_price' ) + ';';
		}
		eval_code = wc_product_price + window.bt_cc_subtotal + eval_code;

		var g_total = eval( '(function() {' + decodeURIComponent( eval_code ) + '}())' );
		
		return parseFloat( g_total );
	}

	// Init functions
	
	// Dropdown init

	window.bt_cc_init_dropdown = function( elem, id, index ) {
		elem.set( "selectedIndex", index );
		var ui2 = $( id ).find( '._msddli_.selected' );
		var val = elem.getData().data.value;
		ui2.data( 'value', val );
		/*bt_cc_eval_conditions( val, ui2.closest( '.btQuoteSelect' ).data( 'condition' ) );
		bt_quote_total( ui2.closest( '.btQuoteBooking' ) );
		bt_paypal_items( ui2.closest( '.btQuoteBooking' ) );*/
	}
	
	// Base init
	
	window.bt_cc_init = function() {
		
		$( ".btQuoteBooking" ).each(function( index ) {
			var c = $( this );

			setTimeout( function(){ c.css( 'opacity', '1' ); }, 200 );
			
			// Init elements 
			
			c.find( '.btContactDate' ).datepicker({
				prevText: window.bt_cc_translate['prev'],
				nextText: window.bt_cc_translate['next'],
				dateFormat: c.data( 'date_format' ),
				dayNamesMin: [ window.bt_cc_translate['su'], window.bt_cc_translate['mo'], window.bt_cc_translate['tu'], window.bt_cc_translate['we'], window.bt_cc_translate['th'], window.bt_cc_translate['fr'], window.bt_cc_translate['sa'] ],
				monthNames: [ window.bt_cc_translate['january'], window.bt_cc_translate['february'], window.bt_cc_translate['march'], window.bt_cc_translate['april'], window.bt_cc_translate['may'], window.bt_cc_translate['june'], window.bt_cc_translate['july'], window.bt_cc_translate['august'], window.bt_cc_translate['september'], window.bt_cc_translate['october'], window.bt_cc_translate['november'], window.bt_cc_translate['december'] ],
				beforeShow: function( input, inst ) {
					$( '.ui-datepicker' ).addClass( 'btDatePicker' );
				}
			});
			c.find( '.btQuoteSlider' ).each(function() {
				$( this ).slider({
					min: ! window.is_rtl ? $( this ).data( 'min' ) : -$( this ).data( 'max' ),
					max: ! window.is_rtl ? $( this ).data( 'max' ) : -$( this ).data( 'min' ),
					step: $( this ).data( 'step' ),
					value: ! window.is_rtl ? $( this ).data( 'value' ) : -$( this ).data( 'value' ),
					change: function( event, ui ) { 
						$( this ).find('.btQuoteSliderValue').html( window.rtl_direction * $( this ).slider( 'value' ) );
						bt_cc_eval_conditions( window.rtl_direction * $( this ).slider( 'value' ), $( this ).data( 'condition' ) );
					}
				});
				bt_cc_eval_conditions( window.rtl_direction * $( this ).data( 'value' ), $( this ).data( 'condition' ) );
			});
			
			c.find( '.ui-slider-handle' ).each(function() {
				$( this ).append( $( this ).closest( '.btQuoteItemInput' ).find( $( '.btQuoteSliderValue' ) ) );
			});
			

			c.find( '.btQuoteSlider' ).each(function() {
				var this_slider = $( this );
				$( this ).slider({
					slide: function( event, ui ) {
						var val = window.rtl_direction * ui.value; 
						this_slider.slider( 'value', val );
						this_slider.find('.btQuoteSliderValue').html( val );
						bt_quote_total( c );
						bt_paypal_items( c );
					}
				});
			});			

			
			c.find( '.btQuoteSwitch' ).on( 'click', function() {
				if ( $( this ).hasClass( 'on' ) ) { 
					$( this ).removeClass( 'on' );
					bt_cc_eval_conditions( $( this ).data( 'off' ), $( this ).data( 'condition' ) );
				} else {
					$( this ).addClass( 'on' );
					bt_cc_eval_conditions( $( this ).data( 'on' ), $( this ).data( 'condition' ) );
				}
				bt_quote_total( c );
				bt_paypal_items( c );
			});
			
			c.find( '.btQuoteSwitch' ).each( function() {
				if ( $( this ).hasClass( 'on' ) ) { 
					bt_cc_eval_conditions( $( this ).data( 'on' ), $( this ).data( 'condition' ) );
				} else {
					bt_cc_eval_conditions( $( this ).data( 'off' ), $( this ).data( 'condition' ) );
				}
			});
			
			c.find( '.btPayPalButton' ).on( 'click', function() {
				$( this ).next().submit();
			});
			
			c.find( '.btQuoteText' ).each(function() {
				bt_cc_eval_conditions( $( this ).val(), $( this ).data( 'condition' ) );
			});
			
			c.find( '.btQuoteText' ).on( 'input', function() {
				bt_quote_total( c );
				bt_paypal_items( c );
				bt_cc_eval_conditions( $( this ).val(), $( this ).data( 'condition' ) );
			});
			
			c.find( '.btContactNext' ).on( 'click', function() {
				$( 'html, body' ).delay( 1000 ).animate({
					scrollTop: ( $( this ).closest( '.btQuoteBooking' ).find( '.btTotalQuoteContactGroup' ).offset().top - 30 )
				}, 400 );
				
				var contact_group = $( this ).closest( '.btQuoteBooking' ).find( '.btTotalQuoteContactGroup' );
				
				$( this ).closest( '.btQuoteBooking' ).find( '.btTotalQuoteContactGroup' ).addClass( 'btActive' );
				$( this ).closest( '.btQuoteBooking' ).find( '.btQuoteBookingForm' ).removeClass( 'btActive' );
			});

			c.find( '.btContactSubmit' ).on( 'click', function() {

				c.find( '.btContactFieldError' ).removeClass( 'btContactFieldError' );
		
				var val = true;
				
				c.find( '.btContactField' ).each(function() {
					if ( $( this ).parent().hasClass( 'btContactFieldMandatory' ) 
					&& ( ( $( this ).val() == '' && ! $( this ).hasClass( 'btContactTime' ) ) 
					|| ( $( this ).hasClass( 'btContactTime' ) && $( this ).hasClass( 'btNotSelected' ) ) ) ) {
						$( this ).parent().addClass( 'btContactFieldError' );
						val = false;
					}
				});
				
				if ( ! val ) {
					c.find( '.btSubmitMessage' ).hide().html( c.data( 'message_mandatory' ) ).fadeIn();
					return false;
				}

				var quote = window.bt_cc_quote( c );
				
				var recaptcha_response = '';
				if ( c.data( 'rec_secret_key' ) != '' ) {
					var recaptcha_response = grecaptcha.getResponse( c.find( '.g-rec' ).data( 'widget_id' ) );
					grecaptcha.reset( c.find( '.g-rec' ).data( 'widget_id' ) );
				}
				
				var email_confirmation = 'no';
				if ( c.find( '.bt_cc_email_confirmation' ).length ) {
					if ( c.find( '.bt_cc_email_confirmation' ).prop( 'checked' ) ) {
						email_confirmation = 'yes';
					}
				} else {
					email_confirmation = 'yes';
				}

				var email_gdpr = 'no';
				if ( c.find( '.bt_cc_email_gdpr' ).length ) {
					if ( c.find( '.bt_cc_email_gdpr' ).prop( 'checked' ) ) {
						email_gdpr = 'yes';
					}
				} else {
					email_gdpr = 'yes';
				}

				if ( email_gdpr == 'no' ) {
					c.find( '.btSubmitMessage' ).html(c.data( 'email_gdpr_not_text' ) );
					return false;
				}

				var data = {
					'action': 'bt_cc',
					'recaptcha_response': recaptcha_response,
					'recaptcha_secret': c.data( 'rec_secret_key' ),
					'admin_email': c.data( 'admin_email' ),
					'email_client': c.data( 'email_client' ),
					'email_rtl': c.data( 'email_rtl' ),
					'currency': c.data( 'currency' ),
					'currency_after': c.data( 'currency_after' ),
					'currency_space': c.data( 'currency_space' ),
					'email_confirmation': email_confirmation,
					'url_confirmation': c.data( 'url_confirmation' ),
					'subject': c.data( 'subject' ),
					'email_header': c.data( 'email_header' ),
					'email_footer': c.data( 'email_footer' ),
					'quote' : quote,
					'total' : c.data( 'total' ),
					'total_text' : c.data( 'total_text' ),
					'name' : c.find( '.btContactName' ).val(),
					'email' : c.find( '.btContactEmail' ).val(),
					'phone' : c.find( '.btContactPhone' ).val(),
					'address' : c.find( '.btContactAddress' ).val(),
					'date' : c.find( '.btContactDate' ).val(),
					'time' : c.find( '.btContactTime option:selected' ).val(),
					'date_text': c.data( 'date_text' ),
					'time_text': c.data( 'time_text' ),
					'message' : c.find( '.btContactMessage' ).val(),
					'bt_cc_nonce': c.data( 'bt_cc_nonce' ),
					'email_gdpr': c.data( 'email_gdpr' ),
					'email_gdpr_text': c.data( 'email_gdpr_text' ),
					'email_gdpr_not_text': c.data( 'email_gdpr_not_text' )
				};
				
				c.find( '.btSubmitMessage' ).hide().html( c.data( 'message_please_wait' ) ).fadeIn();

				$.ajax({
					type: 'POST',
					url: c.data( 'url_ajax' ),
					data: data,
					async: true,
					success: function( response ) {
						response = $.trim( response );
						if ( response == 'ok' ) {
							if ( c.data( 'url_confirmation' ) == '' || c.data( 'url_confirmation' ) == null ) {
								c.find( '.btSubmitMessage' ).hide().html( c.data( 'message_success' ) ).fadeIn();
							} else {
								window.location = c.data( 'url_confirmation' );
							}
							
						} else {
							c.find( '.btSubmitMessage' ).hide().html( c.data( 'message_error' ) ).fadeIn();
						}
					},
					error: function( xhr, status, error ) {
						c.find( '.btSubmitMessage' ).hide().html( c.data( 'message_error' ) ).fadeIn();
					}
				});
				
				return false;
			
			});
			
			// CF 7 support
			
			c.find( '.wpcf7-submit' ).on( 'click', function() {
				
				var totelem = $( this );
				bt_quote_total( totelem.closest( '.btQuoteBooking' ) );		
				var quote = window.bt_cc_quote( c, { 'cf7': true } );
				
				c.find( 'input[name=bt-cc-data]' ).val( quote + totelem.closest( '.btQuoteBooking' ).data('total_text') + ': ' + totelem.closest( '.btQuoteBooking' ).data('total'));
			});
			
			var total = 0;
			total = total.toFixed( 2 );
			bt_quote_total( c, false );
			bt_paypal_items( c );
		});

	}
	
	window.bt_cc_quote = function( c, options = null ) {
		
		var quote = '';
		var back = 0;
		var bt_is_odd = function( n ) {
			return ( n % 2 ) == 1;
		}

		c.find( '.btQuoteBookingForm' ).find( '.btQuoteItem, .btQuoteSubtotal' ).each(function() {
			
			back++;
			
			var item_val = 0;
			var selected_name = '';
			
			var text_val;
			var is_text = false;
			
			var slider_val;
			var is_slider = false;
			
			var is_group = $( this ).closest( '.btQuoteGBlock' ).length === 1 ? true : false;
			
			var raw_value_only = false;
			
			$( this ).find( '.btQuoteText' ).each(function() {
				raw_value_only = $( this ).data( 'raw-value-only' );
				var unit_price = bt_parse_float( $( this ).data( 'price' ) );
				text_val = bt_parse_float( $( this ).val() );
				if ( raw_value_only === 'yes' ) {
					item_val = text_val;
				} else {
					item_val = text_val * unit_price;
				}
				is_text = true;
			});
			
			$( this ).find( '.btQuoteSelect' ).find( '._msddli_.selected' ).each(function() {
				raw_value_only = $( this ).closest( '.btQuoteSelect' ).data( 'raw-value-only' );
				selected_name = $( this ).find( '.ddlabel' )[0].innerHTML;
				if ( $( this ).is( ':first-child' ) ) {
					selected_name = '';
				}
				item_val = window.bt_cc_sanitize_text( $( this ).data( 'value' ) );
			});
			
			$( this ).find( '.btQuoteSlider' ).each(function() {
				raw_value_only = $( this ).data( 'raw-value-only' );
				var unit_price = bt_parse_float( $( this ).data( 'price' ) );
				var offset = bt_parse_float( $( this ).data( 'offset' ) );
				slider_val = bt_cc_get_slider_value( $( this ) );
				if ( raw_value_only === 'yes' ) {
					item_val = slider_val;
				} else {
					item_val = slider_val * unit_price + offset;
				}
				is_slider = true;
			});
			
			$( this ).find( '.btQuoteSwitch' ).each(function() {
				raw_value_only = $( this ).data( 'raw-value-only' );
				if ( $( this ).hasClass( 'on' ) ) {
					item_val = window.bt_cc_sanitize_text( $( this ).data( 'on' ) );
				} else {
					item_val = window.bt_cc_sanitize_text( $( this ).data( 'off' ) );
				}
			});
			
			var label = $( this ).find( 'label' ).html();

			if ( is_slider && raw_value_only !== 'yes' ) {
				if ( options?.cf7 === true || options?.wc === true ) {
					label = label + ' (' + slider_val + ')';
				} else {
					label = label + ': ' + slider_val;
				}
			}
			
			if ( is_text && raw_value_only !== 'yes' ) {
				if ( options?.cf7 === true || options?.wc === true ) {
					label = label + ' (' + text_val + ')';
				} else {
					label = label + ': ' + text_val;
				}
			}
			
			if ( selected_name != '' ) {
				if ( options?.wc === true ) {
					selected_name = selected_name.replace( /<span class="description">.*?<\/span>/, '' );
				}
				selected_name = selected_name.replace( '<span class="description">', '/' );
				selected_name = selected_name.replace( '</span>', '' );
				if ( options?.cf7 === true || options?.wc === true ) {
					label = label + ' (' + selected_name + ')';
				} else {
					label = label + ': ' + selected_name;
				}
			}

			var background = '';
			if ( bt_is_odd( back ) ) background = ' ' + 'style="background:#eee;"';

			if ( ! isNaN( item_val ) && item_val !== '' && raw_value_only !== 'yes' ) {
				item_val = bt_parse_float( item_val );
				item_val = window.bt_cc_currency_format( c, item_val );
			}
	
			// subtotal
			if ( $( this ).hasClass( 'btQuoteSubtotal' ) ) {
				label = $( this ).data( 'label' );
				item_val = $( this ).data( 'val' );
			}
			
			if ( label !== undefined && label !== null ) {
				if ( options?.cf7 === true ) {
					quote += label + ': ' + item_val + "\r\n";
				} else if ( options?.wc === true ) {
					quote += encodeURI( '<div>' + label + ': ' + item_val + '</div>' ) + "\r\n";
				} else {
					quote += encodeURI( '<tr' + background + '><td style="padding:.5em;">' + label + '</td><td style="text-align:right;padding:.5em;">' + item_val + '</td></tr>' ) + "\r\n";
				}
				
			}
			
		});
		
		if ( options?.wc === true ) {
			quote = encodeURI( '<div class="bt_cc_wc_quote">' ) + quote + encodeURI( '</div>' );
		}
		
		return quote;
	}
	
	// ---------------
	// Functions end
	// ---------------

	// Init
	
	var bt_cc_init_finished = false;
	
	document.addEventListener( 'readystatechange', function() { 
		if ( ! bt_cc_init_finished && ( document.readyState === 'interactive' || document.readyState === 'complete' ) ) {
			// base variables
			window.is_rtl = $( 'body' ).hasClass( 'rtl' );
			window.rtl_direction = !window.is_rtl ? 1 : -1;
			setTimeout(function() {
				bt_cc_init();
			}, 30 );
			bt_cc_init_finished = true;
		}
	});
	
})( jQuery );

var BTCaptchaCallback = function() {
	jQuery( '.g-rec' ).each(function() {
		var widget_id = grecaptcha.render( jQuery( this ).attr( 'id' ), { 'sitekey' : jQuery( this ).data( 'sk' ) } );
		jQuery( this ).data( 'widget_id', widget_id );
	});
};
