import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [react()],
  optimizeDeps: {
    exclude: ['lucide-react'],
  },
  server: {
    proxy: {
      // In XAMPP, this repo lives under htdocs/careerconnect
      // and PHP is served by Apache at:
      //   http://localhost/careerconnect/Backend
      '/api': {
        target: 'http://localhost/careerconnect/Backend',
        changeOrigin: true,
      },
      // Serve uploaded files from the same backend origin in dev
      '/uploads': {
        target: 'http://localhost/careerconnect/Backend',
        changeOrigin: true,
      },
    },
  },
});
