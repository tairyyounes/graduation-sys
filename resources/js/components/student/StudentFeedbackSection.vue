<template>
  <section class="space-y-6">
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
      <div class="px-6 py-5 border-b border-slate-100">
        <h3 class="text-base font-medium text-slate-900">{{ $t('student.feedback.title') }}</h3>
      </div>

      <div v-if="!domainFeedback" class="p-6 text-sm text-slate-500">
        {{ $t('student.feedback.loading') }}
      </div>

      <div v-else-if="domainFeedback.length === 0" class="p-10 text-center">
        <p class="text-sm font-medium text-slate-900">{{ $t('student.feedback.empty_title') }}</p>
        <p class="mt-1 text-sm text-slate-500">{{ $t('student.feedback.empty_body') }}</p>
      </div>

      <ul v-else class="divide-y divide-slate-200">
        <li v-for="(feedback, index) in domainFeedback" :key="index" class="p-6">
          <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
            <p class="text-sm font-medium text-slate-900">
              {{ feedback.reviewer }}
              <span v-if="feedback.date" class="text-slate-500 font-normal">({{ formatDate(feedback.date) }})</span>
            </p>
            <span
              class="inline-flex items-center rounded-md px-2.5 py-1 text-xs font-medium"
              :class="badgeClass(feedback.type)"
            >
              {{ $t(`status.${feedback.type}`) }}
            </span>
          </div>
          <div
            v-if="feedback.note"
            class="text-sm text-slate-600 bg-slate-50 p-4 rounded-lg border border-slate-100 whitespace-pre-line"
          >
            {{ feedback.note }}
          </div>
          <p v-else class="text-sm italic text-slate-400">{{ $t('student.feedback.no_note') }}</p>
        </li>
      </ul>
    </div>
  </section>
</template>

<script setup>
import { useI18n } from 'vue-i18n';

defineProps({
  domainFeedback: {
    type: Array,
    default: null,
  },
});

const { locale } = useI18n();

const BADGES = {
  accepted: 'bg-emerald-50 text-emerald-700',
  rejected: 'bg-red-50 text-red-700',
  revision_requested: 'bg-amber-50 text-amber-700',
};

function badgeClass(type) {
  return BADGES[type] ?? 'bg-slate-100 text-slate-800';
}

function formatDate(value) {
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return value;
  return date.toLocaleDateString(locale.value === 'ar' ? 'ar' : 'en', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
}
</script>
