import React from 'react';
import '../styles/ProductModal.css';

export default function ProductModal({ product, onClose }) {
  if (!product) return null;

  const mime = product.file_mime || '';
  const isVideo = mime.startsWith('video/');
  const isImage = mime.startsWith('image/') || (!mime && !!(product.file_url || product.thumbnail_url));
  const isUrl = product.file_type === 'url' && product.url;
  const isPdf = mime === 'application/pdf';

  const mediaUrl = product.file_url || product.thumbnail_url;
  const showTitle = parseInt(product.show_title ?? 1) !== 0;

  const hasInfo = (showTitle && product.name) || product.price || product.description || product.reference || isUrl;

  function handleOpenUrl() {
    window.open(product.url, '_blank', 'noopener,noreferrer');
  }

  return (
    <div className="product-modal-fs">
      <button className="product-modal-close-btn" onClick={onClose} aria-label="Fermer">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"/>
          <line x1="6" y1="6" x2="18" y2="18"/>
        </svg>
      </button>

      <div className="product-modal-media">
        {isVideo && mediaUrl ? (
          <video
            src={mediaUrl}
            className="product-modal-video"
            autoPlay
            controls
            playsInline
          />
        ) : isPdf && mediaUrl ? (
          <>
            {product.thumbnail_url
              ? <img src={product.thumbnail_url} alt={product.name} className="product-modal-image" />
              : <div className="product-modal-placeholder">📄</div>
            }
          </>
        ) : mediaUrl ? (
          <img src={mediaUrl} alt={product.name} className="product-modal-image" />
        ) : (
          <div className="product-modal-placeholder">🖼</div>
        )}
      </div>

      {hasInfo && (
        <div className={`product-modal-info${isUrl ? ' has-url-btn' : ''}`}>
          {showTitle && product.name && (
            <h2 className="product-modal-title">{product.name}</h2>
          )}
          {product.price && (
            <p className="product-modal-price">{parseFloat(product.price).toFixed(2)} €</p>
          )}
          {product.description && (
            <p className="product-modal-desc">{product.description}</p>
          )}
          {product.reference && (
            <p className="product-modal-ref">Réf. {product.reference}</p>
          )}
          {isUrl && (
            <button className="product-modal-url-btn" onClick={handleOpenUrl}>
              🌐 Ouvrir le site
            </button>
          )}
        </div>
      )}
    </div>
  );
}
