<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h2 class="text-xl font-bold text-slate-900 tracking-tight">{{ $t('deptnav.previous_proposals') }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ $t('hist.list_subtitle') }}</p>
      </div>

      <!-- Search & Page Size Controls -->
      <div class="flex flex-wrap items-center gap-3">
        <div class="relative">
          <input
            v-model="searchQuery"
            type="text"
            :placeholder="$t('hist.search_ph')"
            @input="onSearchInput"
            class="block w-full sm:w-64 rounded-xl border-slate-300 ps-10 pe-8 text-sm focus:border-teal-500 focus:ring-teal-500 shadow-sm"
          >
          <div class="pointer-events-none absolute inset-y-0 start-0 flex items-center ps-3">
            <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>
          <button
            v-if="searchQuery"
            @click="clearSearch"
            class="absolute inset-y-0 end-0 flex items-center pe-2.5 text-slate-400 hover:text-slate-600"
          >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <select
          v-model="perPage"
          @change="onPerPageChange"
          class="rounded-xl border-slate-300 text-sm focus:border-teal-500 focus:ring-teal-500 shadow-sm py-2 px-3 bg-white text-slate-700"
        >
          <option :value="15">15</option>
          <option :value="25">25</option>
          <option :value="50">50</option>
          <option :value="100">100</option>
        </select>
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
              <td colspan="5" class="px-6 py-10 text-center text-sm text-slate-500">
                <div class="flex items-center justify-center gap-2">
                  <svg class="h-5 w-5 animate-spin text-teal-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                  <span>{{ $t('hist.loading') }}</span>
                </div>
              </td>
            </tr>
            <tr v-else-if="proposals.length === 0">
              <td colspan="5" class="px-6 py-10 text-center text-sm text-slate-500">
                <div class="flex flex-col items-center justify-center">
                  <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                  <span class="mt-2 block font-medium">{{ $t('hist.none_found') }}</span>
                </div>
              </td>
            </tr>
            <template v-else v-for="proposal in proposals" :key="proposal.id">
              <tr class="hover:bg-slate-50 transition-colors duration-150">
                <td class="px-6 py-4">
                  <div class="text-sm font-semibold text-slate-900 line-clamp-2 max-w-md">{{ proposal.title }}</div>
                </td>
                <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-600">
                  {{ proposal.domain }}
                </td>
                <td class="whitespace-nowrap px-6 py-4">
                  <div class="inline-flex items-center rounded-md bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">
                    {{ proposal.department }}
                  </div>
                </td>
                <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-500">
                  {{ formatDate(proposal.created_at) }}
                </td>
                <td class="whitespace-nowrap px-6 py-4 text-end">
                  <button
                    @click="expandedId = expandedId === proposal.id ? null : proposal.id"
                    class="inline-flex items-center gap-1 text-sm font-medium text-teal-600 hover:text-teal-700 cursor-pointer"
                  >
                    {{ expandedId === proposal.id ? $t('hist.hide_details') : $t('hist.view_details') }}
                    <svg class="h-4 w-4 transition-transform duration-200" :class="{'rotate-180': expandedId === proposal.id}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                  </button>
                </td>
              </tr>
              <!-- Expanded View -->
              <tr v-if="expandedId === proposal.id" class="bg-slate-50/70">
                <td colspan="5" class="px-6 py-6 border-t border-slate-100">
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
                    <div>
                      <h4 class="font-semibold text-slate-900 mb-1.5">{{ $t('fields.problem_statement') }}</h4>
                      <p class="text-slate-600 whitespace-pre-wrap leading-relaxed">{{ proposal.problem || $t('hist.not_provided') }}</p>
                    </div>
                    <div>
                      <h4 class="font-semibold text-slate-900 mb-1.5">{{ $t('fields.proposed_solution') }}</h4>
                      <p class="text-slate-600 whitespace-pre-wrap leading-relaxed">{{ proposal.solution || $t('hist.not_provided') }}</p>
                    </div>
                    <div v-if="proposal.objectives">
                      <h4 class="font-semibold text-slate-900 mb-1.5">{{ $t('student.form.objectives') }}</h4>
                      <p class="text-slate-600 whitespace-pre-wrap leading-relaxed">{{ proposal.objectives }}</p>
                    </div>
                    <div v-if="proposal.functions">
                      <h4 class="font-semibold text-slate-900 mb-1.5">{{ $t('fields.core_functions') }}</h4>
                      <p class="text-slate-600 whitespace-pre-wrap leading-relaxed">{{ proposal.functions }}</p>
                    </div>
                    <div v-if="proposal.tags">
                      <h4 class="font-semibold text-slate-900 mb-1.5">{{ $t('student.form.tags') }}</h4>
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
                      <h4 class="font-semibold text-slate-900 mb-1.5">{{ $t('student.form.tech') }}</h4>
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

      <!-- Pagination Footer -->
      <div v-if="pagination.total > 0" class="border-t border-slate-200 bg-slate-50/50 px-6 py-3.5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="text-xs text-slate-500 font-medium">
          <span v-if="pagination.from && pagination.to">
            {{ pagination.from }} - {{ pagination.to }} / {{ pagination.total }}
          </span>
          <span v-else>
            {{ pagination.total }}
          </span>
        </div>

        <div v-if="pagination.last_page > 1" class="flex items-center gap-1.5">
          <button
            class="inline-flex items-center gap-1 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-xs transition hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer"
            :disabled="currentPage === 1 || loading"
            @click="changePage(currentPage - 1)"
          >
            <svg class="h-3.5 w-3.5 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            {{ $t('common.previous') }}
          </button>

          <span class="px-2 text-xs font-semibold text-slate-600">
            {{ currentPage }} / {{ pagination.last_page }}
          </span>

          <button
            class="inline-flex items-center gap-1 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 shadow-xs transition hover:bg-slate-50 disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer"
            :disabled="currentPage === pagination.last_page || loading"
            @click="changePage(currentPage + 1)"
          >
            {{ $t('common.next') }}
            <svg class="h-3.5 w-3.5 rtl:rotate-180" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useI18n } from 'vue-i18n';

const { locale } = useI18n();

const proposals = ref([]);
const loading = ref(true);
const searchQuery = ref('');
const expandedId = ref(null);
const currentPage = ref(1);
const perPage = ref(25);
const pagination = ref({
  total: 0,
  per_page: 25,
  current_page: 1,
  last_page: 1,
  from: 0,
  to: 0
});

let searchTimeout = null;

async function fetchPreviousProposals(page = 1) {
  try {
    loading.value = true;
    currentPage.value = page;

    const params = new URLSearchParams();
    params.set('page', page);
    params.set('per_page', perPage.value);
    if (searchQuery.value.trim()) {
      params.set('search', searchQuery.value.trim());
    }

    const response = await fetch(`/previous-proposals?${params.toString()}`);
    if (response.ok) {
      const data = await response.json();
      proposals.value = data.proposals || [];
      if (data.pagination) {
        pagination.value = data.pagination;
        currentPage.value = data.pagination.current_page;
      } else {
        pagination.value = {
          total: proposals.value.length,
          per_page: perPage.value,
          current_page: 1,
          last_page: 1,
          from: proposals.value.length > 0 ? 1 : 0,
          to: proposals.value.length
        };
      }
    }
  } catch (error) {
    console.error('Failed to fetch previous proposals:', error);
  } finally {
    loading.value = false;
  }
}

function onSearchInput() {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    fetchPreviousProposals(1);
  }, 300);
}

function clearSearch() {
  searchQuery.value = '';
  fetchPreviousProposals(1);
}

function onPerPageChange() {
  fetchPreviousProposals(1);
}

function changePage(page) {
  if (page < 1 || (pagination.value.last_page && page > pagination.value.last_page)) return;
  fetchPreviousProposals(page);
}

function formatDate(dateString) {
  if (!dateString) return '';
  const date = new Date(dateString);
  return date.toLocaleDateString(locale.value === 'ar' ? 'ar' : 'en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}

onMounted(() => {
  fetchPreviousProposals(1);
});

defineExpose({
  fetchPreviousProposals
});
</script>
