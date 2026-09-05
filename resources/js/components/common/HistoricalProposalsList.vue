<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h2 class="text-xl font-bold text-slate-900 tracking-tight">{{ $t('deptnav.previous_proposals') }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ $t('hist.list_subtitle') }}</p>
      </div>

      <!-- Search/Filter Controls -->
      <div class="flex items-center gap-3">
        <div class="relative">
          <input
            v-model="searchQuery"
            type="text"
            :placeholder="$t('hist.search_ph')"
            class="block w-full sm:w-64 rounded-xl border-slate-300 ps-10 text-sm focus:border-teal-500 focus:ring-teal-500 shadow-sm"
          >
          <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3">
            <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>
        </div>
      </div>
    </div>

    <!-- Data Table -->
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200">
          <thead class="bg-slate-50">
            <tr>
              <th scope="col" class="px-6 py-3.5 text-start text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ $t('hist.project_title') }}</th>
              <th scope="col" class="px-6 py-3.5 text-start text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ $t('fields.domain') }}</th>
              <th scope="col" class="px-6 py-3.5 text-start text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ $t('fields.department') }}</th>
              <th scope="col" class="px-6 py-3.5 text-start text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ $t('hist.submitted_on') }}</th>
              <th scope="col" class="px-6 py-3.5 text-end text-xs font-semibold text-slate-500 uppercase tracking-wider">{{ $t('fields.actions') }}</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200 bg-white">
            <tr v-if="loading" class="animate-pulse">
              <td colspan="5" class="px-6 py-10 text-center text-sm text-slate-500">{{ $t('hist.loading') }}</td>
            </tr>
            <tr v-else-if="filteredProposals.length === 0">
              <td colspan="5" class="px-6 py-10 text-center text-sm text-slate-500">
                <div class="flex flex-col items-center justify-center">
                  <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                  <span class="mt-2 block font-medium">{{ $t('hist.none_found') }}</span>
                </div>
              </td>
            </tr>
            <template v-else v-for="proposal in filteredProposals" :key="proposal.id">
              <tr class="hover:bg-slate-50 transition-colors duration-150">
                <td class="whitespace-nowrap px-6 py-4">
                  <div class="text-sm font-semibold text-slate-900">{{ proposal.title }}</div>
                </td>
                <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-600">
                  {{ proposal.domain }}
                </td>
                <td class="whitespace-nowrap px-6 py-4">
                  <div class="inline-flex items-center rounded-md bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700">
                    {{ proposal.department }}
                  </div>
                </td>
                <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-500">
                  {{ formatDate(proposal.created_at) }}
                </td>
                <td class="whitespace-nowrap px-6 py-4 text-end">
                  <button
                    @click="expandedId = expandedId === proposal.id ? null : proposal.id"
                    class="inline-flex items-center gap-1 text-sm font-medium text-teal-600 hover:text-teal-700"
                  >
                    {{ expandedId === proposal.id ? $t('hist.hide_details') : $t('hist.view_details') }}
                    <svg class="h-4 w-4 transition-transform duration-200" :class="{'rotate-180': expandedId === proposal.id}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                  </button>
                </td>
              </tr>
              <!-- Expanded View -->
              <tr v-if="expandedId === proposal.id" class="bg-slate-50/50">
                <td colspan="5" class="px-6 py-6">
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
                    <div>
                      <h4 class="font-semibold text-slate-900 mb-1">{{ $t('fields.problem_statement') }}</h4>
                      <p class="text-slate-600 whitespace-pre-wrap">{{ proposal.problem || $t('hist.not_provided') }}</p>
                    </div>
                    <div>
                      <h4 class="font-semibold text-slate-900 mb-1">{{ $t('fields.proposed_solution') }}</h4>
                      <p class="text-slate-600 whitespace-pre-wrap">{{ proposal.solution || $t('hist.not_provided') }}</p>
                    </div>
                    <div v-if="proposal.objectives">
                      <h4 class="font-semibold text-slate-900 mb-1">{{ $t('student.form.objectives') }}</h4>
                      <p class="text-slate-600 whitespace-pre-wrap">{{ proposal.objectives }}</p>
                    </div>
                    <div v-if="proposal.functions">
                      <h4 class="font-semibold text-slate-900 mb-1">{{ $t('fields.core_functions') }}</h4>
                      <p class="text-slate-600 whitespace-pre-wrap">{{ proposal.functions }}</p>
                    </div>
                    <div v-if="proposal.tags">
                      <h4 class="font-semibold text-slate-900 mb-1">{{ $t('student.form.tags') }}</h4>
                      <div class="flex flex-wrap gap-1.5 mt-1">
                        <span
                          v-for="tag in proposal.tags.split(',').map(t => t.trim()).filter(Boolean)"
                          :key="tag"
                          class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700 border border-slate-200"
                        >
                          {{ tag }}
                        </span>
                      </div>
                    </div>
                    <div v-if="proposal.technologies">
                      <h4 class="font-semibold text-slate-900 mb-1">{{ $t('student.form.tech') }}</h4>
                      <div class="flex flex-wrap gap-1.5 mt-1">
                        <span
                          v-for="tech in proposal.technologies.split(',').map(t => t.trim()).filter(Boolean)"
                          :key="tech"
                          class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-teal-50 text-teal-700 border border-teal-200"
                        >
                          {{ tech }}
                        </span>
                      </div>
                    </div>
                  </div>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useI18n } from 'vue-i18n';

const { locale } = useI18n();

const proposals = ref([]);
const loading = ref(true);
const searchQuery = ref('');
const expandedId = ref(null);

async function fetchPreviousProposals() {
  try {
    loading.value = true;
    const response = await fetch('/previous-proposals');
    if (response.ok) {
      const data = await response.json();
      proposals.value = data.proposals || [];
    }
  } catch (error) {
    console.error('Failed to fetch previous proposals:', error);
  } finally {
    loading.value = false;
  }
}

const filteredProposals = computed(() => {
  if (!searchQuery.value) return proposals.value;
  
  const query = searchQuery.value.toLowerCase();
  return proposals.value.filter(proposal => {
    return proposal.title.toLowerCase().includes(query) ||
           proposal.department.toLowerCase().includes(query) ||
           (proposal.domain && proposal.domain.toLowerCase().includes(query));
  });
});

function formatDate(dateString) {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString(locale.value === 'ar' ? 'ar' : 'en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}

onMounted(() => {
  fetchPreviousProposals();
});
</script>
