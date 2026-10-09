<template>
  <div class="min-h-screen flex flex-col bg-white text-slate-800 font-['Inter','IBM_Plex_Sans_Arabic',sans-serif]">
    <div class="w-full max-w-6xl mx-auto px-4 sm:px-8 flex flex-col flex-1">
      <!-- Header -->
      <header class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between py-6 border-b border-slate-200">
        <a href="/" class="flex items-center gap-3" @click="show(null)">
          <svg class="h-9 w-9 shrink-0 text-[#4a6a8f]" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M14 3H7a2 2 0 00-2 2v14a2 2 0 002 2h10a2 2 0 002-2V8l-5-5z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" d="M14 3v5h5M9 14h6" />
          </svg>
          <span>
            <span class="block text-2xl font-semibold leading-tight text-slate-900">{{ $t('welcome.brand') }}</span>
            <span class="block text-sm text-slate-500">{{ $t('common.college') }}</span>
          </span>
        </a>

        <nav class="flex flex-wrap items-center gap-6 text-sm text-slate-600">
          <a href="#about" class="hover:text-slate-900 transition-colors" @click.prevent="show('about')">{{ $t('welcome.about') }}</a>
          <a v-if="isAuthenticated" href="/dashboard" class="hover:text-slate-900 transition-colors">{{ $t('common.dashboard') }}</a>
          <form v-if="isAuthenticated" :action="logoutUrl" method="POST" class="m-0">
            <input type="hidden" name="_token" :value="csrfToken" />
            <button type="submit" class="hover:text-slate-900 transition-colors">{{ $t('common.logout') }}</button>
          </form>
          <button type="button" class="hover:text-slate-900 transition-colors" @click="toggleLang">
            {{ locale === 'ar' ? 'English' : 'العربية' }}
          </button>
        </nav>
      </header>

      <main class="flex-1 flex flex-col justify-center py-12 sm:py-16">
        <!-- Hero -->
        <section v-if="!panel" class="text-center">
          <p class="text-sm text-[#4a6a8f] mb-5">{{ $t('welcome.eyebrow') }}</p>
          <h1 class="text-4xl sm:text-5xl font-bold text-slate-900 mb-6">{{ $t('welcome.title') }}</h1>
          <p class="text-lg sm:text-xl text-slate-600 leading-relaxed max-w-xl mx-auto mb-10">{{ $t('welcome.desc') }}</p>

          <a
            :href="isAuthenticated ? '/dashboard' : loginUrl"
            class="inline-block min-w-[14rem] px-8 py-3 rounded-md bg-[#4a6a8f] text-white text-base font-medium hover:bg-[#3d5878] transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-[#4a6a8f] focus-visible:ring-offset-2"
          >
            {{ isAuthenticated ? $t('welcome.go_dashboard') : $t('welcome.login') }}
          </a>

          <p class="mt-6">
            <a href="#guide" class="text-[#4a6a8f] underline underline-offset-4 hover:text-[#3d5878]" @click.prevent="show('guide')">
              {{ $t('welcome.guide_link') }}
            </a>
          </p>
        </section>

        <!-- About -->
        <section v-else-if="panel === 'about'" id="about" class="max-w-2xl w-full mx-auto">
          <h2 class="text-2xl font-semibold text-slate-900 mb-4">{{ $t('welcome.about_title') }}</h2>
          <p class="text-slate-600 leading-relaxed mb-6">{{ $t('welcome.about_intro') }}</p>
          <ul class="space-y-3 list-disc ps-5 text-slate-700 leading-relaxed">
            <li v-for="(item, i) in tm('welcome.about_items')" :key="i">{{ rt(item) }}</li>
          </ul>
          <button type="button" class="mt-8 text-[#4a6a8f] underline underline-offset-4 hover:text-[#3d5878]" @click="show(null)">
            {{ $t('welcome.close') }}
          </button>
        </section>

        <!-- Guide -->
        <section v-else-if="panel === 'guide'" id="guide" class="max-w-2xl w-full mx-auto">
          <h2 class="text-2xl font-semibold text-slate-900 mb-6">{{ $t('welcome.guide_title') }}</h2>
          <h3 class="font-semibold text-slate-900 mb-3">{{ $t('welcome.guide_fields_title') }}</h3>
          <ul class="space-y-2 list-disc ps-5 text-slate-700 leading-relaxed mb-8">
            <li v-for="(item, i) in tm('welcome.guide_fields')" :key="i">{{ rt(item) }}</li>
          </ul>
          <h3 class="font-semibold text-slate-900 mb-3">{{ $t('welcome.guide_steps_title') }}</h3>
          <ol class="space-y-2 list-decimal ps-5 text-slate-700 leading-relaxed">
            <li v-for="(item, i) in tm('welcome.guide_steps')" :key="i">{{ rt(item) }}</li>
          </ol>
          <button type="button" class="mt-8 text-[#4a6a8f] underline underline-offset-4 hover:text-[#3d5878]" @click="show(null)">
            {{ $t('welcome.close') }}
          </button>
        </section>
      </main>

      <!-- Footer -->
      <footer class="flex flex-col gap-2 sm:flex-row sm:justify-between py-6 text-sm text-slate-500">
        <span>{{ $t('common.college') }}</span>
        <span>{{ $t('welcome.brand') }} © {{ year }}</span>
      </footer>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted, onBeforeUnmount } from 'vue'
import { useI18n } from 'vue-i18n'
import { setLocale } from '../i18n'

const { locale, t, tm, rt } = useI18n()

function toggleLang() {
  setLocale(locale.value === 'ar' ? 'en' : 'ar')
}

const PANELS = ['about', 'guide']
const panel = ref(null)

function readHash() {
  const hash = window.location.hash.slice(1)
  panel.value = PANELS.includes(hash) ? hash : null
}

function show(name) {
  panel.value = name
  history.replaceState(null, '', name ? `#${name}` : window.location.pathname)
  window.scrollTo({ top: 0 })
}

onMounted(() => {
  readHash()
  window.addEventListener('hashchange', readHash)
})
onBeforeUnmount(() => window.removeEventListener('hashchange', readHash))

watch(locale, () => { document.title = t('welcome.brand') }, { immediate: true })

const year = new Date().getFullYear()

const appRoot = document.getElementById('app')
const isAuthenticated = ref(appRoot?.dataset.authenticated === '1')
const loginUrl = ref(appRoot?.dataset.loginUrl || '/login')
const logoutUrl = ref(appRoot?.dataset.logoutUrl || '/logout')
const csrfToken = ref(document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '')
</script>
