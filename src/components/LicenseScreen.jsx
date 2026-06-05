import React, { useState, useEffect } from 'react';
import { validateLicense, registerScreen } from '../services/api.js';
import '../styles/LicenseScreen.css';

export default function LicenseScreen({ onSuccess, initialError }) {
  const [code, setCode] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(initialError || null);

  useEffect(() => {
    setError(initialError);
  }, [initialError]);

  async function handleSubmit(e) {
    e.preventDefault();
    const trimmed = code.trim().toUpperCase();
    if (!trimmed) {
      setError('Veuillez saisir une clé de licence.');
      return;
    }

    setLoading(true);
    setError(null);

    try {
      const validation = await validateLicense(trimmed);
      if (!validation.valid) {
        setError('Clé de licence invalide ou inactive.');
        setLoading(false);
        return;
      }

      const { token } = await registerScreen(trimmed);
      onSuccess(token);
    } catch (err) {
      setError(err.message || 'Clé de licence invalide.');
      setLoading(false);
    }
  }

  return (
    <div className="license-screen">
      <div className="license-card">
        <div className="license-logo">
          <div className="license-icon">📺</div>
          <h1>SaaS Display</h1>
          <p>Affichage dynamique</p>
        </div>

        <form onSubmit={handleSubmit} className="license-form">
          <label htmlFor="license-input">Clé de licence</label>
          <input
            id="license-input"
            type="text"
            value={code}
            onChange={(e) => setCode(e.target.value)}
            placeholder="XXXX-XXXX-XXXX-XXXX"
            disabled={loading}
            autoComplete="off"
            autoCapitalize="characters"
            spellCheck={false}
          />

          {error && <div className="error-box">{error}</div>}

          <button type="submit" disabled={loading || !code.trim()}>
            {loading ? (
              <span className="btn-loading">
                <span className="btn-spinner" />
                Vérification...
              </span>
            ) : (
              'Activer l\'écran'
            )}
          </button>
        </form>
      </div>
    </div>
  );
}
