<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h2 class="text-xl font-bold text-slate-900 tracking-tight">{{ $t('hist.title') }}</h2>
        <p class="mt-1 text-sm text-slate-500">{{ $t('hist.subtitle') }}</p>
      </div>
      <div class="flex gap-3">
        <button
          @click="openAddModal"
          class="inline-flex items-center justify-center rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 transition-colors"
        >
          <svg class="me-2 -ms-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          {{ $t('hist.add_single') }}
        </button>
        <button
          @click="showImportModal = true"
          class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 transition-colors"
        >
          <svg class="me-2 -ms-1 h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
          </svg>
          {{ $t('hist.import_csv') }}
        </button>
      </div>
    </div>

    <!-- Include the list component here to see what was added -->
    <HistoricalProposalsList ref="listRef" />

    <!-- Add Single Modal -->
    <div v-if="showAddModal" class="fixed inset-0 z-50 overflow-y-auto">
      <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
        <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="showAddModal = false"></div>

        <div class="relative transform overflow-hidden rounded-2xl bg-white text-start shadow-xl transition-all sm:my-8 w-full max-w-3xl flex flex-col max-h-[90vh]">
          <!-- Header -->
          <div class="border-b border-slate-100 px-6 py-5 bg-slate-50/50 flex justify-between items-center shrink-0">
            <h3 class="text-xl font-bold text-slate-900">
              {{ $t('hist.add_modal_title') }}
            </h3>
            <button @click="showAddModal = false" class="text-slate-400 hover:text-slate-600 transition-colors">
              <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
          </div>

          <!-- Form Body -->
          <div class="px-6 py-6 overflow-y-auto flex-1 space-y-5">
            <!-- Proposal Title -->
            <div>
              <div class="flex justify-between items-center mb-1">
                <label class="block text-sm font-medium text-slate-700">{{ $t('student.form.title') }} <span class="text-red-500">*</span></label>
                <span class="text-xs font-semibold" :class="getWordCount(form.title) < 5 || getWordCount(form.title) > 20 ? 'text-slate-400' : 'text-emerald-600'">
                  {{ $t('student.form.count_words', { count: getWordCount(form.title), max: 20 }) }}
                </span>
              </div>
              <div class="relative rounded-lg shadow-sm">
                <input
                  v-model="form.title"
                  type="text"
                  :class="getFieldClass('title')"
                  class="w-full rounded-lg sm:text-sm px-4 py-2.5 border transition-all duration-200"
                  :placeholder="$t('student.form.title_ph')"
                  @input="handleInput('title')"
                  @blur="handleBlur('title')"
                >
                <div v-if="touched.title" class="absolute inset-y-0 end-0 pe-3 flex items-center pointer-events-none">
                  <span v-if="localErrors.title" class="text-red-500 text-xs">❌</span>
                  <span v-else-if="form.title" class="text-emerald-500 text-xs">✅</span>
                </div>
              </div>
              <p v-if="touched.title && localErrors.title" class="mt-1.5 text-xs text-red-600 font-medium flex items-center gap-1 animate-pulse">
                {{ localErrors.title[0] }}
              </p>
              <p v-else class="mt-1 text-[11px] text-slate-400">{{ $t('student.form.title_hint') }}</p>
            </div>

            <!-- Department & Original Date -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">
                  {{ $t('fields.department') }} <span class="text-red-500">*</span>
                </label>
                <template v-if="isAdmin">
                  <select
                    v-model="form.department_id"
                    :class="getFieldClass('department_id')"
                    class="w-full rounded-lg sm:text-sm px-4 py-2.5 border transition-all duration-200 bg-white"
                    @change="handleInput('department_id')"
                    @blur="handleBlur('department_id')"
                  >
                    <option value="">{{ $t('admin.user_modal.no_department') }}</option>
                    <option v-for="dept in departments" :key="dept.id" :value="dept.id">
                      {{ dept.name }}
                    </option>
                  </select>
                </template>
                <template v-else>
                  <input
                    :value="departmentName"
                    type="text"
                    class="w-full rounded-lg border-slate-200 bg-slate-50 shadow-sm sm:text-sm px-4 py-2.5 border cursor-not-allowed text-slate-500 font-medium"
                    disabled
                  >
                </template>
                <p v-if="touched.department_id && localErrors.department_id" class="mt-1.5 text-xs text-red-600 font-medium flex items-center gap-1">
                  {{ localErrors.department_id[0] }}
                </p>
                <p v-else class="mt-1 text-[11px] text-slate-400">{{ $t('student.form.domain_hint') }}</p>
              </div>

              <div>
                <div class="flex justify-between items-center mb-1">
                  <label class="block text-sm font-medium text-slate-700">{{ $t('hist.original_date') }}</label>
                </div>
                <div class="relative rounded-lg shadow-sm">
                  <input
                    v-model="form.date"
                    type="date"
                    :class="getFieldClass('date')"
                    class="w-full rounded-lg sm:text-sm px-4 py-2.5 border transition-all duration-200"
                    @input="handleInput('date')"
                    @blur="handleBlur('date')"
                  >
                </div>
                <p v-if="touched.date && localErrors.date" class="mt-1.5 text-xs text-red-600 font-medium flex items-center gap-1">
                  {{ localErrors.date[0] }}
                </p>
              </div>
            </div>

            <!-- Problem Statement -->
            <div>
              <div class="flex justify-between items-center mb-1">
                <label class="block text-sm font-medium text-slate-700">{{ $t('fields.problem_statement') }}</label>
                <span class="text-xs font-semibold" :class="getWordCount(form.problem) < 30 || getWordCount(form.problem) > 250 ? 'text-slate-400' : 'text-emerald-600'">
                  {{ $t('student.form.count_words', { count: getWordCount(form.problem), max: 250 }) }}
                </span>
              </div>
              <div class="relative rounded-lg shadow-sm">
                <textarea
                  v-model="form.problem"
                  rows="3"
                  :class="getFieldClass('problem')"
                  class="w-full rounded-lg sm:text-sm px-4 py-2.5 border transition-all duration-200"
                  :placeholder="$t('student.form.problem_ph')"
                  @input="handleInput('problem')"
                  @blur="handleBlur('problem')"
                ></textarea>
                <div v-if="touched.problem" class="absolute end-3 top-3 pointer-events-none">
                  <span v-if="localErrors.problem" class="text-red-500 text-xs">❌</span>
                  <span v-else-if="form.problem" class="text-emerald-500 text-xs">✅</span>
                </div>
              </div>
              <p v-if="touched.problem && localErrors.problem" class="mt-1.5 text-xs text-red-600 font-medium flex items-center gap-1">
                {{ localErrors.problem[0] }}
              </p>
              <p v-else class="mt-1 text-[11px] text-slate-400">{{ $t('student.form.problem_hint') }}</p>
            </div>

            <!-- Proposed Solution -->
            <div>
              <div class="flex justify-between items-center mb-1">
                <label class="block text-sm font-medium text-slate-700">{{ $t('fields.proposed_solution') }}</label>
                <span class="text-xs font-semibold" :class="getWordCount(form.solution) < 30 || getWordCount(form.solution) > 250 ? 'text-slate-400' : 'text-emerald-600'">
                  {{ $t('student.form.count_words', { count: getWordCount(form.solution), max: 250 }) }}
                </span>
              </div>
              <div class="relative rounded-lg shadow-sm">
                <textarea
                  v-model="form.solution"
                  rows="3"
                  :class="getFieldClass('solution')"
                  class="w-full rounded-lg sm:text-sm px-4 py-2.5 border transition-all duration-200"
                  :placeholder="$t('student.form.solution_ph')"
                  @input="handleInput('solution')"
                  @blur="handleBlur('solution')"
                ></textarea>
                <div v-if="touched.solution" class="absolute end-3 top-3 pointer-events-none">
                  <span v-if="localErrors.solution" class="text-red-500 text-xs">❌</span>
                  <span v-else-if="form.solution" class="text-emerald-500 text-xs">✅</span>
                </div>
              </div>
              <p v-if="touched.solution && localErrors.solution" class="mt-1.5 text-xs text-red-600 font-medium flex items-center gap-1">
                {{ localErrors.solution[0] }}
              </p>
              <p v-else class="mt-1 text-[11px] text-slate-400">{{ $t('student.form.solution_hint') }}</p>
            </div>

            <!-- Objectives & Core Functions -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
              <div>
                <div class="flex justify-between items-center mb-1">
                  <label class="block text-sm font-medium text-slate-700">{{ $t('student.form.objectives') }}</label>
                  <span class="text-xs font-semibold" :class="getWordCount(form.objectives) < 20 || getWordCount(form.objectives) > 200 ? 'text-slate-400' : 'text-emerald-600'">
                    {{ $t('student.form.count_words', { count: getWordCount(form.objectives), max: 200 }) }}
                  </span>
                </div>
                <div class="relative rounded-lg shadow-sm">
                  <textarea
                    v-model="form.objectives"
                    rows="3"
                    :class="getFieldClass('objectives')"
                    class="w-full rounded-lg sm:text-sm px-4 py-2.5 border transition-all duration-200"
                    :placeholder="$t('student.form.objectives_ph')"
                    @input="handleInput('objectives')"
                    @blur="handleBlur('objectives')"
                  ></textarea>
                  <div v-if="touched.objectives" class="absolute end-3 top-3 pointer-events-none">
                    <span v-if="localErrors.objectives" class="text-red-500 text-xs">❌</span>
                    <span v-else-if="form.objectives" class="text-emerald-500 text-xs">✅</span>
                  </div>
                </div>
                <p v-if="touched.objectives && localErrors.objectives" class="mt-1.5 text-xs text-red-600 font-medium flex items-center gap-1">
                  {{ localErrors.objectives[0] }}
                </p>
                <p v-else class="mt-1 text-[11px] text-slate-400">{{ $t('student.form.objectives_hint') }}</p>
              </div>

              <div>
                <div class="flex justify-between items-center mb-1">
                  <label class="block text-sm font-medium text-slate-700">{{ $t('fields.core_functions') }}</label>
                  <span class="text-xs font-semibold" :class="getWordCount(form.functions) < 20 || getWordCount(form.functions) > 200 ? 'text-slate-400' : 'text-emerald-600'">
                    {{ $t('student.form.count_words', { count: getWordCount(form.functions), max: 200 }) }}
                  </span>
                </div>
                <div class="relative rounded-lg shadow-sm">
                  <textarea
                    v-model="form.functions"
                    rows="3"
                    :class="getFieldClass('functions')"
                    class="w-full rounded-lg sm:text-sm px-4 py-2.5 border transition-all duration-200"
                    :placeholder="$t('student.form.functions_ph')"
                    @input="handleInput('functions')"
                    @blur="handleBlur('functions')"
                  ></textarea>
                  <div v-if="touched.functions" class="absolute end-3 top-3 pointer-events-none">
                    <span v-if="localErrors.functions" class="text-red-500 text-xs">❌</span>
                    <span v-else-if="form.functions" class="text-emerald-500 text-xs">✅</span>
                  </div>
                </div>
                <p v-if="touched.functions && localErrors.functions" class="mt-1.5 text-xs text-red-600 font-medium flex items-center gap-1">
                  {{ localErrors.functions[0] }}
                </p>
                <p v-else class="mt-1 text-[11px] text-slate-400">{{ $t('student.form.functions_hint') }}</p>
              </div>
            </div>

            <!-- Tags & Technologies Used -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
              <div>
                <div class="flex justify-between items-center mb-1">
                  <label class="block text-sm font-medium text-slate-700">{{ $t('student.form.tags') }}</label>
                  <span class="text-xs font-semibold" :class="getTagCount(form.tags) < 3 || getTagCount(form.tags) > 10 ? 'text-slate-400' : 'text-emerald-600'">
                    {{ $t('student.form.count_tags', { count: getTagCount(form.tags), max: 10 }) }}
                  </span>
                </div>
                <div class="relative rounded-lg shadow-sm">
                  <input
                    v-model="form.tags"
                    type="text"
                    :class="getFieldClass('tags')"
                    class="w-full rounded-lg sm:text-sm px-4 py-2.5 border transition-all duration-200"
                    :placeholder="$t('student.form.tags_ph')"
                    @input="handleInput('tags')"
                    @blur="handleBlur('tags')"
                  >
                  <div v-if="touched.tags" class="absolute inset-y-0 end-0 pe-3 flex items-center pointer-events-none">
                    <span v-if="localErrors.tags" class="text-red-500 text-xs">❌</span>
                    <span v-else-if="form.tags" class="text-emerald-500 text-xs">✅</span>
                  </div>
                </div>
                <p v-if="touched.tags && localErrors.tags" class="mt-1.5 text-xs text-red-600 font-medium flex items-center gap-1">
                  {{ localErrors.tags[0] }}
                </p>
                <p v-else class="mt-1 text-[11px] text-slate-400">{{ $t('student.form.tags_hint') }}</p>
              </div>

              <div>
                <div class="flex justify-between items-center mb-1">
                  <label class="block text-sm font-medium text-slate-700">{{ $t('student.form.tech') }}</label>
                  <span class="text-xs font-semibold" :class="getTagCount(form.technologies) < 2 || getTagCount(form.technologies) > 12 ? 'text-slate-400' : 'text-emerald-600'">
                    {{ $t('student.form.count_tech', { count: getTagCount(form.technologies), max: 12 }) }}
                  </span>
                </div>
                <div class="relative rounded-lg shadow-sm">
                  <input
                    v-model="form.technologies"
                    type="text"
                    :class="getFieldClass('technologies')"
                    class="w-full rounded-lg sm:text-sm px-4 py-2.5 border transition-all duration-200"
                    :placeholder="$t('student.form.tech_ph')"
                    @input="handleInput('technologies')"
                    @blur="handleBlur('technologies')"
                  >
                  <div v-if="touched.technologies" class="absolute inset-y-0 end-0 pe-3 flex items-center pointer-events-none">
                    <span v-if="localErrors.technologies" class="text-red-500 text-xs">❌</span>
                    <span v-else-if="form.technologies" class="text-emerald-500 text-xs">✅</span>
                  </div>
                </div>
                <p v-if="touched.technologies && localErrors.technologies" class="mt-1.5 text-xs text-red-600 font-medium flex items-center gap-1">
                  {{ localErrors.technologies[0] }}
                </p>
                <p v-else class="mt-1 text-[11px] text-slate-400">{{ $t('student.form.tech_hint') }}</p>
              </div>
            </div>
          </div>

          <!-- Footer -->
          <div class="bg-slate-50 px-6 py-4 flex flex-wrap-reverse sm:flex-nowrap justify-between gap-3 border-t border-slate-100 shrink-0">
            <button @click="showAddModal = false" type="button" class="w-full sm:w-auto inline-flex justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 focus:outline-none transition-colors">
              {{ $t('common.cancel') }}
            </button>
            <button @click="submitAdd" :disabled="submitting" type="button" class="w-full sm:w-auto inline-flex justify-center rounded-lg border border-transparent bg-teal-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-teal-700 focus:outline-none transition-colors disabled:opacity-50">
              {{ submitting ? $t('common.saving') : $t('hist.save') }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Import Modal -->
    <div v-if="showImportModal" class="relative z-50" aria-labelledby="modal-title" role="dialog" aria-modal="true">
      <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity"></div>
      <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
          <div class="relative transform overflow-hidden rounded-xl bg-white text-start shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
            <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
              <div class="sm:flex sm:items-start">
                <div class="mt-3 text-center sm:ms-4 sm:mt-0 sm:text-start w-full">
                  <h3 class="text-lg font-bold leading-6 text-slate-900" id="modal-title">{{ $t('hist.import_title') }}</h3>
                  <div class="mt-4">
                    <p class="text-sm text-slate-500 mb-4">
                      {{ $t('hist.expected_columns') }} <br>
                      <code class="text-xs bg-slate-100 p-1 rounded">Title, Tags, Problem, Solution, Objectives, Functions, Technologies, Date(YYYY-MM-DD), DeptID(Admin only)</code>
                    </p>
                    <input type="file" ref="fileInput" accept=".csv" class="block w-full text-sm text-slate-500 file:me-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100" />
                  </div>
                </div>
              </div>
            </div>
            <div class="bg-slate-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
              <button @click="submitImport" type="button" class="inline-flex w-full justify-center rounded-lg bg-teal-600 px-3 py-2 text-sm font-medium text-white shadow-sm hover:bg-teal-700 sm:ms-3 sm:w-auto">
                {{ $t('hist.upload') }}
              </button>
              <button @click="showImportModal = false" type="button" class="mt-3 inline-flex w-full justify-center rounded-lg bg-white px-3 py-2 text-sm font-medium text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:mt-0 sm:w-auto">
                {{ $t('common.cancel') }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useToast } from "vue-toastification";
import { useI18n } from 'vue-i18n';
import HistoricalProposalsList from './HistoricalProposalsList.vue';

const toast = useToast();
const { t } = useI18n();
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
const authUser = window.authUser || {};
const isAdmin = computed(() => authUser.role === 'admin');
const departmentName = computed(() => authUser.department?.department_name || authUser.department_name || authUser.department || 'Department');

const listRef = ref(null);
const showAddModal = ref(false);
const showImportModal = ref(false);
const fileInput = ref(null);
const submitting = ref(false);
const departments = ref([]);

const form = ref({
  title: '',
  department_id: '',
  date: new Date().toISOString().split('T')[0],
  problem: '',
  solution: '',
  objectives: '',
  functions: '',
  tags: '',
  technologies: ''
});

const touched = ref({
  title: false,
  department_id: false,
  date: false,
  problem: false,
  solution: false,
  objectives: false,
  functions: false,
  tags: false,
  technologies: false,
});

const localErrors = ref({});

const getWordCount = (val) => {
  if (!val) return 0;
  const trimmed = val.trim();
  return trimmed === '' ? 0 : trimmed.split(/\s+/).length;
};

const getTagCount = (val) => {
  if (!val) return 0;
  return val.split(',').map(t => t.trim()).filter(t => t !== '').length;
};

const validateField = (field) => {
  const val = form.value[field] || '';
  let errMsg = '';

  if (field === 'title') {
    if (!val.trim()) {
      errMsg = t('student.form.errors.title_required');
    } else {
      const count = getWordCount(val);
      if (count < 5) {
        errMsg = t('student.form.errors.title_min');
      } else if (count > 20) {
        errMsg = t('student.form.errors.title_max');
      }
    }
  } else if (field === 'department_id') {
    if (isAdmin.value && !val) {
      errMsg = t('hist.toast.dept_required');
    }
  } else if (field === 'date') {
    if (!val) {
      errMsg = t('hist.toast.title_date_required');
    }
  } else if (field === 'problem') {
    if (val.trim()) {
      const count = getWordCount(val);
      if (count < 30) {
        errMsg = t('student.form.errors.problem_min');
      } else if (count > 250) {
        errMsg = t('student.form.errors.problem_max');
      }
    }
  } else if (field === 'solution') {
    if (val.trim()) {
      const count = getWordCount(val);
      if (count < 30) {
        errMsg = t('student.form.errors.solution_min');
      } else if (count > 250) {
        errMsg = t('student.form.errors.solution_max');
      }
    }
  } else if (field === 'functions') {
    if (val.trim()) {
      const count = getWordCount(val);
      if (count < 20) {
        errMsg = t('student.form.errors.functions_min');
      } else if (count > 200) {
        errMsg = t('student.form.errors.functions_max');
      }
    }
  } else if (field === 'objectives') {
    if (val.trim()) {
      const count = getWordCount(val);
      if (count < 20) {
        errMsg = t('student.form.errors.objectives_min');
      } else if (count > 200) {
        errMsg = t('student.form.errors.objectives_max');
      }
    }
  } else if (field === 'tags') {
    if (val.trim()) {
      const count = getTagCount(val);
      if (count < 3) {
        errMsg = t('student.form.errors.tags_min');
      } else if (count > 10) {
        errMsg = t('student.form.errors.tags_max');
      }
    }
  } else if (field === 'technologies') {
    if (val.trim()) {
      const count = getTagCount(val);
      if (count < 2) {
        errMsg = t('student.form.errors.tech_min');
      } else if (count > 12) {
        errMsg = t('student.form.errors.tech_max');
      }
    }
  }

  if (errMsg) {
    localErrors.value[field] = [errMsg];
  } else {
    delete localErrors.value[field];
  }
};

const handleInput = (field) => {
  if (touched.value[field]) {
    validateField(field);
  }
};

const handleBlur = (field) => {
  touched.value[field] = true;
  validateField(field);
};

const getFieldClass = (field) => {
  const isTouched = touched.value[field];
  const hasErr = localErrors.value[field] && localErrors.value[field].length > 0;
  const hasValue = (form.value[field] || '').toString().trim() !== '';

  if (isTouched && hasErr) {
    return 'border-red-300 focus:border-red-500 focus:ring-red-500/20';
  }
  if (isTouched && hasValue && !hasErr) {
    return 'border-emerald-300 focus:border-emerald-500 focus:ring-emerald-500/20';
  }
  return 'border-slate-300 focus:border-teal-500 focus:ring-teal-500/20';
};

const resetForm = () => {
  form.value = {
    title: '',
    department_id: '',
    date: new Date().toISOString().split('T')[0],
    problem: '',
    solution: '',
    objectives: '',
    functions: '',
    tags: '',
    technologies: ''
  };
  localErrors.value = {};
  Object.keys(touched.value).forEach(k => touched.value[k] = false);
};

const openAddModal = () => {
  resetForm();
  showAddModal.value = true;
};

async function fetchDepartments() {
  if (!isAdmin.value) return;
  try {
    const res = await fetch('/admin/departments', {
      headers: { Accept: 'application/json' }
    });
    if (res.ok) {
      const data = await res.json();
      departments.value = data.departments || [];
    }
  } catch (e) {
    console.error('Failed to load departments', e);
  }
}

async function submitAdd() {
  Object.keys(touched.value).forEach(k => {
    touched.value[k] = true;
    validateField(k);
  });

  if (Object.keys(localErrors.value).length > 0) {
    toast.error(t('hist.toast.error'));
    return;
  }

  if (!form.value.title || !form.value.date) {
    toast.error(t('hist.toast.title_date_required'));
    return;
  }

  if (isAdmin.value && !form.value.department_id) {
    toast.error(t('hist.toast.dept_required'));
    return;
  }

  submitting.value = true;
  const endpoint = isAdmin.value ? '/admin/previous-proposals' : '/department/previous-proposals';

  try {
    const res = await fetch(endpoint, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        Accept: 'application/json'
      },
      body: JSON.stringify(form.value)
    });

    if (res.ok) {
      toast.success(t('hist.toast.added'));
      showAddModal.value = false;
      resetForm();
      if (listRef.value) {
        listRef.value.fetchPreviousProposals();
      }
    } else {
      const data = await res.json();
      if (data.errors) {
        localErrors.value = data.errors;
      }
      toast.error(data.message || t('hist.toast.add_failed'));
    }
  } catch (err) {
    toast.error(t('hist.toast.error'));
  } finally {
    submitting.value = false;
  }
}

async function submitImport() {
  const file = fileInput.value?.files[0];
  if (!file) {
    toast.error(t('hist.toast.select_file'));
    return;
  }

  const formData = new FormData();
  formData.append('file', file);

  const endpoint = isAdmin.value ? '/admin/previous-proposals/import' : '/department/previous-proposals/import';

  try {
    const res = await fetch(endpoint, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': csrfToken
      },
      body: formData
    });

    if (res.ok) {
      const data = await res.json();
      toast.success(data.message || t('hist.toast.import_success'));
      showImportModal.value = false;
      fileInput.value.value = '';
      if (listRef.value) {
        listRef.value.fetchPreviousProposals();
      }
    } else {
      const data = await res.json();
      toast.error(data.message || t('hist.toast.import_failed'));
    }
  } catch (err) {
    toast.error(t('hist.toast.import_error'));
  }
}

onMounted(() => {
  fetchDepartments();
});
</script>
