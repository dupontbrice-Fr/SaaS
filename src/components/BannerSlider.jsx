import React, { useState, useEffect, useRef } from 'react';
import '../styles/BannerSlider.css';

export default function BannerSlider({ banners }) {
  const [current, setCurrent] = useState(0);
  const timerRef = useRef(null);

  useEffect(() => {
    if (banners.length <= 1) return;
    timerRef.current = setInterval(() => {
      setCurrent((c) => (c + 1) % banners.length);
    }, 5000);
    return () => clearInterval(timerRef.current);
  }, [banners.length]);

  if (!banners.length) return null;

  return (
    <div className="banner-slider">
      {banners.map((banner, i) => (
        <div
          key={banner.id}
          className={`banner-slide ${i === current ? 'active' : ''}`}
        >
          <img src={banner.file_url} alt={banner.title || 'Bannière'} />
        </div>
      ))}
      {banners.length > 1 && (
        <div className="banner-dots">
          {banners.map((_, i) => (
            <button
              key={i}
              className={`dot ${i === current ? 'active' : ''}`}
              onClick={() => setCurrent(i)}
            />
          ))}
        </div>
      )}
    </div>
  );
}
