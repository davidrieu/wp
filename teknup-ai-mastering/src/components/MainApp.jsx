/**
 * Main App Component with Navigation
 */

import React, { useState, useEffect } from 'react';
import axios from 'axios';
import UploadApp from './Upload/UploadApp';
import DashboardApp from './Dashboard/DashboardApp';
import HistoryApp from './Dashboard/HistoryApp';

const MainApp = () => {
    const [activeView, setActiveView] = useState('dashboard');
    const [subscription, setSubscription] = useState(null);

    useEffect(() => {
        loadSubscriptionInfo();
    }, []);

    const loadSubscriptionInfo = async () => {
        try {
            const response = await axios.get(`${window.teknupData.apiUrl}/user/subscription`, {
                headers: {
                    'X-WP-Nonce': window.teknupData.nonce
                }
            });
            setSubscription(response.data);
        } catch (error) {
            console.error('Error loading subscription:', error);
        }
    };

    const renderView = () => {
        switch (activeView) {
            case 'upload':
                return <UploadApp subscription={subscription} onNavigate={setActiveView} />;
            case 'history':
                return <HistoryApp onNavigate={setActiveView} />;
            case 'dashboard':
            default:
                return <DashboardApp subscription={subscription} onNavigate={setActiveView} />;
        }
    };

    return (
        <div className="teknup-main-app">
            {/* Header avec logo et info abonnement */}
            <div className="teknup-app-header">
                <div className="teknup-header-content">
                    <h1 className="teknup-heading">TEKNUP AI MASTERING</h1>
                    {subscription && (
                        <div className="teknup-header-info">
                            <span className="teknup-plan-badge">
                                {subscription.plan_name}
                            </span>
                            <span className="teknup-quota-info">
                                {subscription.quota_remaining === 'unlimited'
                                    ? '∞ Masters'
                                    : `${subscription.quota_remaining} restants`}
                            </span>
                        </div>
                    )}
                </div>
            </div>

            {/* Navigation */}
            <nav className="teknup-navigation">
                <button
                    className={`teknup-nav-button ${activeView === 'dashboard' ? 'active' : ''}`}
                    onClick={() => setActiveView('dashboard')}
                >
                    <span className="teknup-nav-icon">📊</span>
                    Dashboard
                </button>
                <button
                    className={`teknup-nav-button ${activeView === 'upload' ? 'active' : ''}`}
                    onClick={() => setActiveView('upload')}
                >
                    <span className="teknup-nav-icon">⬆️</span>
                    Upload
                </button>
                <button
                    className={`teknup-nav-button ${activeView === 'history' ? 'active' : ''}`}
                    onClick={() => setActiveView('history')}
                >
                    <span className="teknup-nav-icon">📜</span>
                    Historique
                </button>
            </nav>

            {/* Vue active */}
            <div className="teknup-app-content">
                {renderView()}
            </div>
        </div>
    );
};

export default MainApp;
