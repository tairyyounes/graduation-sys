<template>
  <section class="space-y-6">

    <!-- Header -->
    <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
      <div class="flex items-start justify-between gap-4 mb-5">
        <div>
          <h2 class="text-xl font-bold text-slate-900 mb-1">{{ $t('proposals.repository') }}</h2>
          <p class="text-sm text-slate-500">
            Browse previous proposals from your department to explore ideas and avoid duplication.
          </p>
        </div>
        <div v-if="departmentName" class="shrink-0 inline-flex items-center gap-1.5 rounded-full bg-teal-50 px-3 py-1.5 text-xs font-semibold text-teal-700 ring-1 ring-teal-600/20">
          <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
          </svg>
          {{ departmentName }}
        </div>
      </div>

      <!-- Search + Year Filter -->
      <div class="flex flex-col sm:flex-row gap-3">
        <!-- Search -->
        <div class="relative flex-1">
          <input
            v-model="filters.search"
            type="text"
            placeholder="Search by title, keywords, or technologies..."
            class="w-full rounded-xl border border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm pl-10 pr-4 py-2.5"
            @input="debounceSearch"
          >
          <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
          </span>
        </div>

        <!-- Year Filter -->
        <div class="sm:w-44">
          <select
            v-model="filters.year"
            class="w-full rounded-xl border border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm py-2.5 px-3"
            @change="fetchProposals"
          >
            <option value="">{{ $t('common.all_years') }}</option>
            <option v-for="year in years" :key="year" :value="year">{{ year }}</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="text-center py-20 bg-white rounded-2xl border border-slate-200 shadow-sm">
      <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-slate-200 border-t-teal-600 mb-3"></div>
      <p class="text-sm font-medium text-slate-500">Searching archive...</p>
    </div>

    <!-- Empty state -->
    <div
      v-else-if="proposals.length === 0"
      class="text-center py-20 bg-white rounded-2xl border border-slate-200 border-dashed shadow-sm"
    >
      <div class="mx-auto w-12 h-12 bg-slate-50 rounded-full flex items-center justify-center mb-3">
        <svg class="w-6 h-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
        </svg>
      </div>
      <p class="text-base font-semibold text-slate-900">{{ $t('proposals.nos_found') }}</p>
      <p class="mt-1 text-sm text-slate-500">
        {{ filters.search || filters.year ? 'Try adjusting your search or year filter.' : 'No previous proposals in your department yet.' }}
      </p>
    </div>

    <!-- Proposals Grid -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div
        v-for="prop in proposals"
        :key="prop.id"
        class="bg-white rounded-xl border border-slate-200 p-5 hover:shadow-md hover:border-slate-300 transition-all duration-200 shadow-sm flex flex-col justify-between cursor-pointer"
        @click="viewDetails(prop)"
      >
        <div>
          <!-- Year pill -->
          <div class="flex items-center justify-between mb-3">
            <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600 uppercase tracking-wider">
              {{ prop.year || 'N/A' }}
            </span>
            <span class="text-xs text-slate-400">{{ prop.author }}</span>
          </div>

          <!-- Title -->
          <h3 class="text-base font-bold text-slate-900 line-clamp-2 leading-snug group-hover:text-teal-600">
            {{ prop.title }}
          </h3>

          <!-- Problem excerpt -->
          <p v-if="prop.problem" class="mt-2 text-sm text-slate-500 line-clamp-3 leading-relaxed">
            {{ prop.problem }}
          </p>
        </div>

        <!-- Tags + CTA -->
        <div class="mt-4 pt-4 border-t border-slate-100 flex items-center justify-between gap-2">
          <div class="flex flex-wrap gap-1 min-w-0">
            <span
              v-for="tag in getTags(prop.tags).slice(0, 3)"
              :key="tag"
              class="inline-flex items-center rounded-md bg-slate-50 px-1.5 py-0.5 text-[10px] font-medium text-slate-600 ring-1 ring-inset ring-slate-500/10 truncate max-w-[90px]"
            >
              #{{ tag }}
            </span>
          </div>
          <span class="shrink-0 text-xs font-semibold text-teal-600 hover:text-teal-700 whitespace-nowrap">
            View details →
          </span>
        </div>
      </div>
    </div>

    <!-- Detail Modal -->
    <Transition
      enter-active-class="transition ease-out duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition ease-in duration-150"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div v-if="showModal && selectedProposal" class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-screen items-center justify-center p-4 sm:p-0">
          <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="closeDetails"></div>

          <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 w-full max-w-4xl flex flex-col max-h-[90vh]">

            <!-- Modal Header -->
            <div class="border-b border-slate-100 px-6 py-5 bg-slate-50/60 flex justify-between items-start shrink-0">
              <div class="pr-8">
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">{{ selectedProposal.year }}</p>
                <h3 class="text-xl font-bold text-slate-900 leading-snug">{{ selectedProposal.title }}</h3>
                <p class="text-xs text-slate-500 mt-1">Submitted by: <span class="font-medium text-slate-700">{{ selectedProposal.author }}</span></p>
              </div>
              <button @click="closeDetails" class="text-slate-400 hover:text-slate-600 transition-colors p-1 rounded-lg hover:bg-slate-100 shrink-0">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
              </button>
            </div>

            <!-- Modal Body -->
            <div class="px-6 py-6 overflow-y-auto flex-1 space-y-6">
              <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                <!-- Main content -->
                <div class="lg:col-span-2 space-y-6">
                  <div>
                    <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">{{ $t('messages.problem_statement') }}</h4>
                    <p class="text-slate-700 leading-relaxed text-sm bg-slate-50 p-4 rounded-xl border border-slate-100">
                      {{ selectedProposal.problem || 'Not specified.' }}
                    </p>
                  </div>
                  <div>
                    <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">{{ $t('messages.proposed_solution') }}</h4>
                    <p class="text-slate-700 leading-relaxed text-sm bg-slate-50 p-4 rounded-xl border border-slate-100">
                      {{ selectedProposal.solution || 'Not specified.' }}
                    </p>
                  </div>
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                      <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">{{ $t('common.objectives') }}</h4>
                      <p class="text-slate-600 text-sm whitespace-pre-wrap leading-relaxed">{{ selectedProposal.objectives || 'Not specified.' }}</p>
                    </div>
                    <div>
                      <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">{{ $t('common.core_functions') }}</h4>
                      <p class="text-slate-600 text-sm whitespace-pre-wrap leading-relaxed">{{ selectedProposal.functions || 'Not specified.' }}</p>
                    </div>
                  </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-5 lg:border-l lg:border-slate-100 lg:pl-7">
                  <div>
                    <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Keywords / Tags</h4>
                    <div class="flex flex-wrap gap-1.5">
                      <span
                        v-for="tag in getTags(selectedProposal.tags)"
                        :key="tag"
                        class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700"
                      >
                        {{ tag.trim() }}
                      </span>
                      <span v-if="!getTags(selectedProposal.tags).length" class="text-sm text-slate-400">{{ $t('common.none') }}</span>
                    </div>
                  </div>
                  <div>
                    <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">{{ $t('common.technologies') }}</h4>
                    <div class="flex flex-wrap gap-1.5">
                      <span
                        v-for="tech in getTags(selectedProposal.tech)"
                        :key="tech"
                        class="inline-flex items-center rounded-md bg-blue-50 text-blue-700 px-2.5 py-0.5 text-xs font-medium border border-blue-100"
                      >
                        {{ tech.trim() }}
                      </span>
                      <span v-if="!getTags(selectedProposal.tech).length" class="text-sm text-slate-400">{{ $t('common.none') }}</span>
                    </div>
                  </div>
                  <div>
                    <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">{{ $t('messages.submission_date') }}</h4>
                    <p class="text-sm text-slate-700 font-medium">{{ selectedProposal.date || 'N/A' }}</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Modal Footer -->
            <div class="bg-slate-50 px-6 py-4 flex justify-between items-center border-t border-slate-100 shrink-0">
              <span class="text-xs text-slate-400">Proposal #{{ selectedProposal.id }}</span>
              <div class="flex gap-2">
                <button
                  @click="closeDetails"
                  class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 transition"
                >
                  Close
                </button>
                <button
                  v-if="activeProposal"
                  @click="triggerCompare(selectedProposal.id)"
                  class="inline-flex items-center gap-1.5 rounded-lg bg-teal-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-teal-700 transition"
                >
                  <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                  </svg>
                  Compare with My Proposal
                </button>
              </div>
            </div>

          </div>
        </div>
      </div>
    </Transition>

  </section>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const props = defineProps({
  activeProposal: {
    type: Object,
    default: null,
  },
});

const emit = defineEmits(['compare']);

const proposals      = ref([]);
const years          = ref([]);
const departmentName = ref('');
const loading        = ref(false);
const showModal      = ref(false);
const selectedProposal = ref(null);

const filters = ref({
  search: '',
  year: '',
});

let debounceTimeout = null;

function debounceSearch() {
  clearTimeout(debounceTimeout);
  debounceTimeout = setTimeout(() => fetchProposals(), 350);
}

async function fetchProposals() {
  loading.value = true;
  try {
    const params = new URLSearchParams();
    if (filters.value.search) params.append('search', filters.value.search);
    if (filters.value.year)   params.append('year',   filters.value.year);

    const res = await fetch(`/repository?${params.toString()}`);
    if (res.ok) {
      const data = await res.json();
      proposals.value  = data.proposals || [];
      years.value      = data.years     || [];

      // Derive department name from first result (all results are same dept)
      if (proposals.value.length && !departmentName.value) {
        departmentName.value = proposals.value[0].department || '';
      }
    }
  } catch (err) {
    console.error('Error fetching repository proposals:', err);
  } finally {
    loading.value = false;
  }
}

function viewDetails(proposal) {
  selectedProposal.value = proposal;
  showModal.value = true;
}

function closeDetails() {
  showModal.value = false;
  selectedProposal.value = null;
}

function triggerCompare(id) {
  closeDetails();
  emit('compare', id);
}

function getTags(str) {
  if (!str) return [];
  return str.split(',').map(t => t.trim()).filter(Boolean);
}

onMounted(() => fetchProposals());
</script>
