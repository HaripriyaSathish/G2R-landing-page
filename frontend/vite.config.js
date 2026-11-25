import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

// During development the PHP backend runs on :8000
// (php -S localhost:8000 -t backend backend/router.php)
// and Vite forwards /api, /uploads and /admin to it, so everything
// works from http://localhost:5173 (website) and http://localhost:5173/admin (admin panel).
const BACKEND = process.env.VITE_BACKEND || 'http://localhost:8000'

export default defineConfig({
  plugins: [react()],
  server: {
    port: 5173,
    strictPort: true, // always 5173 — fail instead of silently switching to 5174
    proxy: {
      '/api': { target: BACKEND, changeOrigin: true },
      '/uploads': { target: BACKEND, changeOrigin: true },
      '/admin': { target: BACKEND, changeOrigin: false },
    },
  },
})
