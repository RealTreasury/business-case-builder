const assert = require('assert');

const generateButton = { disabled: true };

function handleSettingsSave(response) {
    if (response.success && response.data && response.data.api_valid) {
        generateButton.disabled = false;
    }
}

handleSettingsSave({ success: true, data: { api_valid: true } });
assert.strictEqual(generateButton.disabled, false);
console.log('dashboard-api-key-button.test.js passed');
