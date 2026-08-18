<template>
  <div class="min-h-screen bg-slate-50 text-slate-800">
    <div class="flex min-h-screen w-full">

      <!-- Mobile sidebar overlay -->
      <div
        v-if="sidebarOpen"
        class="fixed inset-0 z-30 bg-slate-900/40 backdrop-blur-[1px] lg:hidden"
        @click="sidebarOpen = false"
      ></div>

      <!-- Sidebar -->
      <aside
        class="fixed inset-y-0 start-0 z-40 flex w-72 flex-col border-e border-slate-200 bg-white transition-transform duration-300 ease-out lg:static lg:w-64 lg:translate-x-0"
        :class="sidebarOpen ? 'translate-x-0' : 'max-lg:-translate-x-full max-lg:rtl:translate-x-full'"
      >
        <!-- Brand Header -->
        <div class="border-b border-slate-100 px-5 py-6">
          <div class="flex items-start gap-3">
            <div class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-teal-500 to-cyan-600 text-white shadow-md shadow-teal-500/20">
              <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2l7 4v6c0 5-3.5 8.5-7 10-3.5-1.5-7-5-7-10V6l7-4z" />
              </svg>
            </div>
            <div>
              <p class="text-lg font-bold tracking-tight text-slate-900">ProposalGuard AI</p>
              <p class="mt-1 text-xs leading-4 text-slate-500">{{ $t('student.dashboard_subtitle') }}</p>
            </div>
          </div>
        </div>

        <!-- Navigation Menu -->
        <nav class="flex-1 px-4 py-6 overflow-y-auto">
          <p class="mb-4 px-2 text-[11px] font-semibold uppercase tracking-wider text-slate-500">{{ $t('common.menu') }}</p>
          <ul class="space-y-1.5">
            <li v-for="item in navItems" :key="item.name">
              <a
                href="#"
                @click.prevent="currentView = item.name; sidebarOpen = false"
                class="group flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-start text-sm font-medium transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2"
                :class="currentView === item.name
                  ? 'bg-teal-50 text-teal-800 shadow-sm ring-1 ring-teal-500/10'
                  : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'"
              >
                <span :class="[currentView === item.name ? 'text-teal-600' : 'text-slate-400 group-hover:text-slate-600']" v-html="item.icon"></span>
                <span>{{ viewLabel(item.name) }}</span>
              </a>
            </li>
          </ul>
        </nav>

        <!-- User Footer -->
        <div class="mt-auto border-t border-slate-100 bg-slate-50/50 px-5 py-5">
          <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-teal-100 text-sm font-bold text-teal-800">
              {{ studentData.name ? studentData.name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase() : 'ST' }}
            </div>
            <div class="overflow-hidden">
              <p class="truncate text-sm font-semibold text-slate-900">{{ studentData.name || $t('common.loading') }}</p>
              <p class="truncate text-xs text-slate-500">{{ studentData.department || $t('student.student_account') }}</p>
            </div>
          </div>
          <div class="mt-4">
            <div class="flex items-center justify-between gap-2">
              <LanguageSwitcher />
              <form method="POST" action="/logout">
                <input type="hidden" name="_token" :value="csrfToken">
                <button
                  type="submit"
                  class="rounded-md p-1.5 text-slate-700 transition hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-400"
                  :title="$t('common.logout')"
                >
                  <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1m0-10V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2h6a2 2 0 002-2v-1" />
                  </svg>
                </button>
              </form>
            </div>
          </div>
        </div>
      </aside>

      <!-- Main Content Area -->
      <main class="flex-1 p-4 sm:p-6 lg:p-8 overflow-y-auto relative">

        <!-- Mobile Header -->
        <div class="mb-5 flex items-center justify-between lg:mb-8">
          <button
            type="button"
            class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-400 lg:hidden"
            @click="sidebarOpen = true"
          >
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
            {{ $t('common.menu') }}
          </button>

          <div class="hidden lg:block text-2xl font-bold text-slate-900 tracking-tight">{{ viewLabel(currentView) }}</div>

          <div class="ms-auto flex items-center gap-4">
            <LangToggle />
            <a
              href="/"
              class="inline-flex items-center gap-2 text-sm font-medium text-slate-500 hover:text-slate-900 transition-colors"
            >
              <svg class="h-4 w-4 rtl:-scale-x-100" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
              </svg>
              {{ $t('common.back_to_home') }}
            </a>
          </div>
        </div>

        <div class="lg:hidden text-2xl font-bold text-slate-900 tracking-tight mb-6">{{ viewLabel(currentView) }}</div>

        <!-- Dashboard Views -->
        <div class="transition-all">

          <StudentOverviewSection
            v-if="currentView === 'Overview'"
            :active-proposal="activeProposal"
            :team-size="teamMembers.length"
            @navigate="currentView = $event"
          />

          <StudentWorkspaceSection
            v-else-if="currentView === 'Project Workspace'"
            :workspace-tab="workspaceTab"
            :workspace-tabs="workspaceTabs"
            :draft-ideas="draftIdeas"
            :archived-ideas="archivedIdeas"
            :active-proposal="activeProposal"
            :draft-count="draftIdeas.length"
            :archived-count="archivedIdeas.length"
            @update:workspace-tab="workspaceTab = $event"
            @open-new-proposal="openNewProposal"
            @open-proposal-details="openProposalDetails"
            @navigate="currentView = $event"
          />

          <StudentTeamSection
            v-else-if="currentView === 'Project Team'"
            :team-members="teamMembers"
            @open-invite="openInviteModal"
          />

          <StudentSimilaritySection
            v-else-if="currentView === 'Similarity Report'"
            :active-proposal="similarityProposal"
            :top-matches="topMatches"
            :summary="similaritySummary"
            :ai-status="similarityAiStatus"
            :recommendations="similarityRecommendations"
            :analyzed-at="similarityAnalyzedAt"
            @navigate="currentView = $event"
            @recheck="fetchSimilarity(similarityProposal?.id, true)"
          />

          <StudentVersionHistorySection
            v-else-if="currentView === 'Version History'"
            :version-history="versionHistory"
          />

          <StudentFeedbackSection
            v-else-if="currentView === 'Domain Feedback'"
            :domain-feedback="domainFeedback"
          />

          <StudentRepoSection
            v-else-if="currentView === 'Proposal Repository'"
            :active-proposal="activeProposal"
            @compare="openCompareView"
          />

          <StudentCompareSection
            v-else-if="currentView === 'Compare View'"
            :compared-id="compareProposalId"
            @back="currentView = 'Proposal Repository'"
          />

        </div>
      </main>
    </div>

    <!-- New Proposal Modal -->
    <StudentNewProposalModal
      :is-open="showNewProposalForm"
      :form="newProposal"
      :is-editing="isEditingProposal"
      :errors="proposalErrors"
      :student-department="studentData.department"
      @close="closeNewProposalForm"
      @save-draft="saveAsDraft"
      @confirm-proposal="saveAndConfirmProposal"
      @update="updateProposal(newProposal)"
    />

    <!-- Proposal Details Modal -->
    <StudentProposalModal
      :is-open="showProposalModal"
      :proposal="selectedProposal"
      :type="selectedProposalType"
      @close="closeProposalDetails"
      @check-similarity="checkDraftSimilarity(selectedProposal)"
      @archive="archiveProposal(selectedProposal)"
      @delete="deleteProposal(selectedProposal)"
      @confirm="confirmDraftProposal(selectedProposal)"
      @view-report="viewReportFromModal"
      @restore="restoreProposal(selectedProposal)"
      @edit="openEditProposalFlow"
    />

    <!-- Invite Member Modal -->
    <StudentInviteMemberModal
      :is-open="showInviteModal"
      :reg-number="inviteRegNumber"
      :error="inviteError"
      @close="closeInviteModal"
      @send="sendInvitation"
      @update:reg-number="inviteRegNumber = $event"
    />

  </div>
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import LanguageSwitcher from './common/LanguageSwitcher.vue';
import StudentOverviewSection from './student/StudentOverviewSection.vue';
import StudentWorkspaceSection from './student/StudentWorkspaceSection.vue';
import StudentTeamSection from './student/StudentTeamSection.vue';
import StudentSimilaritySection from './student/StudentSimilaritySection.vue';
import StudentVersionHistorySection from './student/StudentVersionHistorySection.vue';
import StudentFeedbackSection from './student/StudentFeedbackSection.vue';
import StudentNewProposalModal from './student/StudentNewProposalModal.vue';
import StudentProposalModal from './student/StudentProposalModal.vue';
import StudentInviteMemberModal from './student/StudentInviteMemberModal.vue';
import StudentRepoSection from './student/StudentRepoSection.vue';
import StudentCompareSection from './student/StudentCompareSection.vue';
import LangToggle from './common/LangToggle.vue';
import { useToast } from "vue-toastification";
import { useI18n } from 'vue-i18n';

const toast = useToast();
const { t } = useI18n();
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
const sidebarOpen = ref(false);

// خريطة الاسم الداخلي (مفتاح منطقي إنجليزي) -> مفتاح الترجمة المعروض
const VIEW_KEYS = {
  'Overview': 'nav.overview',
  'Project Workspace': 'nav.workspace',
  'Project Team': 'nav.team',
  'Similarity Report': 'nav.similarity_report',
  'Version History': 'nav.version_history',
  'Domain Feedback': 'nav.domain_feedback',
  'Proposal Repository': 'nav.repository',
  'Compare View': 'nav.compare_view',
};

function viewLabel(name) {
  return VIEW_KEYS[name] ? t(VIEW_KEYS[name]) : name;
}

const navItems = [
  { name: 'Overview', icon: '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>' },
  { name: 'Project Workspace', icon: '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>' },
  { name: 'Project Team', icon: '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>' },
  { name: 'Similarity Report', icon: '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>' },
  { name: 'Version History', icon: '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>' },
  { name: 'Domain Feedback', icon: '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>' },
  { name: 'Proposal Repository', icon: '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 4H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-2m-4-1v8m0 0l3-3m-3 3L9 8m-5 5h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 00.707.293h3.172a1 1 0 00.707-.293l2.414-2.414a1 1 0 01.707-.293H20"/></svg>' },
];

const currentView = ref('Overview');
const studentData = ref({ name: '', email: '', department: '', status: '' });

// Workspace state
const workspaceTabs = ['Draft Ideas', 'Active Proposal', 'Archived Ideas'];
const workspaceTab = ref('Draft Ideas');

// Modal states
const showNewProposalForm = ref(false);
const isEditingProposal = ref(false);
const showProposalModal = ref(false);
const showInviteModal = ref(false);
const selectedProposal = ref(null);
const selectedProposalType = ref('');

const inviteRegNumber = ref('');
const inviteError = ref('');

const emptyProposal = {
  title: '',
  domain: '',
  problem: '',
  solution: '',
  objectives: '',
  functions: '',
  tags: '',
  tech: '',
  similarity: null,
  date: ''
};

const newProposal = ref({ ...emptyProposal });
const proposalErrors = ref({});

// Data Refs
const draftIdeas = ref([]);
const activeProposal = ref(null);
const similarityProposal = ref(null);
const archivedIdeas = ref([]);
const teamMembers = ref([]);
const topMatches = ref([]);
const similaritySummary = ref(null);   // AI breakdown summary for top card
const similarityAiStatus = ref('none'); // 'pending' | 'success' | 'failed' | 'none'
const similarityRecommendations = ref([]);
const similarityAnalyzedAt = ref(null); // ISO timestamp of last analysis run
const versionHistory = ref([]);
const domainFeedback = ref(null);
const compareProposalId = ref(null);

function openCompareView(id) {
  compareProposalId.value = id;
  currentView.value = 'Compare View';
}

// API calls
async function fetchStudentData() {
  const res = await fetch('/student/data');
  if (res.ok) {
    const data = await res.json();
    studentData.value = data.student;
  }
}

async function fetchProposals() {
  const res = await fetch('/student/proposals');
  if (res.ok) {
    const data = await res.json();
    draftIdeas.value = data.drafts;
    activeProposal.value = data.active;
    archivedIdeas.value = data.archived;
  }
}

async function fetchTeam(proposalId) {
  if (!proposalId) return;
  const res = await fetch(`/student/proposals/${proposalId}/team`);
  if (res.ok) {
    const data = await res.json();
    teamMembers.value = data.members;
  }
}

async function fetchVersions(proposalId) {
  if (!proposalId) return;
  const res = await fetch(`/student/proposals/${proposalId}/versions`);
  if (res.ok) {
    const data = await res.json();
    versionHistory.value = data.versions;
  }
}

async function fetchDecision(proposalId) {
  if (!proposalId) return;
  const res = await fetch(`/student/proposals/${proposalId}/decision`);
  if (res.ok) {
    const data = await res.json();
    domainFeedback.value = data.decision ? [data.decision] : [];
  }
}

async function fetchSimilarity(proposalId, recheck = false) {
  if (!proposalId) return;
  const url = `/student/proposals/${proposalId}/similarity` + (recheck ? '?recheck=true' : '');
  const res = await fetch(url);
  if (res.ok) {
    const data = await res.json();
    topMatches.value = data.results ?? [];
    similaritySummary.value = data.summary ?? null;
    similarityAiStatus.value = data.ai_status ?? 'none';
    similarityRecommendations.value = data.recommendations ?? [];
    similarityAnalyzedAt.value = data.analyzed_at ?? null;
  }
}

// Actions
async function saveAsDraft() {
  proposalErrors.value = {};
  const res = await fetch('/student/proposals', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
    body: JSON.stringify(newProposal.value)
  });
  if (res.ok) {
    toast.success(t('student.toast.draft_saved'));
    showNewProposalForm.value = false;
    fetchProposals();
  } else {
    const data = await res.json();
    if (data.errors) {
      proposalErrors.value = data.errors;
    }
    toast.error(data.message || t('student.toast.draft_save_error'));
  }
}

async function saveAndConfirmProposal() {
  proposalErrors.value = {};

  // 1. First save as draft
  const res = await fetch('/student/proposals', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
    body: JSON.stringify(newProposal.value)
  });
  if (res.ok) {
    const data = await res.json();
    
    // 2. Perform pre-submission similarity analysis
    toast.info(t('student.toast.running_presubmission'));
    let similarity = null;
    const checkRes = await fetch(`/student/proposals/${data.proposal.id}/similarity`);
    if (checkRes.ok) {
      const checkData = await checkRes.json();
      if (checkData.summary && checkData.summary.final_score !== undefined) {
        similarity = checkData.summary.final_score;
      }
    }

    // 3. Show high similarity warning if needed, otherwise normal confirmation
    if (similarity !== null && similarity >= 60) {
      if (!confirm(t('student.confirm.high_similarity'))) {
        toast.info(t('student.toast.saved_draft_review'));
        showNewProposalForm.value = false;
        fetchProposals();
        return;
      }
    } else {
      if (!confirm(t('student.confirm.submit'))) {
        toast.info(t('student.toast.saved_draft'));
        showNewProposalForm.value = false;
        fetchProposals();
        return;
      }
    }

    // 4. Submit
    const submitRes = await fetch(`/student/proposals/${data.proposal.id}/submit`, {
      method: 'PUT',
      headers: { 'X-CSRF-TOKEN': csrfToken }
    });
    if (submitRes.ok) {
      toast.success(t('student.toast.submitted'));
      showNewProposalForm.value = false;
      fetchProposals();
      workspaceTab.value = 'Active Proposal';
    } else {
      const submitData = await submitRes.json();
      if (submitData.errors) {
        proposalErrors.value = submitData.errors;
      }
      toast.error(submitData.message || t('student.toast.submit_error'));
    }
  } else {
    const data = await res.json();
    if (data.errors) {
      proposalErrors.value = data.errors;
    }
    toast.error(data.message || t('student.toast.save_error'));
  }
}

async function updateProposal(proposal) {
  proposalErrors.value = {};
  const res = await fetch(`/student/proposals/${proposal.id}`, {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
    body: JSON.stringify(proposal)
  });
  if (res.ok) {
    toast.success(t('student.toast.updated'));
    showNewProposalForm.value = false;
    closeProposalDetails();
    fetchProposals();
  } else {
    const data = await res.json();
    if (data.errors) {
      proposalErrors.value = data.errors;
    }
    toast.error(data.message || t('student.toast.update_error'));
  }
}

function openProposalDetails(proposal, type) {
  selectedProposal.value = { ...proposal };
  selectedProposalType.value = type;
  showProposalModal.value = true;
}

function closeProposalDetails() {
  showProposalModal.value = false;
  selectedProposal.value = null;
  selectedProposalType.value = '';
}

async function checkDraftSimilarity(proposal) {
  if (!proposal) return;
  closeProposalDetails();
  similarityProposal.value = proposal;
  currentView.value = 'Similarity Report';
  
  toast.info(t('student.toast.starting_draft_analysis'));
  await fetchSimilarity(proposal.id, true);
  fetchProposals();
}

async function confirmDraftProposal(proposal) {
  let similarity = proposal.similarity;

  // Run similarity check first if it was never analyzed
  if (similarity === null) {
    toast.info(t('student.toast.running_presubmission'));
    const checkRes = await fetch(`/student/proposals/${proposal.id}/similarity`);
    if (checkRes.ok) {
      const checkData = await checkRes.json();
      if (checkData.summary && checkData.summary.final_score !== undefined) {
        similarity = checkData.summary.final_score;
      }
    }
  }

  // If a high similarity score is detected, show the confirmation warning
  if (similarity !== null && similarity >= 60) {
    if (!confirm("Warning: High similarity detected with an approved project. The project details are hidden for privacy reasons. Please consider adjusting your project scope or selecting a different direction.\n\nDo you still want to submit this proposal anyway?")) {
      return;
    }
  } else {
    // Normal confirmation if no similarity warning
    if (!confirm("Are you sure you want to submit this proposal? Please review your information before confirming.")) {
      return;
    }
  }

  const res = await fetch(`/student/proposals/${proposal.id}/submit`, {
    method: 'PUT',
    headers: { 'X-CSRF-TOKEN': csrfToken }
  });
  if (res.ok) {
    toast.success(t('student.toast.submitted'));
    closeProposalDetails();
    fetchProposals();
    workspaceTab.value = 'Active Proposal';
  } else {
    const data = await res.json();
    toast.error(data.message || t('student.toast.submit_error'));
  }
}

async function archiveProposal(proposal) {
  const res = await fetch(`/student/proposals/${proposal.id}/archive`, {
    method: 'PUT',
    headers: { 'X-CSRF-TOKEN': csrfToken }
  });
  if (res.ok) {
    toast.success(t('student.toast.archived'));
    closeProposalDetails();
    fetchProposals();
  }
}

async function deleteProposal(proposal) {
  if (!confirm(t('student.confirm.delete'))) return;
  const res = await fetch(`/student/proposals/${proposal.id}`, {
    method: 'DELETE',
    headers: { 'X-CSRF-TOKEN': csrfToken }
  });
  if (res.ok) {
    toast.success(t('student.toast.deleted'));
    closeProposalDetails();
    fetchProposals();
  } else {
    const data = await res.json();
    toast.error(data.message || t('student.toast.delete_error'));
  }
}

async function restoreProposal(proposal) {
  if (!confirm(t('student.confirm.restore'))) return;
  const res = await fetch(`/student/proposals/${proposal.id}/restore`, {
    method: 'PUT',
    headers: { 'X-CSRF-TOKEN': csrfToken }
  });
  if (res.ok) {
    toast.success(t('student.toast.restored'));
    closeProposalDetails();
    fetchProposals();
    workspaceTab.value = 'Draft Ideas';
  } else {
    const data = await res.json();
    toast.error(data.message || t('student.toast.restore_error'));
  }
}

async function sendInvitation() {
  if (!inviteRegNumber.value) {
    inviteError.value = t('student.toast.enter_reg_number');
    return;
  }
  if (!activeProposal.value) {
    toast.error(t('student.toast.need_active_proposal'));
    return;
  }
  const res = await fetch(`/student/proposals/${activeProposal.value.id}/invite`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
    body: JSON.stringify({ reg_number: inviteRegNumber.value })
  });
  if (res.ok) {
    toast.success(t('student.toast.member_added'));
    closeInviteModal();
    fetchTeam(activeProposal.value.id);
  } else {
    const data = await res.json();
    inviteError.value = data.message || t('student.toast.invite_error');
  }
}

function closeNewProposalForm() {
  showNewProposalForm.value = false;
  proposalErrors.value = {};
}

function openNewProposal() {
  isEditingProposal.value = false;
  newProposal.value = { ...emptyProposal };
  proposalErrors.value = {};
  showNewProposalForm.value = true;
}

function openEditProposalFlow(proposal) {
  isEditingProposal.value = true;
  newProposal.value = { ...proposal };
  proposalErrors.value = {};
  showProposalModal.value = false;
  showNewProposalForm.value = true;
}

function openInviteModal() {
  inviteError.value = '';
  inviteRegNumber.value = '';
  showInviteModal.value = true;
}

function closeInviteModal() {
  showInviteModal.value = false;
}

function viewReportFromModal() {
  closeProposalDetails();
  currentView.value = 'Similarity Report';
}

// Watchers for sections that need specific data
watch(currentView, (newView) => {
  if (newView !== 'Similarity Report') {
    similarityProposal.value = null;
  }

  if (newView === 'Project Team' && activeProposal.value) {
    fetchTeam(activeProposal.value.id);
  }
  if (newView === 'Version History' && activeProposal.value) {
    fetchVersions(activeProposal.value.id);
  }
  if (newView === 'Domain Feedback' && activeProposal.value) {
    fetchDecision(activeProposal.value.id);
  }
  if (newView === 'Similarity Report') {
    if (!similarityProposal.value && activeProposal.value) {
      similarityProposal.value = activeProposal.value;
    }
    if (similarityProposal.value) {
      fetchSimilarity(similarityProposal.value.id);
    }
  }
});

onMounted(() => {
  fetchStudentData();
  fetchProposals();
});
</script>
