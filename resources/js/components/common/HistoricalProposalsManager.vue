<template>
  <div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
      <div>
        <h2 class="text-xl font-bold text-slate-900 tracking-tight">Manage Historical Proposals</h2>
        <p class="mt-1 text-sm text-slate-500">Add or import previous accepted proposals to serve as examples for students.</p>
      </div>
      <div class="flex gap-3">
        <button 
          @click="showAddModal = true"
          class="inline-flex items-center justify-center rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2 transition-colors"
        >
          <svg class="mr-2 -ml-1 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Add Single
        </button>
        <button 
          @click="showImportModal = true"
          class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 transition-colors"
        >
          <svg class="mr-2 -ml-1 h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
          </svg>
          Import CSV
        </button>
      </div>
    </div>

    <!-- Include the list component here to see what was added -->
    <HistoricalProposalsList ref="listRef" />

    <!-- Add Single Modal -->
    <div v-if="showAddModal" class="relative z-50" aria-labelledby="modal-title" role="dialog" aria-modal="true">
      <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity"></div>
      <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
          <div class="relative transform overflow-hidden rounded-xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl">
            <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
              <div class="sm:flex sm:items-start">
                <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                  <h3 class="text-lg font-bold leading-6 text-slate-900" id="modal-title">Add Historical Proposal</h3>
                  <div class="mt-4 space-y-4">
                    
                    <div v-if="isAdmin">
                      <label class="block text-sm font-medium text-slate-700 mb-1">Department ID</label>
                      <input v-model="form.department_id" type="number" class="block w-full rounded-lg border-slate-300 text-sm focus:border-teal-500 focus:ring-teal-500" placeholder="e.g. 1">
                    </div>

                    <div>
                      <label class="block text-sm font-medium text-slate-700 mb-1">Title *</label>
                      <input v-model="form.title" type="text" class="block w-full rounded-lg border-slate-300 text-sm focus:border-teal-500 focus:ring-teal-500" placeholder="Project Title">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                      <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Domain</label>
                        <input v-model="form.domain" type="text" class="block w-full rounded-lg border-slate-300 text-sm focus:border-teal-500 focus:ring-teal-500" placeholder="e.g. AI, Web">
                      </div>
                      <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Original Date *</label>
                        <input v-model="form.date" type="date" class="block w-full rounded-lg border-slate-300 text-sm focus:border-teal-500 focus:ring-teal-500">
                      </div>
                    </div>

                    <div>
                      <label class="block text-sm font-medium text-slate-700 mb-1">Problem Statement</label>
                      <textarea v-model="form.problem" rows="3" class="block w-full rounded-lg border-slate-300 text-sm focus:border-teal-500 focus:ring-teal-500"></textarea>
                    </div>

                    <div>
                      <label class="block text-sm font-medium text-slate-700 mb-1">Proposed Solution</label>
                      <textarea v-model="form.solution" rows="3" class="block w-full rounded-lg border-slate-300 text-sm focus:border-teal-500 focus:ring-teal-500"></textarea>
                    </div>

                  </div>
                </div>
              </div>
            </div>
            <div class="bg-slate-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
              <button @click="submitAdd" type="button" class="inline-flex w-full justify-center rounded-lg bg-teal-600 px-3 py-2 text-sm font-medium text-white shadow-sm hover:bg-teal-700 sm:ml-3 sm:w-auto">
                Save Proposal
              </button>
              <button @click="showAddModal = false" type="button" class="mt-3 inline-flex w-full justify-center rounded-lg bg-white px-3 py-2 text-sm font-medium text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:mt-0 sm:w-auto">
                Cancel
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Import Modal -->
    <div v-if="showImportModal" class="relative z-50" aria-labelledby="modal-title" role="dialog" aria-modal="true">
      <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity"></div>
      <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
          <div class="relative transform overflow-hidden rounded-xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
            <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
              <div class="sm:flex sm:items-start">
                <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                  <h3 class="text-lg font-bold leading-6 text-slate-900" id="modal-title">Import via CSV</h3>
                  <div class="mt-4">
                    <p class="text-sm text-slate-500 mb-4">
                      Expected columns (no header): <br>
                      <code class="text-xs bg-slate-100 p-1 rounded">Title, Domain, Problem, Solution, Objectives, Functions, Technologies, Date(YYYY-MM-DD), DeptID(Admin only)</code>
                    </p>
                    <input type="file" ref="fileInput" accept=".csv" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100" />
                  </div>
                </div>
              </div>
            </div>
            <div class="bg-slate-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
              <button @click="submitImport" type="button" class="inline-flex w-full justify-center rounded-lg bg-teal-600 px-3 py-2 text-sm font-medium text-white shadow-sm hover:bg-teal-700 sm:ml-3 sm:w-auto">
                Upload CSV
              </button>
              <button @click="showImportModal = false" type="button" class="mt-3 inline-flex w-full justify-center rounded-lg bg-white px-3 py-2 text-sm font-medium text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:mt-0 sm:w-auto">
                Cancel
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useToast } from "vue-toastification";
import HistoricalProposalsList from './HistoricalProposalsList.vue';

const toast = useToast();
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
const authUser = window.authUser || {};
const isAdmin = computed(() => authUser.role === 'admin');

const listRef = ref(null);
const showAddModal = ref(false);
const showImportModal = ref(false);
const fileInput = ref(null);

const form = ref({
  title: '',
  domain: '',
  problem: '',
  solution: '',
  objectives: '',
  functions: '',
  technologies: '',
  date: '',
  department_id: ''
});

async function submitAdd() {
  if (!form.value.title || !form.value.date) {
    toast.error('Title and Date are required.');
    return;
  }
  
  if (isAdmin.value && !form.value.department_id) {
    toast.error('Department ID is required for Admins.');
    return;
  }

  const endpoint = isAdmin.value ? '/admin/previous-proposals' : '/department/previous-proposals';

  try {
    const res = await fetch(endpoint, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken
      },
      body: JSON.stringify(form.value)
    });

    if (res.ok) {
      toast.success('Historical proposal added.');
      showAddModal.value = false;
      form.value = { title: '', domain: '', problem: '', solution: '', objectives: '', functions: '', technologies: '', date: '', department_id: '' };
      if (listRef.value) {
        listRef.value.fetchPreviousProposals();
      }
    } else {
      const data = await res.json();
      toast.error(data.message || 'Failed to add proposal.');
    }
  } catch (err) {
    toast.error('An error occurred.');
  }
}

async function submitImport() {
  const file = fileInput.value?.files[0];
  if (!file) {
    toast.error('Please select a CSV file.');
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
      toast.success(data.message || 'Import successful.');
      showImportModal.value = false;
      fileInput.value.value = '';
      if (listRef.value) {
        listRef.value.fetchPreviousProposals();
      }
    } else {
      const data = await res.json();
      toast.error(data.message || 'Failed to import CSV.');
    }
  } catch (err) {
    toast.error('An error occurred during import.');
  }
}
</script>
