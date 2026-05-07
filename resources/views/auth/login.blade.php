<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'ProposalGuard AI') }} - Login</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 font-sans text-slate-900 antialiased">
    <div class="min-h-screen lg:grid lg:grid-cols-2">
        <aside class="hidden lg:flex flex-col justify-between bg-gradient-to-br from-[#0b3454] to-[#0c9ca0] p-8 text-white">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-white/20">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2l7 4v6c0 5-3.5 8.5-7 10-3.5-1.5-7-5-7-10V6l7-4z" />
                    </svg>
                </div>
                <div>
                    <p class="text-base font-semibold">ProposalGuard AI</p>
                    <p class="text-xs text-white/80">College of Computer Technology - Tripoli</p>
                </div>
            </div>

            <div class="max-w-md">
                <h1 class="text-5xl font-bold leading-tight">Detect proposal similarity</h1>
                <p class="mt-5 text-2xl leading-relaxed text-white/85">
                    An academic platform that helps students submit proposals and helps departments make confident
                    accept, reject, or revise decisions - backed by semantic AI.
                </p>
            </div>

            <p class="text-base text-white/80">© 2026 — College of Computer Technology — Tripoli</p>
        </aside>

        <main class="flex min-h-screen items-center justify-center p-4 sm:p-6 lg:p-10">
            <div class="absolute right-4 top-4 text-sm text-slate-700 sm:right-8 sm:top-8">العربية</div>

            <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-lg sm:p-8">
                <h2 class="text-3xl font-semibold text-slate-900">Welcome back</h2>
                <p class="mt-1 text-sm text-slate-500">Sign in to continue</p>

                <x-auth-session-status class="mt-4" :status="session('status')" />

                <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
                    @csrf

                    <div>
                        <label for="email" class="mb-1.5 block text-sm font-medium text-slate-700">Email</label>
                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            required
                            autofocus
                            autocomplete="username"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-300"
                            placeholder="you@ctc.ly"
                        />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div>
                        <label for="password" class="mb-1.5 block text-sm font-medium text-slate-700">Password</label>
                        <input
                            id="password"
                            type="password"
                            name="password"
                            required
                            autocomplete="current-password"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-300"
                            placeholder="••••••••"
                        />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <div class="flex items-center justify-between pt-1">
                        <label for="remember_me" class="inline-flex items-center gap-2 text-sm text-slate-600">
                            <input id="remember_me" type="checkbox" class="rounded border-slate-300 text-slate-700 focus:ring-slate-400" name="remember">
                            <span>Remember me</span>
                        </label>

                        @if (Route::has('password.request'))
                            <a class="text-sm font-medium text-slate-600 transition hover:text-slate-900" href="{{ route('password.request') }}">
                                Forgot?
                            </a>
                        @endif
                    </div>

                    <button
                        type="submit"
                        class="w-full rounded-lg bg-[#123e69] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#0e3153] focus:outline-none focus:ring-2 focus:ring-slate-400"
                    >
                        Log in
                    </button>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
