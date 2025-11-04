/**
 * Plans App Component - Affiche les plans d'abonnement disponibles
 */

import React, { useState, useEffect } from 'react';
import axios from 'axios';

const PlansApp = ({ subscription }) => {
    const [plans, setPlans] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        loadPlans();
    }, []);

    const loadPlans = async () => {
        try {
            setLoading(true);
            const response = await axios.get(`${window.teknupData.apiUrl}/plans`, {
                headers: {
                    'X-WP-Nonce': window.teknupData.nonce
                }
            });

            if (response.data.success) {
                setPlans(response.data.plans);
            } else {
                setError(response.data.message);
            }
        } catch (err) {
            console.error('Error loading plans:', err);
            setError('Impossible de charger les plans.');
        } finally {
            setLoading(false);
        }
    };

    const getFeatureLabel = (feature) => {
        const labels = {
            'standard_processing': 'Traitement standard',
            'all_formats': 'Tous formats audio',
            'intensity_controls': 'Contrôles d\'intensité',
            'genre_presets': 'Presets par genre',
            'unlimited_revisions': 'Révisions illimitées',
            'unlimited': 'Masters illimités',
            'priority_queue': 'File prioritaire',
            'advanced_controls': 'Contrôles avancés',
            'lufs_control': 'Contrôle LUFS',
            'batch_processing': 'Traitement par batch',
            'reference_matching': 'Reference matching',
            'api_access': 'Accès API',
            'white_label': 'White label',
            'dedicated_support': 'Support dédié',
        };

        return labels[feature] || feature;
    };

    const getLimitText = (limit) => {
        if (limit === -1) {
            return 'Masters illimités';
        }
        return `${limit} masters/mois`;
    };

    if (loading) {
        return (
            <div className="teknup-plans-app">
                <div className="teknup-card">
                    <p>Chargement des plans...</p>
                </div>
            </div>
        );
    }

    if (error) {
        return (
            <div className="teknup-plans-app">
                <div className="teknup-card teknup-error">
                    <p>{error}</p>
                </div>
            </div>
        );
    }

    return (
        <div className="teknup-plans-app">
            <h1 className="teknup-heading">Plans d'Abonnement</h1>

            {subscription && subscription.plan_slug && (
                <div className="teknup-card teknup-current-plan-info">
                    <p>
                        <strong>Plan actuel:</strong> {subscription.plan_name}
                        {subscription.quota_remaining !== 'unlimited' && (
                            <span> - {subscription.quota_remaining} masters restants ce mois</span>
                        )}
                    </p>
                </div>
            )}

            <div className="teknup-plans-grid">
                {plans.map((plan) => (
                    <div
                        key={plan.slug}
                        className={`teknup-plan-card ${plan.is_current ? 'current-plan' : ''}`}
                    >
                        {plan.is_current && (
                            <div className="teknup-plan-badge">Plan Actuel</div>
                        )}

                        <div className="teknup-plan-header">
                            <h2 className="teknup-plan-name">{plan.name}</h2>
                            <div className="teknup-plan-price">
                                {plan.price === '0' || plan.price === 0 ? (
                                    <span className="price-amount">Gratuit</span>
                                ) : (
                                    <>
                                        <span className="price-amount">{plan.price}€</span>
                                        <span className="price-period">/mois</span>
                                    </>
                                )}
                            </div>
                            <p className="teknup-plan-limit">{getLimitText(plan.limit)}</p>
                        </div>

                        <div className="teknup-plan-features">
                            <ul>
                                {plan.features.map((feature, index) => (
                                    <li key={index}>
                                        <span className="feature-icon">✓</span>
                                        {getFeatureLabel(feature)}
                                    </li>
                                ))}
                            </ul>
                        </div>

                        <div className="teknup-plan-footer">
                            {plan.is_current ? (
                                <button className="teknup-button teknup-button-secondary" disabled>
                                    Plan Actuel
                                </button>
                            ) : (
                                <a
                                    href={plan.product_url}
                                    className="teknup-button teknup-button-primary"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    {plan.price === '0' || plan.price === 0 ? 'Commencer' : 'S\'abonner'}
                                </a>
                            )}
                        </div>
                    </div>
                ))}
            </div>

            <div className="teknup-card teknup-plans-note">
                <p>
                    <strong>Note:</strong> Les abonnements se renouvellent automatiquement chaque mois.
                    Vous pouvez annuler à tout moment depuis votre compte.
                </p>
            </div>
        </div>
    );
};

export default PlansApp;
