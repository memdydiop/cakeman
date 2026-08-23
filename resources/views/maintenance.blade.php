<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name') }} — Maintenance</title>
    </head>
    <body class="flex min-h-screen items-center justify-center bg-zinc-50 antialiased">
        <div class="w-full max-w-md px-6 text-center">
            <div class="mx-auto flex size-16 items-center justify-center rounded-2xl bg-amber-500/10 text-amber-600">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17 17.25 21A2.652 2.652 0 0 0 21 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 1 1-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 0 0 4.486-6.336l-3.276 3.277a3.004 3.004 0 0 1-2.25-2.25l3.276-3.276a4.5 4.5 0 0 0-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75 1.5 3V1.5h3l2.25 2.25v1.5l3.4 3.4" />
                </svg>
            </div>
            <h1 class="mt-6 text-2xl font-semibold text-zinc-900">
                {{ __('Maintenance en cours') }}
            </h1>
            <p class="mt-3 text-zinc-500">
                {{ $message }}
            </p>
            <p class="mt-8 text-sm text-zinc-400">
                L'équipe {{ config('app.name') }} revient très vite.
            </p>
        </div>
    </body>
</html>