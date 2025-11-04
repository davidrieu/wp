/**
 * Teknup AI Mastering - React App Entry Point
 */

import React from 'react';
import { createRoot } from 'react-dom/client';
import UploadApp from './components/Upload/UploadApp';
import DashboardApp from './components/Dashboard/DashboardApp';
import HistoryApp from './components/Dashboard/HistoryApp';

// Initialiser l'app d'upload
const uploadContainer = document.getElementById('teknup-upload-app');
if (uploadContainer) {
    const root = createRoot(uploadContainer);
    root.render(<UploadApp />);
}

// Initialiser l'app dashboard
const dashboardContainer = document.getElementById('teknup-dashboard-app');
if (dashboardContainer) {
    const root = createRoot(dashboardContainer);
    root.render(<DashboardApp />);
}

// Initialiser l'app historique
const historyContainer = document.getElementById('teknup-history-app');
if (historyContainer) {
    const root = createRoot(historyContainer);
    root.render(<HistoryApp />);
}
