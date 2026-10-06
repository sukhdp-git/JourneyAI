import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

const API_TARGET = process.env.VITE_API_PROXY_TARGET ?? 'http://localhost:4000';
// The control panel is a separate Vite app (apps/admin) on :3001; proxy it so the dev URL matches production.
const ADMIN_TARGET = process.env.VITE_ADMIN_PROXY_TARGET ?? 'http://localhost:3001';

export default defineConfig({
  plugins: [react()],
  server: {
    port: 3000,
    strictPort: true,
    proxy: {
      '/api': { target: API_TARGET, changeOrigin: false, xfwd: true },
      '/control-panel': { target: ADMIN_TARGET, changeOrigin: false, ws: true },
    },
  },
  preview: { port: 3000, proxy: { '/api': { target: API_TARGET, changeOrigin: false, xfwd: true } } },
  build: {
    sourcemap: false,
    target: 'es2022',
    chunkSizeWarningLimit: 700,
    rollupOptions: {
      output: {
        manualChunks: {
          react: ['react', 'react-dom', 'react-router'],
          query: ['@tanstack/react-query'],
          charts: ['recharts'],
        },
      },
    },
  },
});
