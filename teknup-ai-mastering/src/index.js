/**
 * Teknup AI Mastering - React App Entry Point
 */

import React from 'react';
import { createRoot } from 'react-dom/client';
import MainApp from './components/MainApp';
import UploadApp from './components/Upload/UploadApp';
import DashboardApp from './components/Dashboard/DashboardApp';
import HistoryApp from './components/Dashboard/HistoryApp';

// Initialiser l'app principale avec navigation (shortcode [teknup_app])
const mainAppContainer = document.getElementById('teknup-main-app-root');
if (mainAppContainer) {
    const root = createRoot(mainAppContainer);
    root.render(<MainApp />);
}

// Initialiser l'app d'upload (shortcode [teknup_upload])
const uploadContainer = document.getElementById('teknup-upload-app');
if (uploadContainer) {
    const root = createRoot(uploadContainer);
    root.render(<UploadApp />);
}

// Initialiser l'app dashboard (shortcode [teknup_dashboard])
const dashboardContainer = document.getElementById('teknup-dashboard-app');
if (dashboardContainer) {
    const root = createRoot(dashboardContainer);
    root.render(<DashboardApp />);
}

// Initialiser l'app historique (shortcode [teknup_history])
const historyContainer = document.getElementById('teknup-history-app');
if (historyContainer) {
    const root = createRoot(historyContainer);
    root.render(<HistoryApp />);
}
