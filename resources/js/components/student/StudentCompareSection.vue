<template>
  <section class="space-y-6">
    <!-- Top Bar Navigation -->
    <div class="flex items-center justify-between">
      <button 
        @click="$emit('back')" 
        class="inline-flex items-center text-sm font-medium text-slate-500 hover:text-teal-600 transition"
      >
        <svg class="me-1 h-4 w-4 rtl:-scale-x-100" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        {{ $t('student.compare.back') }}
      </button>

      <!-- Run Similarity Check Trigger -->
      <button
        v-if="currentProposal && !checking"
        @click="runSimilarityRecheck"
        class="rounded-xl border border-teal-600 bg-white px-4 py-2 text-xs font-semibold text-teal-700 hover:bg-teal-50 transition flex items-center gap-1.5"
      >
        <svg class="h-4 w-4 animate-pulse text-teal-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 1121.21 7.89H18v3.5"/></svg>
        {{ $t('student.compare.rerun') }}
      </button>
    </div>

    <!-- Loading spinner -->
    <div v-if="loading" class="text-center py-20 bg-white rounded-2xl border border-slate-200 shadow-sm">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-slate-200 border-t-teal-600 mb-3"></div>
      <p class="text-sm font-medium text-slate-500">{{ $t('student.compare.loading') }}</p>
    </div>

    <div v-else-if="error" class="text-center py-20 bg-white rounded-2xl border border-slate-200 shadow-sm">
      <p class="text-base font-semibold text-rose-600 mb-2">{{ $t('student.compare.failed') }}</p>
      <p class="text-sm text-slate-500">{{ error }}</p>
      <button @click="$emit('back')" class="mt-4 rounded-lg bg-slate-100 px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-200 transition">
        {{ $t('student.compare.return') }}
      </button>
    </div>

    <div v-else-if="currentProposal && comparedProposal" class="space-y-6">
      <!-- AI Similarity Summary Card -->
      <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
          <div class="flex items-center gap-4">
            <div class="h-16 w-16 rounded-2xl bg-teal-50 flex items-center justify-center border border-teal-100 flex-shrink-0">
              <span class="text-2xl font-black text-teal-700">{{ similarity ? similarity.score : '0%' }}</span>
            </div>
            <div>
              <h3 class="text-lg font-bold text-slate-900">{{ $t('similarity.breakdown') }}</h3>
              <p class="text-sm text-slate-500">{{ $t('student.compare.breakdown_desc') }}</p>
            </div>
          </div>

          <div v-if="similarity" class="flex-1 max-w-md">
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 text-center">
              <div class="p-2 bg-slate-50 rounded-xl border border-slate-100">
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">{{ $t('similarity.semantic') }}</p>
                <p class="text-xs font-bold text-slate-700 mt-0.5">{{ similarity.semantic || $t('common.not_available') }}</p>
              </div>
              <div class="p-2 bg-slate-50 rounded-xl border border-slate-100">
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">{{ $t('similarity.functions') }}</p>
                <p class="text-xs font-bold text-slate-700 mt-0.5">{{ similarity.functions || $t('common.not_available') }}</p>
              </div>
              <div class="p-2 bg-slate-50 rounded-xl border border-slate-100">
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">{{ $t('similarity.objectives') }}</p>
                <p class="text-xs font-bold text-slate-700 mt-0.5">{{ similarity.objectives || $t('common.not_available') }}</p>
              </div>
              <div class="p-2 bg-slate-50 rounded-xl border border-slate-100">
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">{{ $t('similarity.tags') }}</p>
                <p class="text-xs font-bold text-slate-700 mt-0.5">{{ similarity.tags || $t('common.not_available') }}</p>
              </div>
              <div class="p-2 bg-slate-50 rounded-xl border border-slate-100">
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">{{ $t('similarity.tech') }}</p>
                <p class="text-xs font-bold text-slate-700 mt-0.5">{{ similarity.tech || $t('common.not_available') }}</p>
              </div>
            </div>
          </div>

          <div v-else class="text-sm text-slate-500 bg-slate-50 p-4 rounded-xl border border-slate-100 max-w-sm">
            💡 {{ $t('student.compare.no_match') }}
          </div>
        </div>

        <!-- AI Verdict & Explanation -->
        <div v-if="similarity" class="mt-5 border-t border-slate-100 pt-5 space-y-2">
          <div class="flex items-center gap-2">
            <span class="text-xs font-bold uppercase tracking-wider px-2 py-0.5 rounded-md"
              :class="similarity.verdict === 'Very High Similarity' || similarity.verdict === 'High Similarity'
                ? 'bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-600/20'
                : 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20'"
            >
              {{ $t('similarity.verdict') }}: {{ similarity.verdict }}
            </span>
          </div>
          <p class="text-sm text-slate-600 leading-relaxed">{{ similarity.explanation }}</p>
        </div>
      </div>

      <!-- Side by side Columns -->
      <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <!-- Left: Current Proposal -->
        <article class="flex flex-col rounded-2xl border border-teal-200 bg-white shadow-sm overflow-hidden">
          <div class="border-b border-teal-100 bg-teal-50/50 p-5">
            <div class="mb-3 inline-block rounded-full bg-teal-100 px-2.5 py-0.5 text-xs font-semibold text-teal-800">
              {{ $t('student.compare.your_proposal') }}
            </div>
            <h2 class="text-xl font-bold text-slate-900">{{ currentProposal.title }}</h2>
            <p class="mt-1 text-xs font-medium text-slate-500">{{ $t('fields.author') }}: {{ currentProposal.author }}</p>
          </div>
          
          <div class="flex-1 p-5 space-y-6">
            <div>
              <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">{{ $t('fields.problem_statement') }}</h3>
              <p class="text-sm leading-relaxed text-slate-700 bg-slate-50 p-4 rounded-xl border border-slate-100">{{ currentProposal.problem }}</p>
            </div>
            <div>
              <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">{{ $t('fields.proposed_solution') }}</h3>
              <p class="text-sm leading-relaxed text-slate-700 bg-slate-50 p-4 rounded-xl border border-slate-100">{{ currentProposal.solution }}</p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">{{ $t('fields.objectives') }}</h3>
                <p class="text-sm leading-relaxed text-slate-600 whitespace-pre-wrap">{{ currentProposal.objectives }}</p>
              </div>
              <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">{{ $t('fields.core_functions') }}</h3>
                <p class="text-sm leading-relaxed text-slate-600 whitespace-pre-wrap">{{ currentProposal.functions }}</p>
              </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-4 border-t border-slate-100">
              <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">{{ $t('fields.tags') }}</h3>
                <div class="flex flex-wrap gap-1">
                  <span v-for="tag in getTags(currentProposal.tags)" :key="tag" class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">
                    {{ tag }}
                  </span>
                </div>
              </div>
              <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">{{ $t('fields.technologies') }}</h3>
                <div class="flex flex-wrap gap-1">
                  <span v-for="tech in getTags(currentProposal.tech)" :key="tech" class="inline-flex items-center rounded-md bg-blue-50 text-blue-700 px-2 py-0.5 text-xs font-medium border border-blue-100">
                    {{ tech }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </article>

        <!-- Right: Compared Proposal -->
        <article class="flex flex-col rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
          <div class="border-b border-slate-100 bg-slate-50/50 p-5">
            <div class="mb-3 inline-block rounded-full bg-slate-200 px-2.5 py-0.5 text-xs font-semibold text-slate-700">
              {{ $t('student.compare.compared_match', { year: comparedProposal.year }) }}
            </div>
            <h2 class="text-xl font-bold text-slate-900">{{ comparedProposal.title }}</h2>
            <p class="mt-1 text-xs font-medium text-slate-500">{{ $t('fields.author') }}: {{ comparedProposal.author }} · {{ comparedProposal.department }}</p>
          </div>
          
          <div class="flex-1 p-5 space-y-6">
            <div>
              <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">{{ $t('fields.problem_statement') }}</h3>
              <p class="text-sm leading-relaxed text-slate-700 bg-slate-50 p-4 rounded-xl border border-slate-100">{{ comparedProposal.problem }}</p>
            </div>
            <div>
              <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">{{ $t('fields.proposed_solution') }}</h3>
              <p class="text-sm leading-relaxed text-slate-700 bg-slate-50 p-4 rounded-xl border border-slate-100">{{ comparedProposal.solution }}</p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">{{ $t('fields.objectives') }}</h3>
                <p class="text-sm leading-relaxed text-slate-600 whitespace-pre-wrap">{{ comparedProposal.objectives }}</p>
              </div>
              <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">{{ $t('fields.core_functions') }}</h3>
                <p class="text-sm leading-relaxed text-slate-600 whitespace-pre-wrap">{{ comparedProposal.functions }}</p>
              </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-4 border-t border-slate-100">
              <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">{{ $t('fields.tags') }}</h3>
                <div class="flex flex-wrap gap-1">
                  <span v-for="tag in getTags(comparedProposal.tags)" :key="tag" class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600">
                    {{ tag }}
                  </span>
                </div>
              </div>
              <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-1.5">{{ $t('fields.technologies') }}</h3>
                <div class="flex flex-wrap gap-1">
                  <span v-for="tech in getTags(comparedProposal.tech)" :key="tech" class="inline-flex items-center rounded-md bg-blue-50 text-blue-700 px-2 py-0.5 text-xs font-medium border border-blue-100">
                    {{ tech }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </article>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useToast } from "vue-toastification";
import { useI18n } from 'vue-i18n';

const toast = useToast();
const { t } = useI18n();

const props = defineProps({
  comparedId: {
    type: Number,
    required: true,
  }
});

const emit = defineEmits(['back']);

const loading = ref(true);
const checking = ref(false);
const error = ref(null);

const currentProposal = ref(null);
const comparedProposal = ref(null);
const similarity = ref(null);

async function fetchComparison() {
  loading.value = true;
  error.value = null;
  try {
    const res = await fetch(`/repository/${props.comparedId}/compare`);
    if (res.ok) {
      const data = await res.json();
      currentProposal.value = data.current;
      comparedProposal.value = data.compared;
      similarity.value = data.similarity;
    } else {
      const data = await res.json();
      error.value = data.message || t('student.compare.load_error');
    }
  } catch (err) {
    console.error('Error fetching comparison:', err);
    error.value = t('student.compare.fetch_error');
  } finally {
    loading.value = false;
  }
}

async function runSimilarityRecheck() {
  if (!currentProposal.value) return;
  checking.value = true;
  toast.info(t('student.compare.starting_check'));
  try {
    // Re-run similarity check for the current proposal
    const res = await fetch(`/student/proposals/${currentProposal.value.id}/similarity?recheck=true`);
    if (res.ok) {
      toast.success(t('student.compare.analysis_updated'));
      await fetchComparison(); // reload values
    } else {
      toast.error(t('student.compare.recheck_failed'));
    }
  } catch (err) {
    console.error('Error during recheck:', err);
    toast.error(t('student.compare.unexpected_error'));
  } finally {
    checking.value = false;
  }
}

function getTags(tagsString) {
  if (!tagsString) return [];
  return tagsString.split(',').map(t => t.trim()).filter(t => t !== '');
}

onMounted(() => {
  fetchComparison();
});
</script>
