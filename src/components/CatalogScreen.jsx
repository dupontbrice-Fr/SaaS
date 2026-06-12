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
  const currentCat = currentCatId !== null
    ? categories.find((c) => Number(c.id) === Number(currentCatId)) ?? null
    : null;

  // Merged + position-sorted items at the current navigation level
  const visibleItems = (() => {
    const cats = (currentCatId === null
      ? categories.filter((c) => !c.parent_id)
      : categories.filter((c) => Number(c.parent_id) === Number(currentCatId))
    ).map((c) => ({ ...c, _type: 'category', _pos: Number(c.position) || 0 }));

    const prods = (currentCatId === null
      ? root_products
      : (currentCat?.products ?? [])
    ).map((p) => ({ ...p, _type: 'product', _pos: Number(p.position) || 0 }));

    return [...cats, ...prods].sort((a, b) => a._pos - b._pos);
  })();

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

  const isEmpty = visibleItems.length === 0;

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

      {/* Banners at root level */}
      {navStack.length === 0 && banners.length > 0 && (
        <BannerSlider banners={banners} />
      )}

      {/* Category image banner when navigating inside a category */}
      {navStack.length > 0 && currentCat?.image_url && (
        <div className="category-banner">
          <img
            src={currentCat.image_url}
            alt={currentLevelName}
            className="category-banner-img"
          />
        </div>
      )}

      <main className="catalog-content">
        {isEmpty ? (
          <div className="empty-state">
            <p>Aucun contenu disponible.</p>
          </div>
        ) : (
          <div className="content-grid">
            {visibleItems.map((item) =>
              item._type === 'category' ? (
                <div
                  key={`cat-${item.id}`}
                  className="category-card"
                  onClick={() => navigateTo(item)}
                >
                  {item.image_url ? (
                    <img
                      src={item.image_url}
                      alt={item.name}
                      className="category-card-img"
                      loading="lazy"
                    />
                  ) : (
                    <div className="category-card-placeholder">
                      {item.name.slice(0, 2).toUpperCase()}
                    </div>
                  )}
                  <div className="category-card-label">{item.name}</div>
                  <div className="category-card-arrow">›</div>
                </div>
              ) : (
                <div
                  key={`prod-${item.id}`}
                  className="product-card"
                  onClick={() => handleProductClick(item)}
                >
                  {item.thumbnail_url ? (
                    <img
                      src={item.thumbnail_url}
                      alt={item.name}
                      className="product-thumb"
                      loading="lazy"
                    />
                  ) : (
                    <div className="product-thumb-placeholder">🖼</div>
                  )}
                  {parseInt(item.show_title ?? 1) !== 0 && (
                    <div className="product-info">
                      <h3 className="product-name">{item.name}</h3>
                      {item.price && (
                        <p className="product-price">
                          {parseFloat(item.price).toFixed(2)} €
                        </p>
                      )}
                    </div>
                  )}
                </div>
              )
            )}
          </div>
        )}
      </main>

      <ProductModal
        product={selectedProduct}
        onClose={() => setSelectedProduct(null)}
      />
    </div>
  );
}
