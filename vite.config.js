import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import tailwindcss from "@tailwindcss/vite";
import vue from "@vitejs/plugin-vue";
import path from "path";

export default defineConfig({
    plugins: [
        laravel({
            input: ["resources/css/app.css", "resources/js/app.js"],
            refresh: true,
        }),
        tailwindcss(),
        vue(),
    ],

    server: {
        // host: "0.0.0.0",
        // port: 5173,
        // strictPort: true,

        watch: {
            ignored: ["**/storage/framework/views/**"],
        },

        // hmr: {
        //     protocol: "wss",
        //     host: "disaster-reassign-visible.ngrok-free.dev",
        //     clientPort: 443,
        // },
    },

    resolve: {
        alias: {
            "@": path.resolve(__dirname, "resources/js"),
        },
    },
});
