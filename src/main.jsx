import React from 'react';
import ReactDOM from 'react-dom/client';
import { Capacitor } from '@capacitor/core';
import { CapacitorUpdater } from '@capgo/capacitor-updater';
import App from './App.jsx';
import './styles/index.css';

// Notifie le plugin que le nouveau bundle a démarré avec succès.
// Sans cet appel, le plugin annule la mise à jour au prochain redémarrage.
if (Capacitor.isNativePlatform()) {
  CapacitorUpdater.notifyAppReady();
}

ReactDOM.createRoot(document.getElementById('root')).render(
  <React.StrictMode>
    <App />
  </React.StrictMode>
);
