(function( wp, window ) {
if ( ! wp || ! wp.element ) {
return;
	}
const { createElement, useState, useContext, createContext, useEffect, Fragment, Children } = wp.element;

const WizardContext = createContext();

function WizardProvider( { children } ) {
const [ isOpen, setIsOpen ] = useState( false );
const [ currentStep, setCurrentStep ] = useState( 1 );

const handleOpen = () => {
setCurrentStep( 1 );
setIsOpen( true );
};
const handleClose = () => setIsOpen( false );
window.closeBusinessCaseModal = handleClose;

useEffect( () => {
if ( window.location.pathname === '/rtbcb' ) {
setCurrentStep( 1 );
setIsOpen( true );
return;
}
const params = new URLSearchParams( window.location.search );
if ( params.get( 'rtbcb_wizard' ) ) {
setCurrentStep( 1 );
setIsOpen( true );
return;
}
const openBtn = document.getElementById( 'rtbcb-open-btn' );
const openWizard = ( e ) => {
e.preventDefault();
const url = openBtn ? openBtn.href : null;
if ( url ) {
window.open( url, '_blank' );
}
};
const closeBtn = document.getElementById( 'rtbcb-close-btn' );
openBtn && openBtn.addEventListener( 'click', openWizard );
closeBtn && closeBtn.addEventListener( 'click', handleClose );
return () => {
openBtn && openBtn.removeEventListener( 'click', openWizard );
closeBtn && closeBtn.removeEventListener( 'click', handleClose );
};
}, [] );

useEffect( () => {
const overlay = document.getElementById( 'rtbcbModalOverlay' );
if ( ! overlay ) {
return;
}
if ( isOpen ) {
overlay.classList.add( 'active' );
document.body.style.overflow = 'hidden';
    if ( window.businessCaseBuilder && typeof window.businessCaseBuilder.reinitialize === 'function' ) {
      window.businessCaseBuilder.reinitialize();
    }
  } else {
    overlay.classList.remove( 'active' );
    document.body.style.overflow = '';
    if ( window.businessCaseBuilder && typeof window.businessCaseBuilder.cancelPolling === 'function' ) {
      window.businessCaseBuilder.cancelPolling();
    }
  }
  }, [ isOpen ] );

const value = {
currentStep,
setCurrentStep,
isOpen,
open: handleOpen,
close: handleClose
};

return createElement( WizardContext.Provider, { value }, children );
}

function useWizard() {
return useContext( WizardContext );
}

function Steps( { children } ) {
const { currentStep } = useWizard();
return createElement( Fragment, null,
Children.map( children, ( child, index ) =>
index + 1 === currentStep ? child : null
)
);
}

window.RTBCBWizardReact = { WizardProvider, useWizard, Steps };

function mountWizard() {
const overlay = document.getElementById( 'rtbcbModalOverlay' );
if ( ! overlay || ! wp.element ) {
return;
}

const markup = overlay.innerHTML;
const app = createElement(
WizardProvider,
null,
createElement( 'div', { dangerouslySetInnerHTML: { __html: markup } } )
);

if ( wp.element.createRoot ) {
wp.element.createRoot( overlay ).render( app );
} else if ( wp.element.render ) {
wp.element.render( app, overlay );
} else {
return;
}

        if ( window.businessCaseBuilder ) {
                window.businessCaseBuilder.form = document.getElementById( 'rtbcbForm' );
                window.businessCaseBuilder.overlay = document.getElementById( 'rtbcbModalOverlay' );
                window.businessCaseBuilder.cacheElements();
                window.businessCaseBuilder.bindEvents();
        }
}

if ( document.readyState === 'loading' ) {
document.addEventListener( 'DOMContentLoaded', mountWizard );
} else {
mountWizard();
}
})( window.wp || {}, window );
