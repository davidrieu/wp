/**
 * Dashboard App Component
 */

import React, { useState, useEffect } from 'react';
import axios from 'axios';

const DashboardApp = () => {
    const [stats, setStats] = useState(null);
    const [subscription, setSubscription] = useState(null);
    const [recentJobs, setRecentJobs] = useState([]);

    useEffect(() => {
        loadDashboardData();
    }, []);

    const loadDashboardData = async () => {
        try {
            const headers = { 'X-WP-Nonce': window.teknupData.nonce };

            const [statsRes, subRes, jobsRes] = await Promise.all([
                axios.get(`${window.teknupData.apiUrl}/user/stats`, { headers }),
                axios.get(`${window.teknupData.apiUrl}/user/subscription`, { headers }),
                axios.get(`${window.teknupData.apiUrl}/jobs?per_page=5`, { headers })
            ]);

            setStats(statsRes.data);
            setSubscription(subRes.data);
            setRecentJobs(jobsRes.data.jobs);
        } catch (error) {
            console.error('Error loading dashboard data:', error);
        }
    };

    if (!stats || !subscription) {
        return <div>Chargement...</div>;
    }

    return (
        <div className="teknup-dashboard-app">
            <h1 className="teknup-heading">Dashboard</h1>

            <div className="teknup-stats-grid">
                <div className="teknup-card">
                    <h3>Plan Actuel</h3>
                    <p className="teknup-stat-number">{subscription.plan_name}</p>
                </div>

                <div className="teknup-card">
                    <h3>Jobs ce Mois</h3>
                    <p className="teknup-stat-number">{stats.monthly_jobs}</p>
                </div>

                <div className="teknup-card">
                    <h3>Quota Restant</h3>
                    <p className="teknup-stat-number">
                        {subscription.quota_remaining === 'unlimited' ? '∞' : subscription.quota_remaining}
                    </p>
                </div>

                <div className="teknup-card">
                    <h3>Total Jobs</h3>
                    <p className="teknup-stat-number">{stats.total_jobs}</p>
                </div>
            </div>

            <div className="teknup-card">
                <h2 className="teknup-heading">Jobs Récents</h2>
                {recentJobs.length === 0 ? (
                    <p>Aucun job pour le moment. Commencez par uploader votre première track !</p>
                ) : (
                    <ul className="teknup-job-list">
                        {recentJobs.map(job => (
                            <li key={job.id} className="teknup-job-item">
                                <div>
                                    <strong>{job.filename}</strong>
                                    <br />
                                    <small>{new Date(job.created_at).toLocaleString()}</small>
                                </div>
                                <span className={`teknup-badge teknup-badge-${job.status}`}>{job.status}</span>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </div>
    );
};

export default DashboardApp;
