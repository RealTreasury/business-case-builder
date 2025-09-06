function validateEmail(email) {
        if (typeof email !== 'string') {
                return false;
        }
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email.trim());
}

function validateRequired(value) {
        if (Array.isArray(value)) {
                return value.filter((v) => v !== undefined && v !== null && String(v).trim() !== '').length > 0;
        }
        return value !== undefined && value !== null && String(value).trim() !== '';
}

function validatePainPoints(values) {
        return Array.isArray(values) && values.length > 0;
}

if (typeof module !== 'undefined' && module.exports) {
        module.exports = { validateEmail, validatePainPoints, validateRequired };
} else if (typeof window !== 'undefined') {
        window.RTBCBValidators = { validateEmail, validatePainPoints, validateRequired };
}
