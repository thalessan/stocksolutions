/**
 * Teste da máscara (roda com: node scripts/test-form-mask.js)
 * Carrega o JS real do mu-plugin e simula digitação/backspace no DOM.
 */
const fs = require( 'fs' );
const path = require( 'path' );

const src = fs.readFileSync(
	path.join( __dirname, '..', 'web/wp-content/mu-plugins/stck-form-mask.js' ),
	'utf8'
);

const listeners = {};
const documentStub = {
	readyState: 'complete',
	addEventListener: ( type, fn ) => {
		( listeners[ type ] = listeners[ type ] || [] ).push( fn );
	},
	querySelectorAll: () => [],
};

global.document = documentStub;
eval( src ); // eslint-disable-line no-eval

function fire( type, el, extra ) {
	( listeners[ type ] || [] ).forEach( ( fn ) =>
		fn( Object.assign( { target: el, preventDefault() {} }, extra ) )
	);
}

function makeEl( value = '' ) {
	return {
		tagName: 'INPUT',
		type: 'tel',
		name: 'telephone',
		value,
		selectionStart: value.length,
		selectionEnd: value.length,
		closest: ( sel ) => ( sel === '.fusion-form' ? {} : null ),
		setSelectionRange( a, b ) {
			this.selectionStart = a;
			this.selectionEnd = b;
		},
	};
}

function type( el, text ) {
	for ( const ch of text ) {
		const pos = el.selectionStart;
		el.value = el.value.slice( 0, pos ) + ch + el.value.slice( pos );
		el.selectionStart = el.selectionEnd = pos + 1;
		fire( 'input', el );
	}
	return el.value;
}

let failures = 0;

function check( label, actual, expected ) {
	const ok = actual === expected;
	if ( ! ok ) {
		failures++;
	}
	console.log( `${ ok ? 'ok  ' : 'FAIL' } ${ label } => ${ JSON.stringify( actual ) }${ ok ? '' : ` (esperado ${ JSON.stringify( expected ) })` }` );
}

// celular, digitado dígito a dígito
let el = makeEl();
check( 'celular 11987654321', type( el, '11987654321' ), '(11) 98765-4321' );

// fixo
el = makeEl();
check( 'fixo 1133334444', type( el, '1133334444' ), '(11) 3333-4444' );

// colagem com formatação e lixo
el = makeEl( '+55 (11) 98765-4321' );
el.selectionStart = el.selectionEnd = el.value.length;
fire( 'input', el );
check( 'colagem +55 (11) 98765-4321', el.value, '(11) 98765-4321' );

// truncamento em 11 dígitos
el = makeEl();
check( 'excesso de dígitos', type( el, '119876543210000' ), '(11) 98765-4321' );

// backspace sobre o hífen remove o dígito anterior
el = makeEl( '(11) 98765-4321' );
el.selectionStart = el.selectionEnd = '(11) 98765-4321'.indexOf( '-' ) + 1;
fire( 'keydown', el, { key: 'Backspace' } );
check( 'backspace no hífen', el.value, '(11) 9876-4321' );

// backspace normal (dígito): 11 -> 10 dígitos reformata como fixo
el = makeEl( '(11) 98765-4321' );
el.value = el.value.slice( 0, -1 );
el.selectionStart = el.selectionEnd = el.value.length;
fire( 'input', el );
check( 'backspace no dígito', el.value, '(11) 9876-5432' );

// campo fora de .fusion-form não é tocado
const other = makeEl();
other.closest = () => null;
type( other, '11987654321' );
check( 'input fora do fusion-form', other.value, '11987654321' );

console.log( failures ? `\n${ failures } falha(s)` : '\ntodos os casos passaram' );
process.exit( failures ? 1 : 0 );
