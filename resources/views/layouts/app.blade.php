<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="emerald">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'IslamResearch') }}</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Amiri:wght@400;700&display=swap" rel="stylesheet">

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .font-arabic { font-family: 'Amiri', serif; }
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="antialiased bg-base-100 text-base-content min-h-screen">
    <div class="navbar bg-base-100 border-b border-base-200 px-8">
        <div class="flex-1">
            <a href="/" class="text-xl font-bold tracking-tighter">Islam<span class="text-primary">Research</span></a>
        </div>
        <div class="flex-none gap-4">
            <div class="menu menu-horizontal px-1">
                <li><a href="/scholar">Workspace</a></li>
            </div>
            <div class="dropdown dropdown-end">
                <div tabindex="0" role="button" class="btn btn-ghost btn-circle avatar online">
                    <div class="w-10 rounded-full">
                        <img alt="User Avatar" src="https://ui-avatars.com/api/?name={{ auth()->user()?->name ?? 'Guest' }}&background=059669&color=fff" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <main>
        {{ $slot }}
    </main>

    <footer class="footer footer-center p-10 bg-base-200 text-base-content rounded mt-12">
        <nav class="grid grid-flow-col gap-4">
            <a href="/" class="link link-hover">Home</a>
            <a href="/scholar" class="link link-hover">Workspace</a>
            <a href="/privacy" class="link link-hover">Privacy Policy</a>
            <a href="/terms" class="link link-hover">Terms of Service</a>
        </nav>
        <aside>
            <p>Copyright © {{ date('Y') }} - All rights reserved by IslamResearch platform. Alhamdulillah.</p>
        </aside>
    </footer>
</body>
</html>
