import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const projectRoot = path.dirname(fileURLToPath(import.meta.url));
const publicFontsPath = path.resolve(projectRoot, 'public/fonts');

function servePublicFontsInDev() {
    return {
        name: 'minhaj-public-fonts-dev-server',
        apply: 'serve',
        configureServer(server) {
            server.middlewares.use('/fonts', (request, response, next) => {
                const requestPath = decodeURIComponent((request.url ?? '').split('?')[0]).replace(/^\/+/, '');
                const fontPath = path.resolve(publicFontsPath, requestPath);

                if (fontPath === publicFontsPath || ! fontPath.startsWith(`${publicFontsPath}${path.sep}`)) {
                    next();
                    return;
                }

                fs.stat(fontPath, (error, stat) => {
                    if (error || ! stat.isFile()) {
                        next();
                        return;
                    }

                    const extension = path.extname(fontPath).toLowerCase();
                    const contentTypes = {
                        '.otf': 'font/otf',
                        '.ttf': 'font/ttf',
                        '.woff': 'font/woff',
                        '.woff2': 'font/woff2',
                    };

                    response.setHeader('Content-Type', contentTypes[extension] ?? 'application/octet-stream');
                    response.setHeader('Cache-Control', 'public, max-age=31536000, immutable');

                    if (request.method === 'HEAD') {
                        response.end();
                        return;
                    }

                    fs.createReadStream(fontPath)
                        .on('error', next)
                        .pipe(response);
                });
            });
        },
    };
}

export default defineConfig({
    server: {
        host: '127.0.0.1',
    },
    plugins: [
        servePublicFontsInDev(),
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
});
