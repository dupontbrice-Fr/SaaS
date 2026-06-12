import React, { useState } from 'react';
import BannerSlider from './BannerSlider.jsx';
import ProductModal from './ProductModal.jsx';
import { useHeartbeat } from '../hooks/useHeartbeat.js';
import { trackClick } from '../services/api.js';
import '../styles/CatalogScreen.css';

export default function CatalogScreen({ token, data, onLogout, onRefresh }) {
  // navStack: array of { id, name } — empty = root
  const [navStack, setNavStack] = useState([]);
  const [selectedProduct, setSelectedProduct] = useState(null);
  const [refreshing, setRefreshing] = useState(false);

  useHeartbeat(token);

  const {
    categories = [],
    root_products = [],
    banners = [],
    settings = {},
    screen = {},
  } = data || {};

  const currentCatId = navStack.length > 0 ? navStack[navStack.length - 1].id : null;

  // Categories visible at the current level
  const visibleCategories =
    currentCatId === null
      ? categories.filter((c) => !c.parent_id)
      : categories.filter((c) => Number(c.parent_id) === Number(currentCatId));

  // Products visible at the current level
  const visibleProducts =
    currentCatId === null
      ? root_products
      : (categories.find((c) => Number(c.id) === Number(currentCatId))?.products ?? []);

  function navigateTo(cat) {
    setNavStack((prev) => [...prev, { id: cat.id, name: cat.name }]);
    trackClick(token, 'category', cat.id, cat.name).catch(() => {});
  }

  function navigateBack() {
    setNavStack((prev) => prev.slice(0, -1));
  }

  function handleProductClick(product) {
    setSelectedProduct(product);
    trackClick(token, 'product', product.id, product.name).catch(() => {});
  }

  async function handleRefresh() {
    setRefreshing(true);
    await onRefresh();
    setRefreshing(false);
  }

  const storeName = settings?.store_name || screen?.name || 'Catalogue';
  const currentLevelName =
    navStack.length > 0 ? navStack[navStack.length - 1].name : storeName;

  const isEmpty = visibleCategories.length === 0 && visibleProducts.length === 0;

  return (
    <div className="catalog-screen">
      <header className="catalog-header">
        <div className="header-left">
          {navStack.length > 0 && (
            <button className="back-btn" onClick={navigateBack} aria-label="Retour">
              <svg width="22" height="22" viewBox="0 0 24 24" fill="none"
                   stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round">
                <polyline points="15 18 9 12 15 6" />
              </svg>
            </button>
          )}
          <h1 className="catalog-title">{currentLevelName}</h1>
        </div>
        <div className="header-actions">
          <button
            className="icon-btn"
            onClick={handleRefresh}
            disabled={refreshing}
            aria-label="Actualiser"
          >
            <span className={refreshing ? 'spin' : ''}>↻</span>
          </button>
          <button className="icon-btn logout-btn" onClick={onLogout} aria-label="Déconnecter">
            ✕
          </button>
        </div>
      </header>

      {/* Banners only at root level */}
      {navStack.length === 0 && banners.length > 0 && (
        <BannerSlider banners={banners} />
      )}

      <main className="catalog-content">
        {isEmpty ? (
          <div className="empty-state">
            <p>Aucun contenu disponible.</p>
          </div>
        ) : (
          <>
            {/* Category cards */}
            {visibleCategories.length > 0 && (
              <div className="categories-grid">
                {visibleCategories.map((cat) => (
                  <div
                    key={cat.id}
                    className="category-card"
                    onClick={() => navigateTo(cat)}
                  >
                    {cat.image_url ? (
                      <img
                        src={cat.image_url}
                        alt={cat.name}
                        className="category-card-img"
                        loading="lazy"
                      />
                    ) : (
                      <div className="category-card-placeholder">
                        {cat.name.slice(0, 2).toUpperCase()}
                      </div>
                    )}
                    <div className="category-card-label">{cat.name}</div>
                    <div className="category-card-arrow">›</div>
                  </div>
                ))}
              </div>
            )}

            {/* Product grid */}
            {visibleProducts.length > 0 && (
              <div className="products-grid">
                {visibleProducts.map((product) => (
                  <div
                    key={product.id}
                    className="product-card"
                    onClick={() => handleProductClick(product)}
                  >
                    {product.thumbnail_url ? (
                      <img
                        src={product.thumbnail_url}
                        alt={product.name}
                        className="product-thumb"
                        loading="lazy"
                      />
                    ) : (
                      <div className="product-thumb-placeholder">🖼</div>
                    )}
                    {parseInt(product.show_title ?? 1) !== 0 && (
                      <div className="product-info">
                        <h3 className="product-name">{product.name}</h3>
                        {product.price && (
                          <p className="product-price">
                            {parseFloat(product.price).toFixed(2)} €
                          </p>
                        )}
                      </div>
                    )}
                  </div>
                ))}
              </div>
            )}
          </>
        )}
      </main>

      <ProductModal
        product={selectedProduct}
        onClose={() => setSelectedProduct(null)}
      />
    </div>
  );
}
