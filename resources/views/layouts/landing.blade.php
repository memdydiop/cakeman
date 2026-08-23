<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        @include('partials.head', ['title' => $title ?? null])

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link rel="stylesheet" href="https://fonts.bunny.net/css?family=fraunces:opsz,wght@9..144,600;9..144,700&display=swap">
    </head>
    <body class="bg-cream text-stone-900 antialiased">
        <nav class="sticky top-0 z-50 border-b border-amber-900/10 bg-cream/80 backdrop-blur">
            <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6">
                <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                    <span class="flex size-8 items-center justify-center rounded-md bg-amber-600">
                        <x-app-logo-icon class="size-5 fill-current text-white" />
                    </span>
                    <span class="font-display text-lg font-semibold tracking-tight">{{ config('app.name') }}</span>
                </a>

                <div class="hidden items-center gap-8 text-sm font-medium text-stone-600 md:flex">
                    <a href="{{ route('home') }}#features" class="transition hover:text-stone-900">Fonctionnalités</a>
                    <a href="{{ route('home') }}#pricing" class="transition hover:text-stone-900">Tarifs</a>
                    <a href="{{ route('faq') }}" class="transition hover:text-stone-900">FAQ</a>
                    <a href="{{ route('admin.login') }}" class="transition hover:text-stone-900">Console</a>
                </div>

                <a href="{{ route('register') }}" class="rounded-full bg-amber-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-amber-700">
                    Créer ma boutique
                </a>
            </div>
        </nav>

        <main class="mx-auto max-w-3xl px-4 py-16 sm:px-6">
            <h1 class="font-display text-3xl font-semibold tracking-tight sm:text-4xl">{{ $title }}</h1>

            <div class="mt-8 text-base leading-relaxed text-zinc-700">
                @yield('content')
            </div>
        </main>

        <x-landing.footer />

        @fluxScripts
    </body>
</html>