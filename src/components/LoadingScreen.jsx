import React from 'react';
import '../styles/LoadingScreen.css';

export default function LoadingScreen({ message = 'Chargement...' }) {
  return (
    <div className="loading-screen">
      <div className="spinner" />
      <p>{message}</p>
    </div>
  );
}
