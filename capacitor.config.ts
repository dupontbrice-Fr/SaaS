import { CapacitorConfig } from '@capacitor/cli';

const config: CapacitorConfig = {
  appId: 'com.saas.display',
  appName: 'SaaS Display',
  webDir: 'dist',
  server: {
    androidScheme: 'https',
    cleartext: true,
  },
  android: {
    allowMixedContent: true,
  },
  plugins: {
    CapacitorUpdater: {
      // URL appelée par l'APK pour vérifier les mises à jour OTA
      updateUrl: 'http://saasapp.s196298.fvl-001.webo-facto.com/api/update.php',
      // Désactive l'envoi de statistiques vers capgo.app (self-hosted)
      statsUrl: '',
      // Active les mises à jour automatiques en arrière-plan
      autoUpdate: true,
      allowEmulatorProd: true,
    },
  },
};

export default config;
