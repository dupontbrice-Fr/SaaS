import { useEffect, useRef } from 'react';
import { sendHeartbeat } from '../services/api';

const INTERVAL_MS = 5 * 60 * 1000; // 5 minutes

export function useHeartbeat(token) {
  const ref = useRef(null);

  useEffect(() => {
    if (!token) return;

    const beat = () => sendHeartbeat(token).catch(console.error);

    beat();
    ref.current = setInterval(beat, INTERVAL_MS);

    return () => clearInterval(ref.current);
  }, [token]);
}
