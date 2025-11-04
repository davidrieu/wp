/**
 * Upload App Component
 */

import React, { useState, useEffect } from 'react';
import axios from 'axios';

const UploadApp = () => {
    const [subscription, setSubscription] = useState(null);
    const [file, setFile] = useState(null);
    const [uploading, setUploading] = useState(false);
    const [progress, setProgress] = useState(0);
    const [currentJob, setCurrentJob] = useState(null);
    const [settings, setSettings] = useState({
        intensity: 'medium',
        genre: 'electronic',
        lufs: -14
    });

    useEffect(() => {
        loadSubscriptionInfo();
    }, []);

    useEffect(() => {
        if (currentJob && currentJob.status !== 'completed' && currentJob.status !== 'failed') {
            const interval = setInterval(() => {
                checkJobStatus(currentJob.id);
            }, 2000);

            return () => clearInterval(interval);
        }
    }, [currentJob]);

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

    const handleFileSelect = (e) => {
        const selectedFile = e.target.files[0];
        if (selectedFile) {
            setFile(selectedFile);
        }
    };

    const handleUpload = async () => {
        if (!file) return;

        setUploading(true);
        setProgress(0);

        const formData = new FormData();
        formData.append('file', file);
        formData.append('intensity', settings.intensity);
        formData.append('genre', settings.genre);
        if (subscription?.features?.includes('lufs_control')) {
            formData.append('lufs', settings.lufs);
        }

        try {
            const response = await axios.post(
                `${window.teknupData.apiUrl}/upload`,
                formData,
                {
                    headers: {
                        'X-WP-Nonce': window.teknupData.nonce,
                        'Content-Type': 'multipart/form-data'
                    },
                    onUploadProgress: (progressEvent) => {
                        const percentCompleted = Math.round((progressEvent.loaded * 100) / progressEvent.total);
                        setProgress(percentCompleted);
                    }
                }
            );

            setCurrentJob(response.data.job);
            setUploading(false);
            setFile(null);
        } catch (error) {
            console.error('Upload error:', error);
            alert(error.response?.data?.message || 'Erreur lors de l\'upload');
            setUploading(false);
        }
    };

    const checkJobStatus = async (jobId) => {
        try {
            const response = await axios.get(`${window.teknupData.apiUrl}/jobs/${jobId}`, {
                headers: {
                    'X-WP-Nonce': window.teknupData.nonce
                }
            });
            setCurrentJob(response.data.job);
        } catch (error) {
            console.error('Error checking job status:', error);
        }
    };

    const handleDownload = async () => {
        try {
            const response = await axios.get(`${window.teknupData.apiUrl}/jobs/${currentJob.id}/download`, {
                headers: {
                    'X-WP-Nonce': window.teknupData.nonce
                }
            });
            window.location.href = response.data.download_url;
        } catch (error) {
            console.error('Download error:', error);
        }
    };

    return (
        <div className="teknup-upload-app">
            {subscription && (
                <div className="teknup-card">
                    <h2 className="teknup-heading">Votre Plan: {subscription.plan_name}</h2>
                    <p>
                        Quota: {subscription.quota_remaining === 'unlimited' ? 'Illimité' : `${subscription.usage} / ${subscription.limit}`}
                    </p>
                </div>
            )}

            {!currentJob && (
                <div className="teknup-card">
                    <h2 className="teknup-heading">Uploader votre Track</h2>

                    <div className="teknup-upload-zone">
                        <input
                            type="file"
                            accept=".wav,.mp3,.flac,.aiff,.aif"
                            onChange={handleFileSelect}
                            disabled={uploading}
                        />
                        {file && <p>Fichier sélectionné: {file.name}</p>}
                    </div>

                    <div className="teknup-settings">
                        <h3>Paramètres</h3>

                        <label>
                            Intensité:
                            <select value={settings.intensity} onChange={(e) => setSettings({...settings, intensity: e.target.value})}>
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                            </select>
                        </label>

                        <label>
                            Genre:
                            <select value={settings.genre} onChange={(e) => setSettings({...settings, genre: e.target.value})}>
                                <option value="electronic">Electronic</option>
                                <option value="techno">Techno</option>
                                <option value="house">House</option>
                                <option value="trance">Trance</option>
                            </select>
                        </label>

                        {subscription?.features?.includes('lufs_control') && (
                            <label>
                                LUFS Target:
                                <input
                                    type="number"
                                    value={settings.lufs}
                                    onChange={(e) => setSettings({...settings, lufs: parseFloat(e.target.value)})}
                                    min="-30"
                                    max="0"
                                    step="0.1"
                                />
                            </label>
                        )}
                    </div>

                    {uploading && (
                        <div className="teknup-progress-bar">
                            <div className="teknup-progress-bar-fill" style={{width: `${progress}%`}}></div>
                        </div>
                    )}

                    <button
                        className="teknup-button"
                        onClick={handleUpload}
                        disabled={!file || uploading || !subscription?.can_upload}
                    >
                        {uploading ? `Upload en cours... ${progress}%` : 'Démarrer le Mastering'}
                    </button>
                </div>
            )}

            {currentJob && (
                <div className="teknup-card">
                    <h2 className="teknup-heading">Job #{currentJob.id}</h2>
                    <p>Fichier: {currentJob.filename}</p>
                    <p>Statut: <span className={`teknup-badge teknup-badge-${currentJob.status}`}>{currentJob.status}</span></p>

                    {currentJob.status === 'completed' && (
                        <button className="teknup-button" onClick={handleDownload}>
                            Télécharger le Master
                        </button>
                    )}

                    {currentJob.status === 'failed' && (
                        <p style={{color: 'red'}}>Erreur: {currentJob.error_message}</p>
                    )}

                    <button className="teknup-button" onClick={() => setCurrentJob(null)}>
                        Nouveau Mastering
                    </button>
                </div>
            )}
        </div>
    );
};

export default UploadApp;
