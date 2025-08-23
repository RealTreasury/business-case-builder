import React from 'react';
import { createRoot } from 'react-dom/client';
import InventoryApp from './components/InventoryApp';

const container = document.getElementById('pit-enhanced-app');
if (container) {
    const root = createRoot(container);
    root.render(<InventoryApp />);
}

