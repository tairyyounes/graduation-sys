<template>
  <section class="space-y-6">
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
      <div class="px-6 py-5 border-b border-slate-100">
        <h3 class="text-base font-medium text-slate-900">{{ $t('decisions.review_decisions') }}</h3>
      </div>
      <ul class="divide-y divide-slate-200">
        <li v-for="(feedback, index) in domainFeedback" :key="index" class="p-6">
          <div class="flex items-center justify-between mb-4">
            <p class="text-sm font-medium text-slate-900">
              {{ feedback.reviewer }}
              <span class="text-slate-500 font-normal">({{ feedback.date }})</span>
            </p>
            <span class="inline-flex items-center rounded-md bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-800">
              {{ feedback.decision }}
            </span>
          </div>
          <div class="text-sm text-slate-600 bg-slate-50 p-4 rounded-lg border border-slate-100">
            {{ feedback.note }}
          </div>
        </li>
      </ul>
    </div>
  </section>
</template>

<script setup>
defineProps({
  domainFeedback: {
    type: Array,
    required: true,
  },
})
</script>
