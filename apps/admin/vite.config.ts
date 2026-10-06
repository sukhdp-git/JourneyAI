import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

const API_TARGET = process.env.VITE_API_PROXY_TARGET ?? 'http://localhost:4000';

// The control panel is a separate app served under /control-panel/ — never from public routes.
export default defineConfig({
  base: '/control-panel/',
  plugins: [react()],
  server: {
    port: 3001,
    strictPort: true,
    proxy: { '/api': { target: API_TARGET, changeOrigin: false, xfwd: true } },
  },
  build: { target: 'es2022', sourcemap: false, chunkSizeWarningLimit: 700 },
});
