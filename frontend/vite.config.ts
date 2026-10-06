import react from '@vitejs/plugin-react'
import { defineConfig } from 'vitest/config'

export default defineConfig({
  base: '/app/',
  plugins: [react()],
  // Laravel serves the built files from public/app.
  build: { outDir: '../backend/public/app', emptyOutDir: true },
  // `npm run dev` talks to the API running in Docker.
  server: { proxy: { '/api': 'http://localhost:8080' } },
  // globals lets Testing Library clean up the DOM after each test.
  test: { environment: 'jsdom', globals: true },
})
