/**
 * Máscara de telefone para os formulários Avada (campos type="tel").
 *
 * (XX) XXXX-XXXX   -> fixo (10 dígitos)
 * (XX) XXXXX-XXXX  -> celular (11 dígitos)
 *
 * Formata enquanto o usuário digita, preserva a posição do cursor e ignora
 * tudo que não for dígito (colar "(11) 91234-5678" funciona).
 */
( function () {
	'use strict';

	var MAX_DIGITS = 11;

	function onlyDigits( value ) {
		var digits = String( value || '' ).replace( /\D+/g, '' );

		// Colagem com código do país: "+55 (11) 98765-4321" -> 11987654321.
		if ( digits.length > MAX_DIGITS && '55' === digits.slice( 0, 2 ) ) {
			digits = digits.slice( 2 );
		}

		return digits.slice( 0, MAX_DIGITS );
	}

	function format( value ) {
		var digits = onlyDigits( value );

		if ( ! digits ) {
			return '';
		}

		if ( digits.length < 3 ) {
			return '(' + digits;
		}

		var area = digits.slice( 0, 2 );
		var rest = digits.slice( 2 );

		if ( rest.length <= 4 ) {
			return '(' + area + ') ' + rest;
		}

		var split = digits.length > 10 ? 5 : 4;

		return '(' + area + ') ' + rest.slice( 0, split ) + '-' + rest.slice( split );
	}

	function isPhoneField( el ) {
		return !! (
			el &&
			'INPUT' === el.tagName &&
			( 'tel' === el.type || 'telephone' === el.name ) &&
			el.closest &&
			el.closest( '.fusion-form' )
		);
	}

	function caretFor( formatted, digitCount ) {
		if ( digitCount <= 0 ) {
			return 0;
		}

		var seen = 0;

		for ( var i = 0; i < formatted.length; i++ ) {
			if ( /\d/.test( formatted.charAt( i ) ) ) {
				seen++;

				if ( seen === digitCount ) {
					return i + 1;
				}
			}
		}

		return formatted.length;
	}

	function apply( el, digits, digitsBeforeCaret ) {
		var formatted = format( digits );

		if ( el.value === formatted ) {
			return;
		}

		el.value = formatted;

		if ( el.setSelectionRange ) {
			var pos = caretFor( formatted, digitsBeforeCaret );

			try {
				el.setSelectionRange( pos, pos );
			} catch ( e ) {}
		}
	}

	function onInput( event ) {
		var el = event.target;

		if ( ! isPhoneField( el ) ) {
			return;
		}

		var start = null === el.selectionStart ? el.value.length : el.selectionStart;
		var digitsBefore = onlyDigits( el.value.slice( 0, start ) ).length;

		apply( el, onlyDigits( el.value ), digitsBefore );
	}

	function onKeyDown( event ) {
		var el = event.target;

		if ( ! isPhoneField( el ) ) {
			return;
		}

		if ( 'Backspace' !== event.key && 'Delete' !== event.key ) {
			return;
		}

		if ( el.selectionStart !== el.selectionEnd ) {
			return;
		}

		var start = el.selectionStart;
		var backward = 'Backspace' === event.key;

		// Só intercepta quando o caractere vizinho é separador: apaga o dígito antes dele.
		if ( backward ) {
			if ( start === 0 || /\d/.test( el.value.charAt( start - 1 ) ) ) {
				return;
			}
		} else if ( start >= el.value.length || /\d/.test( el.value.charAt( start ) ) ) {
			return;
		}

		event.preventDefault();

		var digits = onlyDigits( el.value );
		var digitsBefore = onlyDigits( el.value.slice( 0, start ) ).length;
		var removeAt = backward ? digitsBefore - 1 : digitsBefore;

		if ( removeAt < 0 ) {
			return;
		}

		apply( el, digits.slice( 0, removeAt ) + digits.slice( removeAt + 1 ), removeAt );
	}

	function onFocus( event ) {
		var el = event.target;

		if ( ! isPhoneField( el ) || ! el.value ) {
			return;
		}

		apply( el, onlyDigits( el.value ), onlyDigits( el.value ).length );
	}

	function init() {
		var fields = document.querySelectorAll( '.fusion-form input[type="tel"]' );

		for ( var i = 0; i < fields.length; i++ ) {
			if ( fields[ i ].value ) {
				apply( fields[ i ], onlyDigits( fields[ i ].value ), onlyDigits( fields[ i ].value ).length );
			}
		}
	}

	document.addEventListener( 'input', onInput, true );
	document.addEventListener( 'keydown', onKeyDown, true );
	document.addEventListener( 'focusin', onFocus, true );

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
