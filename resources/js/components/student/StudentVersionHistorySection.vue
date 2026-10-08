<template>
  <section class="space-y-6">
    <!-- Summary strip -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
      <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-sm text-slate-500">{{ $t('student.version.total') }}</p>
        <p class="mt-1 text-2xl font-semibold text-slate-900">{{ versionHistory.length }}</p>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-sm text-slate-500">{{ $t('student.version.updates_left') }}</p>
        <p class="mt-1 text-2xl font-semibold" :class="updatesLeft > 0 ? 'text-teal-600' : 'text-rose-600'">
          {{ updatesLeft }} <span class="text-sm font-normal text-slate-400">/ {{ maxEdits }}</span>
        </p>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <p class="text-sm text-slate-500">{{ $t('student.version.last_updated') }}</p>
        <p class="mt-1 text-lg font-semibold text-slate-900">
          {{ versionHistory.length ? formatDate(versionHistory[0].created_at) : '—' }}
        </p>
      </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-6 sm:p-8 shadow-sm">
      <h3 class="text-lg font-medium text-slate-900 mb-6">{{ $t('student.version.title') }}</h3>

      <!-- Empty state -->
      <div v-if="!versionHistory.length" class="py-10 text-center">
        <p class="text-sm font-medium text-slate-900">{{ $t('student.version.empty_title') }}</p>
        <p class="mt-1 text-sm text-slate-500">{{ $t('student.version.empty_desc') }}</p>
      </div>

      <ul v-else class="-mb-8">
        <li v-for="(ver, index) in versionHistory" :key="ver.id">
          <div class="relative pb-8">
            <span
              v-if="index !== versionHistory.length - 1"
              class="absolute start-4 top-4 -ms-px h-full w-0.5 bg-slate-200"
              aria-hidden="true"
            ></span>
            <div class="relative flex gap-3">
              <span :class="[index === 0 ? 'bg-teal-500' : 'bg-slate-400', 'h-8 w-8 shrink-0 rounded-full flex items-center justify-center ring-8 ring-white text-xs font-semibold text-white']">
                v{{ ver.version_number }}
              </span>

              <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-start justify-between gap-2 pt-1">
                  <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                      <span class="text-sm font-semibold text-slate-900">
                        {{ $t('student.version.version_n', { n: ver.version_number }) }}
                      </span>
                      <span v-if="index === 0" class="rounded-full bg-teal-50 px-2 py-0.5 text-xs font-medium text-teal-700">
                        {{ $t('student.version.current') }}
                      </span>
                      <span v-if="ver.similarity !== null" :class="['rounded-full px-2 py-0.5 text-xs font-medium', scoreClass(ver.similarity)]">
                        {{ $t('student.version.similarity', { percent: ver.similarity }) }}
                      </span>
                    </div>
                    <p class="mt-1 text-sm text-slate-700 break-words">{{ ver.title }}</p>
                  </div>
                  <time class="whitespace-nowrap text-sm text-slate-500" :datetime="ver.created_at">
                    {{ formatDate(ver.created_at) }}
                  </time>
                </div>

                <div class="mt-2 flex flex-wrap items-center gap-1.5 text-xs">
                  <span v-if="ver.is_initial" class="text-slate-500">{{ $t('student.version.initial') }}</span>
                  <template v-else-if="ver.changed_fields.length">
                    <span class="text-slate-500">{{ $t('student.version.changed') }}</span>
                    <span
                      v-for="f in ver.changed_fields"
                      :key="f"
                      class="rounded-md bg-amber-50 px-1.5 py-0.5 font-medium text-amber-700"
                    >{{ $t(`student.version.fields.${f}`) }}</span>
                  </template>
                  <span v-else class="text-slate-500">{{ $t('student.version.no_changes') }}</span>
                </div>

                <button
                  type="button"
                  class="mt-3 text-sm font-medium text-teal-600 hover:text-teal-700"
                  @click="toggle(ver.id)"
                >
                  {{ expanded.has(ver.id) ? $t('student.version.hide') : $t('student.version.show') }}
                </button>

                <dl v-if="expanded.has(ver.id)" class="mt-3 space-y-3 rounded-lg border border-slate-100 bg-slate-50 p-4">
                  <div v-for="(value, field) in ver.content" :key="field">
                    <dt class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-slate-500">
                      {{ $t(`student.version.fields.${field}`) }}
                      <span v-if="ver.changed_fields.includes(field)" class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                    </dt>
                    <dd class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ value || '—' }}</dd>
                  </div>
                </dl>
              </div>
            </div>
          </div>
        </li>
      </ul>
    </div>
  </section>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useI18n } from 'vue-i18n';

const props = defineProps({
  // Newest first
  versionHistory: {
    type: Array,
    required: true,
  },
  maxEdits: {
    type: Number,
    default: 2,
  },
});

const { locale } = useI18n();

const updatesLeft = computed(() => Math.max(0, props.maxEdits - (props.versionHistory.length - 1)));

const expanded = ref(new Set());
function toggle(id) {
  const next = new Set(expanded.value);
  next.has(id) ? next.delete(id) : next.add(id);
  expanded.value = next;
}

function formatDate(iso) {
  if (!iso) return '—';
  return new Date(iso).toLocaleDateString(locale.value === 'ar' ? 'ar' : 'en-GB', {
    year: 'numeric', month: 'short', day: 'numeric',
  });
}

function scoreClass(pct) {
  if (pct >= 70) return 'bg-rose-50 text-rose-700';
  if (pct >= 40) return 'bg-amber-50 text-amber-700';
  return 'bg-emerald-50 text-emerald-700';
}
</script>
