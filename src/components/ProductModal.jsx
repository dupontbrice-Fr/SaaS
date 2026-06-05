import React from 'react';
import '../styles/ProductModal.css';

export default function ProductModal({ product, onClose }) {
  if (!product) return null;

  return (
    <div className="modal-overlay" onClick={onClose}>
      <div className="modal-content" onClick={(e) => e.stopPropagation()}>
        {product.thumbnail_url && (
          <img
            src={product.thumbnail_url}
            alt={product.name}
            className="modal-image"
          />
        )}
        <div className="modal-body">
          <h2 className="modal-title">{product.name}</h2>
          {product.price && (
            <p className="modal-price">
              {parseFloat(product.price).toFixed(2)} €
            </p>
          )}
          {product.description && (
            <p className="modal-description">{product.description}</p>
          )}
          {product.reference && (
            <p className="modal-ref">Réf. {product.reference}</p>
          )}
        </div>
        <button className="modal-close-btn" onClick={onClose}>
          Fermer
        </button>
      </div>
    </div>
  );
}
