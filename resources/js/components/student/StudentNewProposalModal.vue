<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
      <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="$emit('close')"></div>

      <div class="relative transform overflow-hidden rounded-2xl bg-white text-start shadow-xl transition-all sm:my-8 w-full max-w-3xl flex flex-col max-h-[90vh]">
        <!-- Header -->
        <div class="border-b border-slate-100 px-6 py-5 bg-slate-50/50 flex justify-between items-center shrink-0">
          <h3 class="text-xl font-bold text-slate-900">
            {{ isEditing ? $t('student.form.edit_title') : $t('student.form.new_title') }}
          </h3>
          <button @click="$emit('close')" class="text-slate-400 hover:text-slate-600 transition-colors">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
          </button>
        </div>

        <!-- Form Body -->
        <div class="px-6 py-6 overflow-y-auto flex-1 space-y-5">
          <!-- Proposal Title -->
          <div>
            <div class="flex justify-between items-center mb-1">
              <label class="block text-sm font-medium text-slate-700">{{ $t('student.form.title') }}</label>
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

          <!-- Domain Specialization (Read-Only) -->
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">{{ $t('student.form.domain') }}</label>
            <input
              :value="studentDepartment"
              type="text"
              class="w-full rounded-lg border-slate-200 bg-slate-50 shadow-sm sm:text-sm px-4 py-2 border cursor-not-allowed text-slate-500 font-medium"
              disabled
            >
            <p class="mt-1 text-[11px] text-slate-400 italic">{{ $t('student.form.domain_hint') }}</p>
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
                <span class="text-xs font-semibold" :class="getTagCount(form.tech) < 2 || getTagCount(form.tech) > 12 ? 'text-slate-400' : 'text-emerald-600'">
                  {{ $t('student.form.count_tech', { count: getTagCount(form.tech), max: 12 }) }}
                </span>
              </div>
              <div class="relative rounded-lg shadow-sm">
                <input
                  v-model="form.tech"
                  type="text"
                  :class="getFieldClass('tech')"
                  class="w-full rounded-lg sm:text-sm px-4 py-2.5 border transition-all duration-200"
                  :placeholder="$t('student.form.tech_ph')"
                  @input="handleInput('tech')"
                  @blur="handleBlur('tech')"
                >
                <div v-if="touched.tech" class="absolute inset-y-0 end-0 pe-3 flex items-center pointer-events-none">
                  <span v-if="localErrors.tech" class="text-red-500 text-xs">❌</span>
                  <span v-else-if="form.tech" class="text-emerald-500 text-xs">✅</span>
                </div>
              </div>
              <p v-if="touched.tech && localErrors.tech" class="mt-1.5 text-xs text-red-600 font-medium flex items-center gap-1">
                {{ localErrors.tech[0] }}
              </p>
              <p v-else class="mt-1 text-[11px] text-slate-400">{{ $t('student.form.tech_hint') }}</p>
            </div>
          </div>

          <!-- Academic Supervisor -->
          <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-5 space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
              <div>
                <h4 class="text-base font-bold text-slate-900">{{ $t('student.form.supervisor.section') }}</h4>
                <p class="mt-1 text-[11px] text-slate-500 leading-relaxed">{{ $t('student.form.supervisor.section_hint') }}</p>
              </div>
              <a
                href="/student/supervisor-approval-template"
                download
                class="shrink-0 inline-flex items-center justify-center gap-2 rounded-lg border border-teal-600 bg-white px-3.5 py-2 text-xs font-semibold text-teal-700 shadow-sm hover:bg-teal-50 transition-colors"
              >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                {{ $t('student.form.supervisor.download_template') }}
              </a>
            </div>

            <!-- Supervisor Name -->
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">{{ $t('student.form.supervisor.name') }}</label>
              <div class="relative rounded-lg shadow-sm">
                <input
                  v-model="form.supervisor_name"
                  type="text"
                  maxlength="150"
                  :class="getFieldClass('supervisor_name')"
                  class="w-full rounded-lg sm:text-sm px-4 py-2.5 border bg-white transition-all duration-200"
                  :placeholder="$t('student.form.supervisor.name_ph')"
                  @input="handleInput('supervisor_name')"
                  @blur="handleBlur('supervisor_name')"
                >
                <div v-if="touched.supervisor_name" class="absolute inset-y-0 end-0 pe-3 flex items-center pointer-events-none">
                  <span v-if="localErrors.supervisor_name" class="text-red-500 text-xs">❌</span>
                  <span v-else-if="form.supervisor_name" class="text-emerald-500 text-xs">✅</span>
                </div>
              </div>
              <p v-if="touched.supervisor_name && localErrors.supervisor_name" class="mt-1.5 text-xs text-red-600 font-medium">
                {{ localErrors.supervisor_name[0] }}
              </p>
            </div>

            <!-- Signed Approval Upload -->
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1">{{ $t('student.form.supervisor.upload') }}</label>
              <input
                ref="approvalInput"
                type="file"
                accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                class="hidden"
                @change="onApprovalSelected"
              >

              <!-- A file is selected or already stored -->
              <div
                v-if="approvalFileName"
                class="flex flex-col sm:flex-row sm:items-center gap-3 rounded-lg border bg-white px-4 py-3"
                :class="touched.supervisor_approval && localErrors.supervisor_approval ? 'border-red-300' : 'border-slate-200'"
              >
                <div class="flex items-center gap-3 min-w-0 flex-1">
                  <div class="h-9 w-9 shrink-0 rounded-lg bg-teal-50 text-teal-700 flex items-center justify-center text-[10px] font-bold uppercase">
                    {{ approvalExtension }}
                  </div>
                  <div class="min-w-0">
                    <p class="text-sm font-medium text-slate-800 truncate" :title="approvalFileName" dir="auto">{{ approvalFileName }}</p>
                    <p class="text-[11px] text-slate-400">
                      <template v-if="form.supervisor_approval_file">{{ formatSize(form.supervisor_approval_file.size) }} · {{ $t('student.form.supervisor.new_file') }}</template>
                      <template v-else>{{ formatSize(form.supervisor_approval?.size) }}<template v-if="form.supervisor_approval?.uploaded_at"> · {{ $t('student.form.supervisor.uploaded_on', { date: formatDate(form.supervisor_approval.uploaded_at) }) }}</template></template>
                    </p>
                  </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                  <button type="button" @click="previewApproval" class="rounded-md border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                    {{ $t('student.form.supervisor.preview') }}
                  </button>
                  <button type="button" @click="approvalInput?.click()" class="rounded-md border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                    {{ $t('student.form.supervisor.replace') }}
                  </button>
                  <button type="button" @click="removeApproval" class="rounded-md border border-red-200 bg-white px-2.5 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50 transition-colors">
                    {{ $t('student.form.supervisor.remove') }}
                  </button>
                </div>
              </div>

              <!-- Nothing selected -->
              <button
                v-else
                type="button"
                @click="approvalInput?.click()"
                class="w-full flex flex-col items-center justify-center gap-1 rounded-lg border-2 border-dashed bg-white px-4 py-5 text-center hover:border-teal-400 hover:bg-teal-50/40 transition-colors"
                :class="touched.supervisor_approval && localErrors.supervisor_approval ? 'border-red-300' : 'border-slate-300'"
              >
                <svg class="h-6 w-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                <span class="text-sm font-medium text-teal-700">{{ $t('student.form.supervisor.choose_file') }}</span>
                <span v-if="form.remove_supervisor_approval && form.supervisor_approval" class="text-[11px] text-amber-600">
                  {{ $t('student.form.supervisor.will_remove') }}
                  <span class="underline cursor-pointer" @click.stop="undoRemoveApproval">{{ $t('student.form.supervisor.undo_remove') }}</span>
                </span>
              </button>

              <p v-if="touched.supervisor_approval && localErrors.supervisor_approval" class="mt-1.5 text-xs text-red-600 font-medium">
                {{ localErrors.supervisor_approval[0] }}
              </p>
              <p v-else class="mt-1 text-[11px] text-slate-400">{{ $t('student.form.supervisor.upload_hint') }}</p>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="bg-slate-50 px-6 py-4 flex flex-wrap-reverse sm:flex-nowrap justify-between gap-3 border-t border-slate-100 shrink-0">
          <button @click="$emit('close')" type="button" class="w-full sm:w-auto inline-flex justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 focus:outline-none transition-colors">
            {{ $t('common.cancel') }}
          </button>
          <div class="flex gap-3 w-full sm:w-auto">
            <template v-if="isEditing">
              <button @click="onUpdate" type="button" class="w-full sm:w-auto inline-flex justify-center rounded-lg border border-transparent bg-teal-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-teal-700 focus:outline-none transition-colors">
                {{ $t('student.form.update') }}
              </button>
            </template>
            <template v-else>
              <button @click="onSaveDraft" type="button" class="w-full sm:w-auto inline-flex justify-center rounded-lg border border-teal-600 bg-white px-4 py-2.5 text-sm font-medium text-teal-700 shadow-sm hover:bg-teal-50 focus:outline-none transition-colors">
                {{ $t('student.form.save_draft') }}
              </button>
              <button @click="onConfirmProposal" type="button" class="w-full sm:w-auto inline-flex justify-center rounded-lg border border-transparent bg-teal-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-teal-700 focus:outline-none transition-colors">
                {{ $t('student.form.confirm') }}
              </button>
            </template>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';

const { t } = useI18n();

const props = defineProps({
  isOpen: {
    type: Boolean,
    required: true,
  },
  form: {
    type: Object,
    required: true,
  },
  isEditing: {
    type: Boolean,
    default: false,
  },
  studentDepartment: {
    type: String,
    default: '',
  },
  errors: {
    type: Object,
    default: () => ({}),
  },
});

const emit = defineEmits(['close', 'save-draft', 'confirm-proposal', 'update']);

const touched = ref({
  title: false,
  problem: false,
  solution: false,
  functions: false,
  objectives: false,
  tags: false,
  tech: false,
  supervisor_name: false,
  supervisor_approval: false,
});

// ── Supervisor approval document ─────────────────────────────────────────
const APPROVAL_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png'];
const APPROVAL_MAX_BYTES = 5 * 1024 * 1024;
const approvalInput = ref(null);

// The approval the proposal will have after saving: a newly picked file,
// otherwise the stored one (unless the student chose to remove it).
const hasApproval = () =>
  !!props.form.supervisor_approval_file ||
  (!!props.form.supervisor_approval && !props.form.remove_supervisor_approval);

const approvalFileName = computed(() => {
  if (props.form.supervisor_approval_file) return props.form.supervisor_approval_file.name;
  if (props.form.supervisor_approval && !props.form.remove_supervisor_approval) return props.form.supervisor_approval.name;
  return '';
});

const approvalExtension = computed(() => (approvalFileName.value.split('.').pop() || '').slice(0, 4));

const formatSize = (bytes) => {
  if (!bytes) return '';
  if (bytes < 1024 * 1024) return `${Math.max(1, Math.round(bytes / 1024))} KB`;
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
};

const formatDate = (iso) => (iso ? new Date(iso).toLocaleDateString() : '');

const onApprovalSelected = (event) => {
  const file = event.target.files?.[0];
  event.target.value = ''; // allow re-selecting the same file later
  if (!file) return;

  touched.value.supervisor_approval = true;
  const ext = (file.name.split('.').pop() || '').toLowerCase();
  if (!APPROVAL_EXTENSIONS.includes(ext)) {
    localErrors.value.supervisor_approval = [t('student.form.supervisor.errors.file_type')];
    return;
  }
  if (file.size > APPROVAL_MAX_BYTES) {
    localErrors.value.supervisor_approval = [t('student.form.supervisor.errors.file_size')];
    return;
  }

  props.form.supervisor_approval_file = file;
  delete localErrors.value.supervisor_approval;
};

const previewApproval = () => {
  if (props.form.supervisor_approval_file) {
    const url = URL.createObjectURL(props.form.supervisor_approval_file);
    window.open(url, '_blank', 'noopener');
    setTimeout(() => URL.revokeObjectURL(url), 60_000);
  } else if (props.form.supervisor_approval?.preview_url) {
    window.open(props.form.supervisor_approval.preview_url, '_blank', 'noopener');
  }
};

const removeApproval = () => {
  if (props.form.supervisor_approval_file) {
    // Dropping a not-yet-uploaded file just reverts to the stored one (if any).
    props.form.supervisor_approval_file = null;
  } else {
    props.form.remove_supervisor_approval = true;
  }
};

const undoRemoveApproval = () => {
  props.form.remove_supervisor_approval = false;
  delete localErrors.value.supervisor_approval;
};

const localErrors = ref({});

// Watch backend errors and merge them
watch(() => props.errors, (newVal) => {
  if (newVal && Object.keys(newVal).length > 0) {
    localErrors.value = { ...localErrors.value, ...newVal };
    Object.keys(newVal).forEach(field => {
      touched.value[field] = true;
    });
  }
}, { deep: true });

// Reset state on open/close
watch(() => props.isOpen, (isOpen) => {
  if (isOpen) {
    localErrors.value = {};
    Object.keys(touched.value).forEach(k => touched.value[k] = false);
  }
});

const getWordCount = (val) => {
  if (!val) return 0;
  const trimmed = val.trim();
  return trimmed === '' ? 0 : trimmed.split(/\s+/).length;
};

const getTagCount = (val) => {
  if (!val) return 0;
  return val.split(',').map(t => t.trim()).filter(t => t !== '').length;
};

const validateField = (field, isStrict = false) => {
  if (field === 'supervisor_approval') {
    // Invalid picks are rejected (and reported) in onApprovalSelected and never
    // attached, so here we only enforce presence for a submission.
    if (isStrict && !hasApproval()) {
      localErrors.value[field] = [t('student.form.supervisor.errors.file_required')];
    } else {
      delete localErrors.value[field];
    }
    return;
  }

  const val = props.form[field] || '';
  let errMsg = '';

  if (field === 'supervisor_name') {
    if (isStrict && !val.trim()) {
      errMsg = t('student.form.supervisor.errors.name_required');
    } else if (val.trim().length > 150) {
      errMsg = t('student.form.supervisor.errors.name_max');
    }
  } else if (field === 'title') {
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
  } else if (field === 'problem') {
    if (isStrict && !val.trim()) {
      errMsg = t('student.form.errors.problem_required');
    } else if (val.trim()) {
      const count = getWordCount(val);
      if (count < 30) {
        errMsg = t('student.form.errors.problem_min');
      } else if (count > 250) {
        errMsg = t('student.form.errors.problem_max');
      }
    }
  } else if (field === 'solution') {
    if (isStrict && !val.trim()) {
      errMsg = t('student.form.errors.solution_required');
    } else if (val.trim()) {
      const count = getWordCount(val);
      if (count < 30) {
        errMsg = t('student.form.errors.solution_min');
      } else if (count > 250) {
        errMsg = t('student.form.errors.solution_max');
      }
    }
  } else if (field === 'functions') {
    if (isStrict && !val.trim()) {
      errMsg = t('student.form.errors.functions_min');
    } else if (val.trim()) {
      const count = getWordCount(val);
      if (count < 20) {
        errMsg = t('student.form.errors.functions_min');
      } else if (count > 200) {
        errMsg = t('student.form.errors.functions_max');
      }
    }
  } else if (field === 'objectives') {
    if (isStrict && !val.trim()) {
      errMsg = t('student.form.errors.objectives_min');
    } else if (val.trim()) {
      const count = getWordCount(val);
      if (count < 20) {
        errMsg = t('student.form.errors.objectives_min');
      } else if (count > 200) {
        errMsg = t('student.form.errors.objectives_max');
      }
    }
  } else if (field === 'tags') {
    if (isStrict && !val.trim()) {
      errMsg = t('student.form.errors.tags_min');
    } else if (val.trim()) {
      const count = getTagCount(val);
      if (count < 3) {
        errMsg = t('student.form.errors.tags_min');
      } else if (count > 10) {
        errMsg = t('student.form.errors.tags_max');
      }
    }
  } else if (field === 'tech') {
    if (isStrict && !val.trim()) {
      errMsg = t('student.form.errors.tech_min');
    } else if (val.trim()) {
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
    const isStrict = props.form.submission_status === 'submitted';
    validateField(field, isStrict);
  }
};

const handleBlur = (field) => {
  touched.value[field] = true;
  const isStrict = props.form.submission_status === 'submitted';
  validateField(field, isStrict);
};

const getFieldClass = (field) => {
  const isTouched = touched.value[field];
  const hasErr = localErrors.value[field] && localErrors.value[field].length > 0;
  const hasValue = (props.form[field] || '').trim() !== '';

  if (isTouched && hasErr) {
    return 'border-red-300 focus:border-red-500 focus:ring-red-500/20';
  }
  if (isTouched && hasValue && !hasErr) {
    return 'border-emerald-300 focus:border-emerald-500 focus:ring-emerald-500/20';
  }
  return 'border-slate-300 focus:border-teal-500 focus:ring-teal-500/20';
};

const onSaveDraft = () => {
  touched.value.title = true;
  validateField('title', false);

  // Validate other fields optionally if filled
  Object.keys(touched.value).forEach(k => {
    if (k !== 'title') {
      validateField(k, false);
    }
  });

  if (Object.keys(localErrors.value).length > 0) {
    return;
  }
  emit('save-draft');
};

const onConfirmProposal = () => {
  Object.keys(touched.value).forEach(k => {
    touched.value[k] = true;
    validateField(k, true);
  });

  if (Object.keys(localErrors.value).length > 0) {
    return;
  }
  emit('confirm-proposal');
};

const onUpdate = () => {
  const isStrict = props.form.submission_status === 'submitted';
  Object.keys(touched.value).forEach(k => {
    touched.value[k] = true;
    validateField(k, isStrict);
  });

  if (Object.keys(localErrors.value).length > 0) {
    return;
  }
  emit('update');
};
</script>
