<?php

namespace JoshLogic\Hotswap\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ScaffoldHotswapCommand extends Command
{
    protected $signature = 'hotswap:scaffold';
    protected $description = 'Scaffold frontend entry point, fix Blade templates, and set Hotswap env';

    public function handle()
    {
        // Ask user for React or Vue
        $choice = $this->choice(
            'Which frontend environment do you want to use?',
            ['react', 'vue'],
            0
        );

        if ($choice === 'react') {
            $this->updateReactAppTsx();
        } else {
            $this->updateVueAppTsx();
        }

        $this->updateBladeTemplates();
        $this->updateEnvFile($choice);

        $this->info("✅ Scaffold complete: {$choice} app.js, Blade templates, and .env updated.");
        return 0;
    }

    protected function updateReactAppTsx(): void
    {
        $file = base_path('resources/js/app.tsx');

        if (!File::exists(dirname($file))) {
            File::makeDirectory(dirname($file), 0755, true);
        }

        $contents = <<<'TSX'
import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { initializeTheme } from './hooks/use-appearance';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// 1️⃣ Host app pages
const hostPages = import.meta.glob('./pages/**/*.tsx');

// 2️⃣ Automatically detect package pages
const packagePagesGlob = import.meta.glob('../../packages/*/src/resources/js/pages/**/*.tsx');

// Map of packages
const packages: Record<string, Record<string, () => Promise<any>>> = {};

for (const path in packagePagesGlob) {
    const match = path.match(/packages\/([^/]+)\/src\/resources\/js\/pages\/(.+)\.tsx$/i);
    if (!match) continue;

    const [, pkgName, pagePath] = match;
    const normalizedPath = pagePath.replace(/\\/g, '/').toLowerCase();

    if (!packages[pkgName.toLowerCase()]) packages[pkgName.toLowerCase()] = {};
    packages[pkgName.toLowerCase()][normalizedPath] = packagePagesGlob[path];
}

// 3️⃣ Generic resolver
const resolve = async (name: string) => {
    const [pkg, ...rest] = name.split('/');
    const pagePath = rest.join('/').toLowerCase();

    if (pkg && packages[pkg.toLowerCase()]) {
        const pages = packages[pkg.toLowerCase()];
        if (!pages[pagePath]) throw new Error(`Page not found in package "${pkg}": ${pagePath}`);
        const mod = await pages[pagePath]();
        return (mod as { default: any }).default;
    }

    // fallback to host app
    return resolvePageComponent(`./pages/${name}.tsx`, hostPages);
};

// 4️⃣ React root
let root: ReturnType<typeof createRoot> | null = null;

createInertiaApp({
    title: title => title ? `${title} - ${appName}` : appName,
    resolve,
    setup({ el, App, props }) {
        if (!root) root = createRoot(el);
        root.render(<App {...props} />);
    },
    progress: { color: '#4B5563' },
});

// 5️⃣ Theme initialization
initializeTheme();
TSX;

        File::put($file, $contents);
        $this->line("🔹 Updated React app.ts");
    }

    protected function updateVueAppTsx(): void
    {
        $file = base_path('resources/js/app.ts');

        if (!File::exists(dirname($file))) {
            File::makeDirectory(dirname($file), 0755, true);
        }

        $contents = <<<'VUE'
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

// 1️⃣ Host app pages
const hostPages = import.meta.glob('./pages/**/*.vue');

// 2️⃣ Automatically detect package pages
const packagePagesGlob = import.meta.glob('../../packages/*/src/resources/js/pages/**/*.vue');

// Map of packages
const packages = {};

for (const path in packagePagesGlob) {
    const match = path.match(/packages\/([^/]+)\/src\/resources\/js\/pages\/(.+)\.vue$/i);
    if (!match) continue;

    const [, pkgName, pagePath] = match;
    const normalizedPath = pagePath.replace(/\\/g, '/').toLowerCase();

    if (!packages[pkgName.toLowerCase()]) packages[pkgName.toLowerCase()] = {};
    packages[pkgName.toLowerCase()][normalizedPath] = packagePagesGlob[path];
}

// 3️⃣ Generic resolver
const resolve = async (name) => {
    const [pkg, ...rest] = name.split('/');
    const pagePath = rest.join('/').toLowerCase();

    if (pkg && packages[pkg.toLowerCase()]) {
        const pages = packages[pkg.toLowerCase()];
        if (!pages[pagePath]) throw new Error(`Page not found in package "${pkg}": ${pagePath}`);
        const mod = await pages[pagePath]();
        return mod.default;
    }

    // fallback to host app
    return resolvePageComponent(`./pages/${name}.vue`, hostPages);
};

// 4️⃣ Vue root
createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve,
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .mount(el);
    },
    progress: { color: '#4B5563' },
});
VUE;

        File::put($file, $contents);
        $this->line("🔹 Updated Vue app.ts");
    }

    protected function updateBladeTemplates(): void
    {
        $bladeFiles = File::allFiles(resource_path('views'));

        foreach ($bladeFiles as $file) {
            if (!Str::endsWith($file->getFilename(), '.blade.php')) {
                continue;
            }

            $contents = File::get($file->getRealPath());

            // Replace any @vite([...]) call with just app entry
            $pattern = '/@vite\s*\(\s*\[.*?\]\s*\)/s';
            if (preg_match($pattern, $contents)) {
                // React uses app.tsx, Vue uses app.js
                $replacement = "@vite(['resources/js/app." . (File::exists(base_path('resources/js/app.tsx')) ? 'tsx' : 'ts') . "'])";
                $contents = preg_replace($pattern, $replacement, $contents);
                File::put($file->getRealPath(), $contents);
                $this->line("🔹 Updated @vite call in {$file->getFilename()}");
            }
        }
    }

    protected function updateEnvFile(string $choice): void
    {
        $file = base_path('.env');

        if (!File::exists($file)) {
            $this->error("❌ .env file not found!");
            return;
        }

        $contents = File::get($file);

        // Remove existing HOTSWAP_ENV lines
        $contents = preg_replace('/\n?HOTSWAP_ENV=.*\n?/m', "\n", $contents);

        // Append new HOTSWAP_ENV after ensuring spacing
        $contents = rtrim($contents) . "\n\nHOTSWAP_ENV={$choice}\n";

        File::put($file, $contents);

        $this->line("🔹 Updated .env with HOTSWAP_ENV={$choice}");
    }
}
