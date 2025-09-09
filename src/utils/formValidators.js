const validation = require('../../public/js/wizard-validation.js');

const { validateEmail, validateRequired, requirePainPoints } = validation;

function validatePainPoints(values) {
return requirePainPoints(values);
}

const exportsObj = { validateEmail, validatePainPoints, validateRequired, requirePainPoints };

if (typeof module !== 'undefined' && module.exports) {
module.exports = exportsObj;
} else if (typeof window !== 'undefined') {
window.RTBCBValidators = exportsObj;
}
