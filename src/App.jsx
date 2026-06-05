import React, { useState, useEffect } from 'react';
import LicenseScreen from './components/LicenseScreen.jsx';
import CatalogScreen from './components/CatalogScreen.jsx';
import LoadingScreen from './components/LoadingScreen.jsx';
import { getToken, saveToken, clearToken } from './services/storage.js';
import { fetchCatalog, sendOffline } from './services/api.js';

const SCREEN = { LOADING: 'loading', LICENSE: 'license', CATALOG: 'catalog' };

export default function App() {
  const [screen, setScreen] = useState(SCREEN.LOADING);
  const [token, setToken] = useState(null);
  const [catalogData, setCatalogData] = useState(null);
  const [licenseError, setLicenseError] = useState(null);

  useEffect(() => {
    const saved = getToken();
    if (saved) {
      loadCatalog(saved);
    } else {
      setScreen(SCREEN.LICENSE);
    }
  }, []);

  // Mark screen offline when app closes
  useEffect(() => {
    const handleUnload = () => {
      if (token) sendOffline(token).catch(() => {});
    };
    window.addEventListener('beforeunload', handleUnload);
    return () => window.removeEventListener('beforeunload', handleUnload);
  }, [token]);

  async function loadCatalog(t) {
    setScreen(SCREEN.LOADING);
    setLicenseError(null);
    try {
      const data = await fetchCatalog(t);
      setToken(t);
      setCatalogData(data);
      setScreen(SCREEN.CATALOG);
    } catch (err) {
      clearToken();
      setLicenseError(err.message);
      setScreen(SCREEN.LICENSE);
    }
  }

  function handleLicenseSuccess(newToken) {
    saveToken(newToken);
    loadCatalog(newToken);
  }

  function handleLogout() {
    if (token) sendOffline(token).catch(() => {});
    clearToken();
    setToken(null);
    setCatalogData(null);
    setScreen(SCREEN.LICENSE);
  }

  if (screen === SCREEN.LOADING) return <LoadingScreen />;

  if (screen === SCREEN.LICENSE) {
    return <LicenseScreen onSuccess={handleLicenseSuccess} initialError={licenseError} />;
  }

  return (
    <CatalogScreen
      token={token}
      data={catalogData}
      onLogout={handleLogout}
      onRefresh={() => loadCatalog(token)}
    />
  );
}
