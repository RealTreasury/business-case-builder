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

useEffect( () => {
const openBtn = document.getElementById( 'rtbcb-open-btn' );
const closeBtn = document.getElementById( 'rtbcb-close-btn' );
openBtn && openBtn.addEventListener( 'click', handleOpen );
closeBtn && closeBtn.addEventListener( 'click', handleClose );
return () => {
openBtn && openBtn.removeEventListener( 'click', handleOpen );
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
})( window.wp || {}, window );
