<template>
  <button
    type="button"
    class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-slate-900 transition-colors focus:outline-none"
    @click="toggle"
  >
    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" />
    </svg>
    <span>{{ locale === 'ar' ? 'English' : 'العربية' }}</span>
  </button>
</template>

<script setup>
import { useI18n } from 'vue-i18n'
import { setLocale } from '../../i18n'

const { locale } = useI18n()

function toggle() {
  setLocale(locale.value === 'ar' ? 'en' : 'ar')
}
</script>
