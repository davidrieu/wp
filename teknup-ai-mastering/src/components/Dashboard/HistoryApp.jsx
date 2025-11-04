/**
 * History App Component
 */

import React, { useState, useEffect } from 'react';
import axios from 'axios';

const HistoryApp = () => {
    const [jobs, setJobs] = useState([]);
    const [loading, setLoading] = useState(true);
    const [filter, setFilter] = useState('');

    useEffect(() => {
        loadJobs();
    }, [filter]);

    const loadJobs = async () => {
        setLoading(true);
        try {
            const url = `${window.teknupData.apiUrl}/jobs${filter ? `?status=${filter}` : ''}`;
            const response = await axios.get(url, {
                headers: { 'X-WP-Nonce': window.teknupData.nonce }
            });
            setJobs(response.data.jobs);
        } catch (error) {
            console.error('Error loading jobs:', error);
        }
        setLoading(false);
    };

    const handleDownload = async (jobId) => {
        try {
            const response = await axios.get(`${window.teknupData.apiUrl}/jobs/${jobId}/download`, {
                headers: { 'X-WP-Nonce': window.teknupData.nonce }
            });
            window.location.href = response.data.download_url;
        } catch (error) {
            console.error('Download error:', error);
            alert('Erreur lors du téléchargement');
        }
    };

    const handleDelete = async (jobId) => {
        if (!confirm('Êtes-vous sûr de vouloir supprimer ce job ?')) {
            return;
        }

        try {
            await axios.delete(`${window.teknupData.apiUrl}/jobs/${jobId}`, {
                headers: { 'X-WP-Nonce': window.teknupData.nonce }
            });
            loadJobs();
        } catch (error) {
            console.error('Delete error:', error);
            alert('Erreur lors de la suppression');
        }
    };

    return (
        <div className="teknup-history-app">
            <h1 className="teknup-heading">Historique</h1>

            <div className="teknup-card">
                <div style={{marginBottom: '20px'}}>
                    <label>Filtrer par statut: </label>
                    <select value={filter} onChange={(e) => setFilter(e.target.value)}>
                        <option value="">Tous</option>
                        <option value="completed">Complétés</option>
                        <option value="processing">En cours</option>
                        <option value="failed">Échoués</option>
                    </select>
                </div>

                {loading ? (
                    <p>Chargement...</p>
                ) : jobs.length === 0 ? (
                    <p>Aucun job trouvé.</p>
                ) : (
                    <ul className="teknup-job-list">
                        {jobs.map(job => (
                            <li key={job.id} className="teknup-job-item">
                                <div>
                                    <strong>{job.filename}</strong>
                                    <br />
                                    <small>{job.file_size_formatted} • {new Date(job.created_at).toLocaleString()}</small>
                                    <br />
                                    <span className={`teknup-badge teknup-badge-${job.status}`}>{job.status}</span>
                                </div>
                                <div>
                                    {job.status === 'completed' && job.has_mastered_file && (
                                        <button className="teknup-button" onClick={() => handleDownload(job.id)}>
                                            Télécharger
                                        </button>
                                    )}
                                    <button
                                        className="teknup-button"
                                        onClick={() => handleDelete(job.id)}
                                        style={{marginLeft: '10px', background: '#666'}}
                                    >
                                        Supprimer
                                    </button>
                                </div>
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </div>
    );
};

export default HistoryApp;
