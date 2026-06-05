import React, { useState, useEffect } from 'react';
import BannerSlider from './BannerSlider.jsx';
import ProductModal from './ProductModal.jsx';
import { useHeartbeat } from '../hooks/useHeartbeat.js';
import { trackClick } from '../services/api.js';
import '../styles/CatalogScreen.css';

const ALL_ID = '__all__';

export default function CatalogScreen({ token, data, onLogout, onRefresh }) {
  const [activeCatId, setActiveCatId] = useState(ALL_ID);
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

  // Flatten all products for the "Tous" tab
  const allProducts = [
    ...root_products,
    ...categories.flatMap((c) => c.products || []),
  ];

  const activeCategory = categories.find((c) => c.id === activeCatId);
  const displayedProducts =
    activeCatId === ALL_ID
      ? allProducts
      : (activeCategory?.products ?? []);

  // Show "Tous" tab only if there are multiple categories or root products
  const showAllTab = categories.length > 1 || root_products.length > 0;

  // Default to first category on load
  useEffect(() => {
    if (categories.length > 0) {
      setActiveCatId(categories[0].id);
    }
  }, [categories.length]);

  function handleCategoryClick(cat) {
    setActiveCatId(cat.id);
    trackClick(token, 'category', cat.id, cat.name).catch(() => {});
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

  return (
    <div className="catalog-screen">
      <header className="catalog-header">
        <h1 className="catalog-title">{storeName}</h1>
        <div className="header-actions">
          <button
            className="icon-btn"
            onClick={handleRefresh}
            disabled={refreshing}
            title="Actualiser"
          >
            <span className={refreshing ? 'spin' : ''}>↻</span>
          </button>
          <button className="icon-btn logout-btn" onClick={onLogout} title="Déconnecter">
            ✕
          </button>
        </div>
      </header>

      {banners.length > 0 && <BannerSlider banners={banners} />}

      {categories.length > 0 && (
        <nav className="categories-nav">
          {showAllTab && (
            <button
              className={`cat-btn ${activeCatId === ALL_ID ? 'active' : ''}`}
              onClick={() => setActiveCatId(ALL_ID)}
            >
              Tous
            </button>
          )}
          {categories.map((cat) => (
            <button
              key={cat.id}
              className={`cat-btn ${activeCatId === cat.id ? 'active' : ''}`}
              onClick={() => handleCategoryClick(cat)}
            >
              {cat.image_url && (
                <img src={cat.image_url} alt="" className="cat-icon" />
              )}
              {cat.name}
            </button>
          ))}
        </nav>
      )}

      <main className="products-grid">
        {displayedProducts.length === 0 ? (
          <div className="empty-state">
            <p>Aucun produit disponible.</p>
          </div>
        ) : (
          displayedProducts.map((product) => (
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
              <div className="product-info">
                <h3 className="product-name">{product.name}</h3>
                {product.price && (
                  <p className="product-price">
                    {parseFloat(product.price).toFixed(2)} €
                  </p>
                )}
                {product.description && (
                  <p className="product-desc">{product.description}</p>
                )}
              </div>
            </div>
          ))
        )}
      </main>

      <ProductModal product={selectedProduct} onClose={() => setSelectedProduct(null)} />
    </div>
  );
}
