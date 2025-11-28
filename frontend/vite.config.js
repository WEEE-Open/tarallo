import { fileURLToPath, URL } from 'node:url';
import path from "node:path";

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'

// https://vite.dev/config/
export default defineConfig({
  plugins: [
    vue(),
    vueDevTools(),
  ],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url))
    },
  },
  build: {
    rollupOptions: {
      input: {
        donation: path.resolve(__dirname, 'src/donation.js'),
      },
      output: {
        dir: "../script-ng",
        entryFileNames: "[name].js"
      },
    },
    emptyOutDir: true,
  }
})
