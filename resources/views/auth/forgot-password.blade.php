<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'ProposalGuard AI') }} - {{ __('auth_ui.reset_password') }}</title>
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
                    <p class="text-xs text-white/80">{{ __('auth_ui.college') }}</p>
                </div>
            </div>

            <div class="max-w-md">
                <h1 class="text-5xl font-bold leading-tight">{{ __('auth_ui.hero') }}</h1>
                <p class="mt-5 text-2xl leading-relaxed text-white/85">
                    {{ __('auth_ui.hero_desc') }}
                </p>
            </div>

            <p class="text-base text-white/80">{{ __('auth_ui.copyright') }}</p>
        </aside>

        <main class="flex min-h-screen items-center justify-center p-4 sm:p-6 lg:p-10 relative">
            <button
                type="button"
                onclick="var m=document.cookie.match(/app_locale=([^;]+)/);var next=(m&&m[1]==='ar')?'en':'ar';document.cookie='app_locale='+next+';path=/;max-age=31536000;SameSite=Lax';try{localStorage.setItem('app_locale',next);}catch(e){}location.reload();"
                class="absolute end-4 top-4 text-sm font-medium text-slate-700 hover:text-slate-900 sm:end-8 sm:top-8 flex items-center gap-1.5"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" />
                </svg>
                <span>{{ app()->getLocale() === 'ar' ? 'English' : 'العربية' }}</span>
            </button>

            <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-lg sm:p-8">
                <div class="flex items-center gap-2 mb-2">
                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-teal-50 text-teal-700">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-slate-900">{{ __('auth_ui.reset_password') }}</h2>
                </div>
                <p class="text-sm text-slate-600 leading-relaxed mb-6">
                    {{ __('auth_ui.forgot_password_desc') }}
                </p>

                <!-- Session Status -->
                <x-auth-session-status class="mb-4" :status="session('status')" />

                <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label for="email" class="mb-1.5 block text-sm font-medium text-slate-700">{{ __('auth_ui.email') }}</label>
                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            required
                            autofocus
                            autocomplete="username"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none transition focus:border-slate-500 focus:ring-2 focus:ring-slate-300"
                            placeholder="{{ __('auth_ui.email_placeholder') }}"
                        />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <button
                        type="submit"
                        class="w-full rounded-lg bg-[#123e69] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#0e3153] focus:outline-none focus:ring-2 focus:ring-slate-400"
                    >
                        {{ __('auth_ui.email_password_reset_link') }}
                    </button>

                    <div class="pt-2 text-center">
                        <a href="{{ route('login') }}" class="inline-flex items-center gap-1 text-sm font-medium text-slate-600 transition hover:text-slate-900">
                            <svg class="h-4 w-4 rtl:rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            <span>{{ __('auth_ui.back_to_login') }}</span>
                        </a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
