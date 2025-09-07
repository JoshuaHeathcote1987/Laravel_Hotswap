<?php

namespace JoshLogic\Hotswap\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ScaffoldHotswapCommand extends Command
{
    protected $signature = 'hotswap:scaffold';
    protected $description = 'Scaffold resources/js/app.tsx and fix Blade templates for package pages';

    public function handle()
    {
        $this->updateAppTsx();
        $this->updateBladeTemplates();

        $this->info("✅ Scaffold complete: app.tsx and Blade templates updated.");
        return 0;
    }

    protected function updateAppTsx(): void
    {
        $file = base_path('resources/js/app.tsx');

        if (!File::exists($file)) {
            $this->error("❌ app.tsx not found in resources/js/");
            return;
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
        $this->line("🔹 Updated resources/js/app.tsx");
    }

    protected function updateBladeTemplates(): void
    {
        $bladeFiles = File::allFiles(resource_path('views'));

        foreach ($bladeFiles as $file) {
            if (!Str::endsWith($file->getFilename(), '.blade.php')) {
                continue;
            }

            $contents = File::get($file->getRealPath());

            // Replace any @vite([...]) call with just app.tsx
            $pattern = '/@vite\s*\(\s*\[.*?\]\s*\)/s';
            if (preg_match($pattern, $contents)) {
                $contents = preg_replace($pattern, "@vite(['resources/js/app.tsx'])", $contents);
                File::put($file->getRealPath(), $contents);
                $this->line("🔹 Updated @vite call in {$file->getFilename()}");
            }
        }
    }
}
