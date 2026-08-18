<template>
  <section class="space-y-6">
    <div class="rounded-xl border border-slate-200 bg-white p-6 sm:p-8 shadow-sm">
      <h3 class="text-lg font-medium text-slate-900 mb-6">{{ $t('proposals.timeline') }}</h3>
      <ul class="-mb-8">
        <li v-for="(ver, index) in versionHistory" :key="ver.version">
          <div class="relative pb-8">
            <span
              v-if="index !== versionHistory.length - 1"
              class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-slate-200"
              aria-hidden="true"
            ></span>
            <div class="relative flex space-x-3">
              <div>
                <span :class="[index === 0 ? 'bg-teal-500' : 'bg-slate-400', 'h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white']">
                  <svg class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </span>
              </div>
              <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                <div>
                  <p class="text-sm text-slate-500">
                    <span class="font-medium text-slate-900">{{ ver.version }}</span>
                    &mdash; {{ ver.note }}
                  </p>
                </div>
                <div class="whitespace-nowrap text-right text-sm text-slate-500">
                  <time>{{ ver.date }}</time>
                </div>
              </div>
            </div>
          </div>
        </li>
      </ul>
    </div>
  </section>
</template>

<script setup>
defineProps({
  versionHistory: {
    type: Array,
    required: true,
  },
})
</script>
