import { copyFile, glob, mkdir, readdir, rm } from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { watch } from 'chokidar';
import { rolldown } from 'rolldown';

const root = path.dirname(fileURLToPath(import.meta.url));
const output = path.resolve(root, process.env.BUILD_DIR || 'dist');
const args = new Set(process.argv.slice(2));
const debug = args.has('--debug');
const hasTask = args.has('--js') || args.has('--vendor') || args.has('--vendors');
const buildJs = args.has('--js') || !hasTask;
const buildVendor = args.has('--vendor') || args.has('--vendors') || !hasTask;

const copyVendor = [
    {
        src: 'node_modules/jquery/dist/jquery.min.js',
        dist: 'javascript'
    },
    {
        src: 'node_modules/jquery-validation/dist/jquery.validate.min.js',
        dist: 'javascript'
    },
];

async function scripts() {
    const source = path.join(root, 'src/javascript');
    const destination = path.join(output, 'javascript');
    await rm(destination, { recursive: true, force: true });
    await mkdir(destination, { recursive: true });
    for (const file of await readdir(source)) {
        if (!file.endsWith('.js')) continue;
        const bundle = await rolldown({
            input: path.join(source, file),
            platform: 'browser',
            resolve: { extensions: ['.js', '.mjs', '.json'] },
        });
        try {
            await bundle.write({
                file: path.join(destination, file),
                format: 'es',
                sourcemap: true,
                minify: debug ? false : {
                    compress: { dropConsole: true },
                    mangle: true,
                },
            });
        } finally {
            await bundle.close();
        }
    }
}

async function vendor() {
    for (const item of copyVendor) {
        if (!item.src || !item.dist) continue;
        const globIndex = item.src.search(/[*?{[+@!]/);
        let baseDir;
        if (globIndex === -1) {
            baseDir = path.dirname(path.resolve(root, item.src));
        } else {
            const prefix = item.src.slice(0, globIndex);
            const lastSlash = prefix.lastIndexOf('/');
            baseDir = path.resolve(root, lastSlash !== -1 ? prefix.slice(0, lastSlash) : prefix);
        }
        for await (const entry of glob(item.src, { cwd: root, withFileTypes: true })) {
            if (!entry.isFile()) continue;
            const srcFile = path.join(entry.parentPath || entry.path, entry.name);
            const relPath = path.relative(baseDir, srcFile);
            const destFile = path.resolve(output, item.dist, relPath);
            await mkdir(path.dirname(destFile), { recursive: true });
            await copyFile(srcFile, destFile);
        }
    }
}

async function build() {
    if (buildJs) await scripts();
    if (buildVendor) await vendor();
}

await build();
if (args.has('--watch')) {
    let pending = Promise.resolve();
    watch(path.join(root, 'src'), { ignoreInitial: true }).on('all', () => {
        pending = pending.then(build).catch(error => console.error(error));
    });
    console.log('Watching client sources for changes');
}
