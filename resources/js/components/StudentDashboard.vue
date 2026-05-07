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
              TM
            </div>
            <div class="overflow-hidden">
              <p class="truncate text-sm font-semibold text-slate-900">Tayri Musa</p>
              <p class="truncate text-xs text-slate-500">Student Account</p>
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
      <main class="flex-1 p-4 sm:p-6 lg:p-8 overflow-y-auto">
        
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
          
          <!-- 1. Overview -->
          <div v-if="currentView === 'Overview'" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
              <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Active Proposal</p>
                <p class="mt-2 text-xl font-bold text-slate-900">{{ activeProposal.title }}</p>
                <p class="mt-1 text-sm text-slate-600 line-clamp-1">{{ activeProposal.description }}</p>
              </div>
              <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Status</p>
                <p class="mt-2 text-xl font-bold text-slate-900">{{ activeProposal.status }}</p>
              </div>
              <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Similarity Score</p>
                <div class="mt-2 flex items-baseline gap-2">
                  <p class="text-xl font-bold text-slate-900">23%</p>
                  <span class="inline-flex items-center rounded-md bg-teal-50 px-2 py-1 text-xs font-medium text-teal-700 ring-1 ring-inset ring-teal-600/20">Low Risk</span>
                </div>
              </div>
              <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-sm font-medium text-slate-500">Team Size</p>
                <p class="mt-2 text-xl font-bold text-slate-900">{{ teamMembers.length }} / 3</p>
              </div>
            </div>
          </div>

          <!-- 2. Project Workspace -->
          <div v-else-if="currentView === 'Project Workspace'" class="space-y-6">
            <div class="border-b border-slate-200">
              <nav class="-mb-px flex space-x-8">
                <button v-for="tab in workspaceTabs" :key="tab"
                        @click="workspaceTab = tab"
                        :class="[
                          workspaceTab === tab 
                            ? 'border-teal-500 text-teal-600' 
                            : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700',
                          'whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium'
                        ]">
                  {{ tab }}
                </button>
              </nav>
            </div>

            <div v-if="workspaceTab === 'Draft Ideas'" class="space-y-4">
              <div class="flex justify-end mb-4">
                 <button class="inline-flex items-center justify-center rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-teal-700 transition">
                   Draft New Idea
                 </button>
              </div>
              <div v-for="idea in draftIdeas" :key="idea.id" class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-100 bg-slate-50/50 flex justify-between items-center">
                  <h3 class="text-base font-semibold text-slate-900">{{ idea.title }}</h3>
                  <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-800 border border-slate-200">
                    {{ idea.similarity }}% Similarity
                  </span>
                </div>
                <div class="p-6">
                  <p class="text-sm text-slate-600">{{ idea.description }}</p>
                  <div class="mt-4 flex flex-wrap gap-2">
                    <span v-for="kw in idea.keywords.split(', ')" :key="kw" class="inline-flex items-center rounded-md bg-teal-50 px-2 py-1 text-xs font-medium text-teal-700 ring-1 ring-inset ring-teal-700/10">
                      {{ kw }}
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <div v-if="workspaceTab === 'Active Proposal'" class="space-y-6">
               <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                 <div class="p-6 sm:p-8">
                   <div class="flex justify-between items-start mb-6">
                     <span class="inline-flex items-center rounded-md bg-teal-50 px-2.5 py-1 text-xs font-medium text-teal-700 ring-1 ring-inset ring-teal-700/10">Active Proposal</span>
                     <span class="inline-flex items-center rounded-md bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-800 ring-1 ring-inset ring-amber-600/20">{{ activeProposal.status }}</span>
                   </div>
                   
                   <h3 class="text-2xl font-bold text-slate-900 mb-4">{{ activeProposal.title }}</h3>
                   <p class="text-slate-600 text-sm leading-relaxed mb-6">{{ activeProposal.description }}</p>
                   
                   <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-slate-50 rounded-lg p-5 border border-slate-100 mb-6">
                      <div>
                        <p class="text-xs font-medium text-slate-500">Keywords</p>
                        <p class="mt-1 text-sm text-slate-900">{{ activeProposal.keywords }}</p>
                      </div>
                      <div>
                        <p class="text-xs font-medium text-slate-500">Department</p>
                        <p class="mt-1 text-sm font-medium text-slate-900">{{ activeProposal.department }}</p>
                      </div>
                   </div>
                   
                   <div class="flex gap-3">
                     <button class="inline-flex items-center justify-center rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-teal-700 transition">Edit Proposal</button>
                     <button class="inline-flex items-center justify-center rounded-lg bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 transition">View Report</button>
                   </div>
                 </div>
               </div>
            </div>

            <div v-if="workspaceTab === 'Archived Ideas'" class="text-center bg-white rounded-xl border border-slate-200 border-dashed py-16 px-6 shadow-sm">
               <p class="text-sm font-medium text-slate-900">No archived ideas</p>
               <p class="mt-1 text-sm text-slate-500">Ideas you archive will appear here.</p>
            </div>
          </div>

          <!-- 3. Project Team -->
          <div v-else-if="currentView === 'Project Team'" class="space-y-6">
            <div class="rounded-xl border border-slate-200 bg-white p-6 sm:p-8 shadow-sm">
              <div class="sm:flex sm:items-center sm:justify-between mb-8">
                <div>
                  <h3 class="text-lg font-medium text-slate-900">Team Members</h3>
                  <p class="mt-1 text-sm text-slate-500">Maximum 3 students allowed per project.</p>
                </div>
                <div class="mt-4 sm:mt-0" v-if="teamMembers.length < 3">
                  <button type="button" class="inline-flex items-center justify-center rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-teal-700 transition">
                    Invite Member
                  </button>
                </div>
              </div>

              <div class="overflow-hidden shadow-sm ring-1 ring-slate-200 rounded-lg">
                <table class="min-w-full divide-y divide-slate-200">
                  <thead class="bg-slate-50">
                    <tr>
                      <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold text-slate-900 sm:pl-6">Name</th>
                      <th class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Registration Number</th>
                      <th class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Role</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-200 bg-white">
                    <tr v-for="member in teamMembers" :key="member.id">
                      <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm font-medium text-slate-900 sm:pl-6">
                        {{ member.name }}
                      </td>
                      <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">{{ member.regNumber }}</td>
                      <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">
                        <span :class="[
                          member.role === 'Owner' ? 'bg-teal-50 text-teal-700 ring-teal-600/20' : 'bg-slate-50 text-slate-600 ring-slate-500/10',
                          'inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset'
                        ]">
                          {{ member.role }}
                        </span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- 4. Similarity Report -->
          <div v-else-if="currentView === 'Similarity Report'" class="space-y-6">
            <div class="rounded-lg bg-teal-50 p-4 border border-teal-100">
              <div class="flex">
                <div class="ml-3">
                  <p class="text-sm text-teal-800"><strong>Scope Notice:</strong> Only English text is analyzed. ERDs, diagrams, images, and non-text files are excluded.</p>
                </div>
              </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
              <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm flex flex-col items-center justify-center">
                <p class="text-sm font-medium text-slate-900 mb-6">Cosine Similarity</p>
                <div class="relative flex items-center justify-center w-32 h-32">
                  <svg class="w-full h-full transform -rotate-90" viewBox="0 0 100 100">
                    <circle cx="50" cy="50" r="42" fill="none" stroke="#f1f5f9" stroke-width="8"></circle>
                    <circle cx="50" cy="50" r="42" fill="none" stroke="#14b8a6" stroke-width="8" stroke-linecap="round" stroke-dasharray="264" stroke-dashoffset="203.28"></circle>
                  </svg>
                  <div class="absolute inset-0 flex items-center justify-center">
                    <span class="text-2xl font-bold text-slate-900">23%</span>
                  </div>
                </div>
              </div>

              <div class="rounded-xl border border-slate-200 bg-white shadow-sm lg:col-span-2 overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-100">
                  <h3 class="text-base font-medium text-slate-900">Top Matches</h3>
                </div>
                <div class="overflow-x-auto">
                  <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                      <tr>
                        <th class="py-3.5 pl-6 pr-3 text-left text-sm font-semibold text-slate-900">Project</th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Similarity</th>
                        <th class="px-3 py-3.5 text-left text-sm font-semibold text-slate-900">Department</th>
                      </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 bg-white">
                      <tr v-for="(match, index) in topMatches" :key="index">
                        <td class="whitespace-nowrap py-4 pl-6 pr-3 text-sm font-medium text-slate-900">{{ match.title }}</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">{{ match.similarity }}%</td>
                        <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-500">{{ match.department }}</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>

          <!-- 5. Version History -->
          <div v-else-if="currentView === 'Version History'" class="space-y-6">
            <div class="rounded-xl border border-slate-200 bg-white p-6 sm:p-8 shadow-sm">
              <h3 class="text-lg font-medium text-slate-900 mb-6">Proposal Timeline</h3>
              <ul class="-mb-8">
                <li v-for="(ver, index) in versionHistory" :key="ver.version">
                  <div class="relative pb-8">
                    <span v-if="index !== versionHistory.length - 1" class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-slate-200" aria-hidden="true"></span>
                    <div class="relative flex space-x-3">
                      <div>
                        <span :class="[index === 0 ? 'bg-teal-500' : 'bg-slate-400', 'h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white']">
                          <svg class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </span>
                      </div>
                      <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                        <div>
                          <p class="text-sm text-slate-500">
                            <span class="font-medium text-slate-900">{{ ver.version }}</span>
                            &mdash; {{ ver.note }}
                          </p>
                        </div>
                        <div class="whitespace-nowrap text-right text-sm text-slate-500">
                          <time>{{ ver.date }}</time>
                        </div>
                      </div>
                    </div>
                  </div>
                </li>
              </ul>
            </div>
          </div>

          <!-- 6. Department Feedback -->
          <div v-else-if="currentView === 'Department Feedback'" class="space-y-6">
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
              <div class="px-6 py-5 border-b border-slate-100">
                <h3 class="text-base font-medium text-slate-900">Review Decisions</h3>
              </div>
              <ul class="divide-y divide-slate-200">
                <li v-for="(feedback, index) in departmentFeedback" :key="index" class="p-6">
                  <div class="flex items-center justify-between mb-4">
                    <p class="text-sm font-medium text-slate-900">{{ feedback.reviewer }} <span class="text-slate-500 font-normal">({{ feedback.date }})</span></p>
                    <span class="inline-flex items-center rounded-md bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-800">{{ feedback.decision }}</span>
                  </div>
                  <div class="text-sm text-slate-600 bg-slate-50 p-4 rounded-lg border border-slate-100">
                    {{ feedback.note }}
                  </div>
                </li>
              </ul>
            </div>
          </div>

          <!-- 7. Profile Settings -->
          <div v-else-if="currentView === 'Profile Settings'" class="rounded-xl border border-slate-200 bg-white p-12 shadow-sm text-center">
             <p class="text-sm text-slate-500">Settings will be available soon.</p>
          </div>

        </div>
      </main>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
const sidebarOpen = ref(false);

const navItems = [
  { name: 'Overview', icon: '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>' },
  { name: 'Project Workspace', icon: '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>' },
  { name: 'Project Team', icon: '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>' },
  { name: 'Similarity Report', icon: '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>' },
  { name: 'Version History', icon: '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>' },
  { name: 'Department Feedback', icon: '<svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>' },
];

const currentView = ref('Overview');

// Workspace state
const workspaceTabs = ['Draft Ideas', 'Active Proposal', 'Archived Ideas'];
const workspaceTab = ref('Active Proposal');

// Sample Data
const draftIdeas = ref([
  { id: 1, title: 'Smart IoT Home System', description: 'A system to manage home appliances securely via cloud-integrated IoT devices with local fallback capabilities.', keywords: 'IoT, Smart Home, Automation', similarity: 12 },
  { id: 2, title: 'E-commerce Recommendation Engine', description: 'Online store component that provides AI-based product recommendations based on user browsing history.', keywords: 'E-commerce, AI, Machine Learning', similarity: 45 },
]);

const activeProposal = ref({
  title: 'AI Chatbot for Student Support',
  description: 'An intelligent conversational agent designed to assist university students with academic queries, administrative procedures, and campus navigation using modern Natural Language Processing techniques.',
  keywords: 'AI, NLP, Chatbot, Education',
  department: 'Programming',
  status: 'Under Review'
});

const teamMembers = ref([
  { id: 1, name: 'Tayri Musa', role: 'Owner', regNumber: '2023001' },
  { id: 2, name: 'John Doe', role: 'Member', regNumber: '2023002' },
]);

const topMatches = ref([
  { title: 'University Assistant Bot', similarity: 18, department: 'Programming', date: '2025-10-12' },
  { title: 'Smart Student Helper', similarity: 15, department: 'IT', date: '2024-05-20' },
]);

const versionHistory = ref([
  { version: 'v2.1', date: '2026-05-06', note: 'Updated dataset description and refined abstract.' },
  { version: 'v2.0', date: '2026-05-01', note: 'Added new NLP feature documentation based on reviewer feedback.' },
  { version: 'v1.1', date: '2026-04-15', note: 'Fixed minor typos in the introductory section.' },
  { version: 'v1.0', date: '2026-04-01', note: 'Initial proposal submission to the department.' },
]);

const departmentFeedback = ref([
  { reviewer: 'Dr. Smith', decision: 'Revision Requested', note: 'Please expand on the data collection methodology and specify which embedding model you plan to use.', date: '2026-05-05' },
  { reviewer: 'Prof. Davis', decision: 'Accepted', note: 'Good initial concept. Ensure you focus on local language nuances if applicable.', date: '2026-04-10' },
]);
</script>
