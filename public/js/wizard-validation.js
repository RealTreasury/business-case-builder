/**
 * Reusable validation helpers for the Business Case Builder wizard.
 * Centralizes all validation rules so step transitions and submissions
 * share the same logic.
 */
( function( global ) {
const __ = ( typeof wp !== 'undefined' && wp.i18n && wp.i18n.__ ) ? wp.i18n.__ : ( s ) => s;

function validateEmail( email ) {
if ( typeof email !== 'string' ) {
return false;
}
const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
return emailRegex.test( email.trim() );
}

function validateRequired( value ) {
if ( Array.isArray( value ) ) {
return value.filter( ( v ) => v !== undefined && v !== null && String( v ).trim() !== '' ).length > 0;
}
return value !== undefined && value !== null && String( value ).trim() !== '';
}

function requirePainPoints( values ) {
return Array.isArray( values ) && values.length > 0;
}

function validateCompanyName( value ) {
if ( ! value ) {
return '';
}
if ( value.length < 2 ) {
return __( 'Company name must be at least 2 characters', 'rtbcb' );
}
if ( value.length > 100 ) {
return __( 'Company name must be less than 100 characters', 'rtbcb' );
}
if ( /^[^a-zA-Z]*$/.test( value ) ) {
return __( 'Please enter a valid company name', 'rtbcb' );
}
return '';
}

function validateNumber( value ) {
if ( value && ! Number.isFinite( Number( value ) ) ) {
return __( 'Please enter a valid number', 'rtbcb' );
}
return '';
}

function validateField( field, { form, forceRequired = false } = {} ) {
const value = field.value.trim();
let message = '';
let valid = true;
let group = null;

if ( field.type === 'radio' ) {
group = form ? form.querySelectorAll( `input[name="${ field.name }"]` ) : [];
const checked = Array.from( group ).some( ( radio ) => radio.checked );
if ( ! checked ) {
message = __( 'This field is required', 'rtbcb' );
valid = false;
}
return { valid, message, group };
}

if ( forceRequired || field.hasAttribute( 'required' ) ) {
if ( field.type === 'checkbox' ) {
const values = Array.from( form.querySelectorAll( `[name="${ field.name }"]:checked` ) ).map( ( el ) => el.value );
if ( ! validateRequired( values ) ) {
message = __( 'This field is required', 'rtbcb' );
valid = false;
}
} else if ( ! validateRequired( value ) ) {
message = __( 'This field is required', 'rtbcb' );
valid = false;
}
}

if ( valid && field.name === 'company_name' ) {
message = validateCompanyName( value );
if ( message ) {
valid = false;
}
}

if ( valid && field.type === 'email' && value ) {
if ( ! validateEmail( value ) ) {
message = __( 'Please enter a valid email address', 'rtbcb' );
valid = false;
}
}

if ( valid && field.type === 'number' && value ) {
message = validateNumber( value );
if ( message ) {
valid = false;
}
}

return { valid, message };
}

function validateStep( stepNumber, { form, reportType, getStepFields } ) {
const currentFields = getStepFields ? ( getStepFields( stepNumber ) || [] ) : [];
let isValid = true;
let stepError = null;
const fieldErrors = [];

if ( currentFields.includes( 'pain_points' ) ) {
const checkedValues = Array.from( form.querySelectorAll( 'input[name="pain_points[]"]:checked' ) ).map( ( cb ) => cb.value );
if ( ! requirePainPoints( checkedValues ) ) {
  stepError = __( 'Please select at least one challenge.', 'rtbcb' );
  isValid = false;
}
}

for ( const fieldName of currentFields ) {
const field = form.querySelector( `[name="${ fieldName }"]` );
if ( ! field ) {
continue;
}
if ( reportType === 'basic' && field.closest( '.rtbcb-enhanced-only' ) ) {
continue;
}
if ( reportType === 'enhanced' && field.closest( '.rtbcb-enhanced-only' ) && ! field.hasAttribute( 'required' ) ) {
continue;
}
const { valid } = validateField( field, { form, forceRequired: true } );
if ( ! valid ) {
isValid = false;
const fieldContainer = field.closest( '.rtbcb-field' );
const label = fieldContainer ? fieldContainer.querySelector( 'label' ) : null;
fieldErrors.push( label ? label.textContent.trim() : fieldName );
}
}

return { valid: isValid, stepError, fieldErrors };
}

function validateFormData( formData, { form, reportType, totalSteps, getStepFields } ) {
const getValue = ( field ) => {
if ( typeof formData.get === 'function' ) {
return formData.get( field );
}
for ( const [ k, v ] of formData.entries() ) {
if ( k === field ) {
return v;
}
}
return null;
};

const getAllValues = ( field ) => {
if ( typeof formData.getAll === 'function' ) {
const values = formData.getAll( field );
return Array.isArray( values ) ? values : [];
}
const values = [];
for ( const [ k, v ] of formData.entries() ) {
if ( k === field ) {
values.push( v );
}
}
return values;
};

const requiredFields = new Set();
const arrayFields = new Set();
for ( let step = 1; step <= totalSteps; step++ ) {
const fields = getStepFields ? ( getStepFields( step ) || [] ) : [];
fields.forEach( ( field ) => {
const baseName = field.replace( /\[\]$/, '' );
requiredFields.add( baseName );
if ( field.endsWith( '[]' ) || baseName === 'pain_points' ) {
arrayFields.add( baseName );
}
} );
}

if ( reportType !== 'basic' ) {
[ 'hours_reconciliation', 'hours_cash_positioning', 'num_banks', 'ftes' ].forEach( ( field ) => {
if ( form.querySelector( `[name="${ field }"]` ) ) {
requiredFields.add( field );
}
} );
}

for ( const field of requiredFields ) {
if ( field === 'pain_points' ) {
const painValues = getAllValues( 'pain_points[]' );
if ( ! requirePainPoints( painValues.filter( ( v ) => v ) ) ) {
  throw new Error( __( 'Please select at least one challenge.', 'rtbcb' ) );
}
continue;
}
const fieldElement = form ? ( form.querySelector( `[name="${ field }"]` ) || form.querySelector( `[name="${ field }[]"]` ) ) : null;
if ( ! fieldElement ) {
continue;
}
if ( arrayFields.has( field ) ) {
const values = getAllValues( `${ field }[]` ).filter( ( v ) => v );
if ( ! validateRequired( values ) ) {
throw new Error( `${ __( 'Missing required field:', 'rtbcb' ) } ${ field.replace( '_', ' ' ) }` );
}
continue;
}
if ( ! validateRequired( getValue( field ) ) ) {
throw new Error( `${ __( 'Missing required field:', 'rtbcb' ) } ${ field.replace( '_', ' ' ) }` );
}
}

if ( requiredFields.has( 'email' ) ) {
const email = getValue( 'email' );
if ( ! validateEmail( email ) ) {
throw new Error( __( 'Please enter a valid email address', 'rtbcb' ) );
}
}

if ( reportType !== 'basic' ) {
const numericFields = [ 'hours_reconciliation', 'hours_cash_positioning', 'num_banks', 'ftes' ];
for ( const field of numericFields ) {
if ( requiredFields.has( field ) ) {
const value = parseFloat( getValue( field ) );
if ( isNaN( value ) || value <= 0 ) {
throw new Error( `${ field.replace( '_', ' ' ) } ${ __( 'must be a positive number', 'rtbcb' ) }` );
}
}
}
}
}

const api = {
validateEmail,
validateRequired,
requirePainPoints,
validateField,
validateStep,
validateFormData,
};

if ( typeof module !== 'undefined' && module.exports ) {
module.exports = api;
} else {
global.RTBCBWizardValidation = api;
}
} )( typeof window !== 'undefined' ? window : globalThis );

