(function( window, React, ReactDOM ) {
    const { useState, createContext, useContext } = React;
    const { __ } = window.wp && window.wp.i18n ? window.wp.i18n : { __: ( s ) => s };

    const WizardContext = createContext();

    function WizardProvider( { children } ) {
        const [ isOpen, setIsOpen ] = useState( false );
        return React.createElement(
            WizardContext.Provider,
            { value: { isOpen, setIsOpen } },
            children
        );
    }

    function Step( { step, currentStep, children } ) {
        if ( step !== currentStep ) {
            return null;
        }
        return React.createElement(
            'div',
            { className: 'rtbcb-wizard-step', 'data-step': step },
            children
        );
    }

    function WizardForm() {
        const { isOpen, setIsOpen } = useContext( WizardContext );
        const [ currentStep, setCurrentStep ] = useState( 1 );
        const [ formData, setFormData ] = useState( {} );

        if ( ! isOpen ) {
            return React.createElement(
                'button',
                {
                    type: 'button',
                    className: 'rtbcb-trigger-btn',
                    onClick: () => setIsOpen( true )
                },
                __( 'Build Your Business Case', 'rtbcb' )
            );
        }

        const handleChange = ( e ) => {
            setFormData( { ...formData, [ e.target.name ]: e.target.value } );
        };

        const next = () => setCurrentStep( ( s ) => s + 1 );
        const prev = () => setCurrentStep( ( s ) => Math.max( 1, s - 1 ) );

        return React.createElement(
            'div',
            { id: 'rtbcbModalOverlay', className: 'active' },
            React.createElement(
                'form',
                { id: 'rtbcbForm', className: 'rtbcb-form rtbcb-wizard', method: 'post' },
                React.createElement(
                    'div',
                    { className: 'rtbcb-wizard' },
                    React.createElement(
                        Step,
                        { step: 1, currentStep },
                        React.createElement( 'input', {
                            name: 'company_name',
                            value: formData.company_name || '',
                            onChange: handleChange
                        } )
                    ),
                    React.createElement(
                        Step,
                        { step: 2, currentStep },
                        React.createElement( 'input', {
                            name: 'email',
                            type: 'email',
                            value: formData.email || '',
                            onChange: handleChange
                        } )
                    ),
                    React.createElement(
                        'div',
                        { className: 'rtbcb-wizard-navigation' },
                        React.createElement(
                            'button',
                            {
                                type: 'button',
                                className: 'rtbcb-nav-prev',
                                onClick: prev,
                                disabled: currentStep === 1
                            },
                            __( 'Previous', 'rtbcb' )
                        ),
                        React.createElement(
                            'button',
                            {
                                type: 'button',
                                className: 'rtbcb-nav-next',
                                onClick: next
                            },
                            __( 'Next', 'rtbcb' )
                        ),
                        React.createElement(
                            'button',
                            {
                                type: 'button',
                                className: 'rtbcb-nav-close',
                                onClick: () => setIsOpen( false )
                            },
                            __( 'Close', 'rtbcb' )
                        )
                    )
                )
            )
        );
    }

    function WizardApp() {
        return React.createElement( WizardProvider, null, React.createElement( WizardForm ) );
    }

    window.renderRTBCBWizardForm = function( containerId ) {
        const container = document.getElementById( containerId );
        if ( ! container ) {
            return;
        }
        ReactDOM.render( React.createElement( WizardApp ), container );
    };
})( window, window.React, window.ReactDOM );
