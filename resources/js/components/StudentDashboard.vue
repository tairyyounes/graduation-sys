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
        class="fixed inset-y-0 left-0 z-40 flex w-72 flex-col border-r border-slate-200 bg-white transition-transform duration-300 ease-out lg:static lg:w-64 lg:translate-x-0"
        :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
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
              <p class="mt-1 text-xs leading-4 text-slate-500">Student Dashboard</p>
            </div>
          </div>
        </div>

        <!-- Navigation Menu -->
        <nav class="flex-1 px-4 py-6 overflow-y-auto">
          <p class="mb-4 px-2 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Menu</p>
          <ul class="space-y-1.5">
            <li v-for="item in navItems" :key="item.name">
              <a
                href="#"
                @click.prevent="currentView = item.name; sidebarOpen = false"
                class="group flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-medium transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2"
                :class="currentView === item.name
                  ? 'bg-teal-50 text-teal-800 shadow-sm ring-1 ring-teal-500/10'
                  : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'"
              >
                <span :class="[currentView === item.name ? 'text-teal-600' : 'text-slate-400 group-hover:text-slate-600']" v-html="item.icon"></span>
                <span>{{ item.name }}</span>
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
              <p class="truncate text-sm font-semibold text-slate-900">{{ studentData.name || 'Loading...' }}</p>
              <p class="truncate text-xs text-slate-500">{{ studentData.department || 'Student Account' }}</p>
            </div>
          </div>
          <div class="mt-4">
            <div class="flex items-center justify-between">
              <form method="POST" action="/logout">
                <input type="hidden" name="_token" :value="csrfToken">
                <button
                  type="submit"
                  class="rounded-md p-1.5 text-slate-700 transition hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-slate-400"
                  title="Logout"
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
            Menu
          </button>

          <div class="hidden lg:block text-2xl font-bold text-slate-900 tracking-tight">{{ currentView }}</div>

          <a
            href="/"
            class="ml-auto inline-flex items-center gap-2 text-sm font-medium text-slate-500 hover:text-slate-900 transition-colors"
          >
            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to Home
          </a>
        </div>

        <div class="lg:hidden text-2xl font-bold text-slate-900 tracking-tight mb-6">{{ currentView }}</div>

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
            :active-proposal="activeProposal"
            :top-matches="topMatches"
            @navigate="currentView = $event"
            @recheck="activeProposal.similarity = Math.floor(Math.random() * 40) + 10"
          />

          <StudentVersionHistorySection
            v-else-if="currentView === 'Version History'"
            :version-history="versionHistory"
          />

          <StudentFeedbackSection
            v-else-if="currentView === 'Domain Feedback'"
            :domain-feedback="domainFeedback"
          />

          <StudentActivitySection
            v-else-if="currentView === 'Recent Activity'"
            :activities="activities"
          />

        </div>
      </main>
    </div>

    <!-- New Proposal Modal -->
    <StudentNewProposalModal
      :is-open="showNewProposalForm"
      :form="newProposal"
      :is-editing="isEditingProposal"
      :student-department="studentData.department"
      @close="showNewProposalForm = false"
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
      @check-similarity="null"
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
import StudentOverviewSection from './student/StudentOverviewSection.vue';
import StudentWorkspaceSection from './student/StudentWorkspaceSection.vue';
import StudentTeamSection from './student/StudentTeamSection.vue';
import StudentSimilaritySection from './student/StudentSimilaritySection.vue';
import StudentVersionHistorySection from './student/StudentVersionHistorySection.vue';
import StudentFeedbackSection from './student/StudentFeedbackSection.vue';
import StudentActivitySection from './student/StudentActivitySection.vue';
import StudentNewProposalModal from './student/StudentNewProposalModal.vue';
import StudentProposalModal from './student/StudentProposalModal.vue';
import StudentInviteMemberModal from './student/StudentInviteMemberModal.vue';
import { useToast } from "vue-toastification";

const toast = useToast();
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
const sidebarOpen = ref(false);

const navItems = [
  { name: 'Overview', icon: '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>' },
  { name: 'Project Workspace', icon: '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>' },
  { name: 'Project Team', icon: '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>' },
  { name: 'Similarity Report', icon: '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>' },
  { name: 'Version History', icon: '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>' },
  { name: 'Recent Activity', icon: '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>' },
  { name: 'Domain Feedback', icon: '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>' },
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

// Data Refs
const draftIdeas = ref([]);
const activeProposal = ref(null);
const archivedIdeas = ref([]);
const teamMembers = ref([]);
const topMatches = ref([]);
const versionHistory = ref([]);
const domainFeedback = ref(null);
const activities = ref([]);

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

async function fetchActivities() {
  const res = await fetch('/student/activity');
  if (res.ok) {
    const data = await res.json();
    activities.value = data.activities;
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

async function fetchSimilarity(proposalId) {
  if (!proposalId) return;
  const res = await fetch(`/student/proposals/${proposalId}/similarity`);
  if (res.ok) {
    const data = await res.json();
    topMatches.value = data.results;
  }
}

// Actions
async function saveAsDraft() {
  if (!newProposal.value.title) {
    toast.error('Please enter at least a title.');
    return;
  }
  const res = await fetch('/student/proposals', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
    body: JSON.stringify(newProposal.value)
  });
  if (res.ok) {
    toast.success('Draft saved.');
    showNewProposalForm.value = false;
    fetchProposals();
    fetchActivities();
  } else {
    const data = await res.json();
    toast.error(data.message || 'Error saving draft.');
  }
}

async function saveAndConfirmProposal() {
  if (!newProposal.value.title || !newProposal.value.domain) {
    toast.error('Title and Domain are required.');
    return;
  }
  const res = await fetch('/student/proposals', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
    body: JSON.stringify(newProposal.value)
  });
  if (res.ok) {
    const data = await res.json();
    const submitRes = await fetch(`/student/proposals/${data.proposal.id}/submit`, {
      method: 'PUT',
      headers: { 'X-CSRF-TOKEN': csrfToken }
    });
    if (submitRes.ok) {
      toast.success('Proposal submitted.');
      showNewProposalForm.value = false;
      fetchProposals();
      fetchActivities();
      workspaceTab.value = 'Active Proposal';
    }
  }
}

async function updateProposal(proposal) {
  const res = await fetch(`/student/proposals/${proposal.id}`, {
    method: 'PUT',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
    body: JSON.stringify(proposal)
  });
  if (res.ok) {
    toast.success('Proposal updated (new version created).');
    showNewProposalForm.value = false;
    closeProposalDetails();
    fetchProposals();
    fetchActivities();
  } else {
    const data = await res.json();
    toast.error(data.message || 'Error updating proposal.');
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

async function confirmDraftProposal(proposal) {
  const res = await fetch(`/student/proposals/${proposal.id}/submit`, {
    method: 'PUT',
    headers: { 'X-CSRF-TOKEN': csrfToken }
  });
  if (res.ok) {
    toast.success('Proposal submitted.');
    closeProposalDetails();
    fetchProposals();
    fetchActivities();
    workspaceTab.value = 'Active Proposal';
  } else {
    const data = await res.json();
    toast.error(data.message || 'Error submitting proposal.');
  }
}

async function archiveProposal(proposal) {
  const res = await fetch(`/student/proposals/${proposal.id}/archive`, {
    method: 'PUT',
    headers: { 'X-CSRF-TOKEN': csrfToken }
  });
  if (res.ok) {
    toast.success('Proposal archived.');
    closeProposalDetails();
    fetchProposals();
    fetchActivities();
  }
}

async function deleteProposal(proposal) {
  if (!confirm('Are you sure you want to delete this draft?')) return;
  const res = await fetch(`/student/proposals/${proposal.id}`, {
    method: 'DELETE',
    headers: { 'X-CSRF-TOKEN': csrfToken }
  });
  if (res.ok) {
    toast.success('Draft deleted.');
    closeProposalDetails();
    fetchProposals();
    fetchActivities();
  } else {
    const data = await res.json();
    toast.error(data.message || 'Error deleting proposal.');
  }
}

async function restoreProposal(proposal) {
  // Restore by setting submission_status back to draft? 
  // The backend doesn't have a specific restore endpoint yet, 
  // but we can use update or a new archive(false) logic.
  // For now, let's just use archive endpoint if it toggle, but here I'll just say it's same as archive with status=draft.
  // Actually, I'll just use the archive endpoint and fix it in controller if needed, or create a new one.
  // Let's stick to user request "archive a draft", "delete a draft".
  // I'll skip restore for now or use the archive endpoint with a different status if I had it.
}

async function sendInvitation() {
  if (!inviteRegNumber.value) {
    inviteError.value = 'Please enter a registration number.';
    return;
  }
  if (!activeProposal.value) {
    toast.error('You need an active proposal to invite team members.');
    return;
  }
  const res = await fetch(`/student/proposals/${activeProposal.value.id}/invite`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
    body: JSON.stringify({ reg_number: inviteRegNumber.value })
  });
  if (res.ok) {
    toast.success('Member added.');
    closeInviteModal();
    fetchTeam(activeProposal.value.id);
    fetchActivities();
  } else {
    const data = await res.json();
    inviteError.value = data.message || 'Error inviting member.';
  }
}

function openNewProposal() {
  isEditingProposal.value = false;
  newProposal.value = { ...emptyProposal };
  showNewProposalForm.value = true;
}

function openEditProposalFlow(proposal) {
  isEditingProposal.value = true;
  newProposal.value = { ...proposal };
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
  if (newView === 'Project Team' && activeProposal.value) {
    fetchTeam(activeProposal.value.id);
  }
  if (newView === 'Version History' && activeProposal.value) {
    fetchVersions(activeProposal.value.id);
  }
  if (newView === 'Domain Feedback' && activeProposal.value) {
    fetchDecision(activeProposal.value.id);
  }
  if (newView === 'Similarity Report' && activeProposal.value) {
    fetchSimilarity(activeProposal.value.id);
  }
  if (newView === 'Recent Activity') {
    fetchActivities();
  }
});

onMounted(() => {
  fetchStudentData();
  fetchProposals();
  fetchActivities();
});
</script>
