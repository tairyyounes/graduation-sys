<template>
  <button
    @click="switchLang"
    class="flex items-center gap-2 rounded-md bg-slate-100 px-3 py-1.5 text-sm font-medium text-slate-700 transition hover:bg-slate-200"
  >
    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129" />
    </svg>
    {{ currentLang === 'en' ? 'عربي' : 'English' }}
  </button>
</template>

<script setup>
import { ref } from 'vue';

const currentLang = ref(localStorage.getItem('locale') || 'en');

const switchLang = () => {
  const newLang = currentLang.value === 'en' ? 'ar' : 'en';
  localStorage.setItem('locale', newLang);
  document.cookie = `locale=${newLang}; path=/; max-age=31536000`;
  
  window.location.reload();
};
</script>
