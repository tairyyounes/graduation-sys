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
          
          <!-- 1. Overview -->
          <div v-if="currentView === 'Overview'" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
              <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm hover:shadow-md transition-shadow">
                <p class="text-sm font-medium text-slate-500">Active Proposal</p>
                <template v-if="activeProposal">
                  <p class="mt-2 text-xl font-bold text-slate-900 truncate">{{ activeProposal.title }}</p>
                  <p class="mt-1 text-sm text-slate-600 line-clamp-1">{{ activeProposal.problem }}</p>
                </template>
                <template v-else>
                  <p class="mt-2 text-xl font-bold text-slate-400">No Active Proposal</p>
                </template>
              </div>
              <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm hover:shadow-md transition-shadow">
                <p class="text-sm font-medium text-slate-500">Status</p>
                <p class="mt-2 text-xl font-bold text-slate-900">{{ activeProposal ? activeProposal.status : 'None' }}</p>
              </div>
              <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm hover:shadow-md transition-shadow">
                <p class="text-sm font-medium text-slate-500">Similarity Score</p>
                <div class="mt-2 flex items-baseline gap-2">
                  <p class="text-xl font-bold text-slate-900">{{ activeProposal && activeProposal.similarity !== null ? activeProposal.similarity + '%' : 'N/A' }}</p>
                  <span v-if="activeProposal && activeProposal.similarity !== null" :class="[activeProposal.similarity < 30 ? 'bg-teal-50 text-teal-700 ring-teal-600/20' : activeProposal.similarity < 60 ? 'bg-amber-50 text-amber-700 ring-amber-600/20' : 'bg-red-50 text-red-700 ring-red-600/20', 'inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset']">
                    {{ activeProposal.similarity < 30 ? 'Low Risk' : activeProposal.similarity < 60 ? 'Medium Risk' : 'High Risk' }}
                  </span>
                </div>
              </div>
              <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm hover:shadow-md transition-shadow">
                <p class="text-sm font-medium text-slate-500">Team Size</p>
                <p class="mt-2 text-xl font-bold text-slate-900">{{ teamMembers.length }} / 3</p>
              </div>
            </div>
            
            <div class="mt-8 bg-gradient-to-r from-teal-600 to-cyan-600 rounded-2xl p-8 text-white shadow-lg relative overflow-hidden">
              <div class="relative z-10">
                <h2 class="text-2xl font-bold mb-2">Welcome back, Tayri!</h2>
                <p class="text-teal-100 max-w-2xl text-sm leading-relaxed">
                  Your graduation proposal journey is looking good. Use ProposalGuard AI to check your drafts against previous projects to ensure originality and a smooth approval process from your domain reviewers.
                </p>
                <button @click="currentView = 'Project Workspace'" class="mt-6 bg-white text-teal-700 px-5 py-2.5 rounded-lg text-sm font-semibold shadow-sm hover:bg-slate-50 transition-colors">
                  Go to Workspace
                </button>
              </div>
              <svg class="absolute right-0 top-0 h-full text-white/10 transform translate-x-1/4 scale-150" viewBox="0 0 24 24" fill="currentColor">
                 <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" />
              </svg>
            </div>
          </div>

          <!-- 2. Project Workspace -->
          <div v-else-if="currentView === 'Project Workspace'" class="space-y-6">
            <div class="border-b border-slate-200 flex justify-between items-end">
              <nav class="-mb-px flex space-x-8">
                <button v-for="tab in workspaceTabs" :key="tab"
                        @click="workspaceTab = tab"
                        :class="[
                          workspaceTab === tab 
                            ? 'border-teal-500 text-teal-600' 
                            : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700',
                          'whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition-colors'
                        ]">
                  {{ tab }}
                  <span v-if="tab === 'Draft Ideas' && draftIdeas.length" class="ml-2 bg-slate-100 text-slate-600 py-0.5 px-2 rounded-full text-xs">{{ draftIdeas.length }}</span>
                  <span v-if="tab === 'Archived Ideas' && archivedIdeas.length" class="ml-2 bg-slate-100 text-slate-600 py-0.5 px-2 rounded-full text-xs">{{ archivedIdeas.length }}</span>
                </button>
              </nav>
            </div>

            <div v-if="workspaceTab === 'Draft Ideas'" class="space-y-4">
              <div class="flex justify-between items-center mb-6">
                 <p class="text-sm text-slate-500">Drafts are private and have not been submitted to the domain.</p>
                 <button @click="openNewProposal" class="inline-flex items-center justify-center rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-teal-700 transition-colors ring-1 ring-teal-600">
                   <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                   New Proposal
                 </button>
              </div>
              
              <div v-if="draftIdeas.length === 0" class="text-center bg-white rounded-xl border border-slate-200 border-dashed py-16 px-6 shadow-sm">
                <div class="mx-auto w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4">
                   <svg class="w-8 h-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <p class="text-base font-medium text-slate-900">No drafts yet</p>
                <p class="mt-1 text-sm text-slate-500">Create your first proposal draft to begin.</p>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div v-for="idea in draftIdeas" :key="idea.id" @click="openProposalDetails(idea, 'draft')" class="cursor-pointer group rounded-xl border border-slate-200 bg-white shadow-sm hover:shadow-md hover:border-teal-300 transition-all overflow-hidden flex flex-col">
                  <div class="px-6 py-5 border-b border-slate-100 bg-slate-50/50 flex justify-between items-start">
                    <h3 class="text-base font-semibold text-slate-900 group-hover:text-teal-700 transition-colors">{{ idea.title }}</h3>
                    <span v-if="idea.similarity !== null" :class="[idea.similarity < 30 ? 'bg-teal-50 text-teal-800 border-teal-200' : idea.similarity < 60 ? 'bg-amber-50 text-amber-800 border-amber-200' : 'bg-red-50 text-red-800 border-red-200', 'ml-3 inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium border']">
                      {{ idea.similarity }}% Match
                    </span>
                    <span v-else class="ml-3 inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600 border border-slate-200">
                      Unchecked
                    </span>
                  </div>
                  <div class="p-6 flex-1 flex flex-col">
                    <p class="text-sm text-slate-600 line-clamp-3 mb-4 flex-1">{{ idea.problem }}</p>
                    <div class="mt-auto flex flex-wrap gap-2">
                      <span v-for="tag in idea.tags.split(',').slice(0,3)" :key="tag" class="inline-flex items-center rounded-md bg-slate-100 px-2 py-1 text-[11px] font-medium text-slate-600">
                        {{ tag.trim() }}
                      </span>
                      <span v-if="idea.tags.split(',').length > 3" class="inline-flex items-center rounded-md bg-slate-50 px-2 py-1 text-[11px] font-medium text-slate-400">
                        +{{ idea.tags.split(',').length - 3 }}
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div v-if="workspaceTab === 'Active Proposal'" class="space-y-6">
               <div v-if="activeProposal" class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                 <div class="p-6 sm:p-8">
                   <div class="flex justify-between items-start mb-6">
                     <span class="inline-flex items-center rounded-md bg-teal-50 px-2.5 py-1 text-xs font-medium text-teal-700 ring-1 ring-inset ring-teal-700/10">Active Proposal</span>
                     <span class="inline-flex items-center rounded-md bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-800 ring-1 ring-inset ring-amber-600/20">{{ activeProposal.status }}</span>
                   </div>
                   
                   <h3 class="text-2xl font-bold text-slate-900 mb-4">{{ activeProposal.title }}</h3>
                   <div class="prose prose-sm prose-slate max-w-none mb-6">
                     <h4 class="text-slate-900 font-semibold mb-2">Problem Statement</h4>
                     <p class="text-slate-600 leading-relaxed mb-4">{{ activeProposal.problem }}</p>
                     
                     <h4 class="text-slate-900 font-semibold mb-2">Proposed Solution</h4>
                     <p class="text-slate-600 leading-relaxed">{{ activeProposal.solution }}</p>
                   </div>
                   
                   <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 bg-slate-50 rounded-lg p-5 border border-slate-100 mb-6">
                      <div>
                        <p class="text-xs font-medium text-slate-500">Domain</p>
                        <p class="mt-1 text-sm font-medium text-slate-900">{{ activeProposal.domain }}</p>
                      </div>
                      <div class="sm:col-span-2">
                        <p class="text-xs font-medium text-slate-500">Tags</p>
                        <p class="mt-1 text-sm text-slate-900">{{ activeProposal.tags }}</p>
                      </div>
                      <div>
                        <p class="text-xs font-medium text-slate-500">Similarity</p>
                        <p class="mt-1 text-sm font-bold" :class="[activeProposal.similarity < 30 ? 'text-teal-600' : activeProposal.similarity < 60 ? 'text-amber-600' : 'text-red-600']">
                           {{ activeProposal.similarity !== null ? activeProposal.similarity + '%' : 'Not Checked' }}
                        </p>
                      </div>
                   </div>
                   
                   <div class="flex gap-3">
                     <button @click="currentView = 'Similarity Report'" class="inline-flex items-center justify-center rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-teal-700 transition">View Full Report</button>
                     <button @click="openProposalDetails(activeProposal, 'active')" class="inline-flex items-center justify-center rounded-lg bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 transition">View All Details</button>
                   </div>
                 </div>
               </div>
               
               <div v-else class="text-center bg-white rounded-xl border border-slate-200 border-dashed py-20 px-6 shadow-sm">
                  <div class="mx-auto w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4">
                     <svg class="w-8 h-8 text-teal-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                  </div>
                  <h3 class="text-lg font-medium text-slate-900">No Active Proposal</h3>
                  <p class="mt-2 text-sm text-slate-500 max-w-md mx-auto">You have not confirmed a proposal yet. Go to your Draft Ideas, select a draft, and confirm it to submit to the domain.</p>
                  <button @click="workspaceTab = 'Draft Ideas'" class="mt-6 inline-flex items-center justify-center rounded-lg bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 transition">
                    View Drafts
                  </button>
               </div>
            </div>

            <div v-if="workspaceTab === 'Archived Ideas'" class="space-y-4">
               <div v-if="archivedIdeas.length === 0" class="text-center bg-white rounded-xl border border-slate-200 border-dashed py-16 px-6 shadow-sm">
                  <div class="mx-auto w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4">
                     <svg class="w-8 h-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                  </div>
                  <p class="text-base font-medium text-slate-900">No archived ideas</p>
                  <p class="mt-1 text-sm text-slate-500">Ideas you archive will appear here.</p>
               </div>
               
               <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div v-for="idea in archivedIdeas" :key="idea.id" @click="openProposalDetails(idea, 'archived')" class="cursor-pointer group rounded-xl border border-slate-200 bg-slate-50 shadow-sm hover:shadow-md hover:border-slate-300 transition-all overflow-hidden flex flex-col opacity-75 hover:opacity-100">
                  <div class="px-6 py-5 border-b border-slate-200 bg-slate-100/50 flex justify-between items-start">
                    <h3 class="text-base font-semibold text-slate-700">{{ idea.title }}</h3>
                    <span class="ml-3 inline-flex items-center rounded-full bg-slate-200 px-2.5 py-0.5 text-xs font-medium text-slate-700">
                      Archived
                    </span>
                  </div>
                  <div class="p-6 flex-1 flex flex-col">
                    <p class="text-sm text-slate-500 line-clamp-2 mb-4 flex-1">{{ idea.problem }}</p>
                    <div class="mt-auto">
                      <span class="text-xs text-slate-400 font-medium">Domain: {{ idea.domain }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- 3. Project Team -->
          <div v-else-if="currentView === 'Project Team'" class="space-y-6">
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
              <div class="p-6 sm:p-8 border-b border-slate-100 bg-slate-50/30 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <h3 class="text-lg font-semibold text-slate-900">Team Members</h3>
                <button v-if="teamMembers.length < 3" @click="openInviteModal" type="button" class="inline-flex items-center justify-center rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-teal-700 transition ring-1 ring-teal-600 shrink-0">
                  <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                  Invite Member
                </button>
                <div v-else class="text-sm text-amber-600 bg-amber-50 px-3 py-1.5 rounded-md border border-amber-200 font-medium">
                  Maximum team size reached (3/3)
                </div>
              </div>

              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                  <thead class="bg-slate-50">
                    <tr>
                      <th class="py-4 pl-6 pr-3 text-left text-sm font-semibold text-slate-900">Name</th>
                      <th class="px-3 py-4 text-left text-sm font-semibold text-slate-900">Registration Number</th>
                      <th class="px-3 py-4 text-left text-sm font-semibold text-slate-900">Role</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-100 bg-white">
                    <tr v-for="member in teamMembers" :key="member.id" class="hover:bg-slate-50/50 transition-colors">
                      <td class="whitespace-nowrap py-4 pl-6 pr-3 text-sm font-medium text-slate-900">
                        <div class="flex items-center">
                          <div class="h-8 w-8 rounded-full bg-slate-200 text-slate-600 flex items-center justify-center font-bold text-xs mr-3">
                             {{ member.name.split(' ').map(n => n[0]).join('') }}
                          </div>
                          {{ member.name }}
                        </div>
                      </td>
                      <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-600">{{ member.regNumber }}</td>
                      <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-600">
                        <span :class="[
                          member.role === 'Owner' ? 'bg-teal-50 text-teal-700 ring-teal-600/20' : 'bg-slate-100 text-slate-700 ring-slate-500/10',
                          'inline-flex items-center rounded-md px-2.5 py-1 text-xs font-medium ring-1 ring-inset'
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
            <div class="mb-8">
              <h2 class="text-2xl font-bold text-slate-900">Similarity Report</h2>
              <p class="mt-1 text-slate-500 text-sm">AI-based semantic comparison for the active proposal.</p>
            </div>
            
            <div v-if="!activeProposal" class="rounded-xl border border-slate-200 bg-white p-12 shadow-sm text-center">
               <p class="text-slate-500">You must confirm a proposal to view its similarity report.</p>
               <button @click="currentView = 'Project Workspace'" class="mt-4 text-teal-600 font-medium hover:text-teal-700">Go to Workspace</button>
            </div>
            <div v-else>
               <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                  <!-- Active Proposal Summary Card -->
                  <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
                     <div class="flex justify-between items-start mb-4">
                        <p class="text-sm font-semibold text-slate-500 uppercase tracking-wider">Active Proposal Summary</p>
                        <span :class="[activeProposal.similarity < 30 ? 'bg-teal-50 text-teal-700 border-teal-200' : activeProposal.similarity < 60 ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-red-50 text-red-700 border-red-200', 'inline-flex items-center rounded-full px-3 py-1 text-xs font-bold border shadow-sm']">
                           {{ activeProposal.similarity < 30 ? 'Low Risk' : activeProposal.similarity < 60 ? 'Medium Risk' : 'High Risk' }}
                        </span>
                     </div>
                     <h3 class="text-xl font-bold text-slate-900 mb-2">{{ activeProposal.title }}</h3>
                     <div class="flex flex-wrap gap-x-6 gap-y-2 mt-4 text-sm text-slate-600">
                        <div><span class="font-medium text-slate-900">Domain:</span> {{ activeProposal.domain }}</div>
                        <div><span class="font-medium text-slate-900">Status:</span> {{ activeProposal.status }}</div>
                        <div><span class="font-medium text-slate-900">Checked:</span> Just now</div>
                     </div>
                  </div>

                  <!-- Main Similarity Score Card -->
                  <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm flex flex-col items-center justify-center text-center relative overflow-hidden">
                     <div class="relative z-10">
                       <p class="text-sm font-semibold text-slate-500 mb-2">Overall Similarity</p>
                       <div class="text-5xl font-black mb-2" :class="activeProposal.similarity < 30 ? 'text-teal-600' : activeProposal.similarity < 60 ? 'text-amber-500' : 'text-red-500'">
                         {{ activeProposal.similarity }}<span class="text-3xl text-slate-400">%</span>
                       </div>
                       <p class="text-xs text-slate-500 mt-2 px-4">
                         This score represents how close the proposal idea is to previously submitted projects in the same domain.
                       </p>
                     </div>
                  </div>
               </div>

               <!-- AI Recommendation Card -->
               <div class="mb-6 rounded-xl border border-slate-200 bg-gradient-to-r from-slate-50 to-white p-6 shadow-sm border-l-4" :class="activeProposal.similarity < 30 ? 'border-l-teal-500' : activeProposal.similarity < 60 ? 'border-l-amber-500' : 'border-l-red-500'">
                  <div class="flex items-start gap-4">
                     <div class="mt-1" :class="activeProposal.similarity < 30 ? 'text-teal-500' : activeProposal.similarity < 60 ? 'text-amber-500' : 'text-red-500'">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                           <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                     </div>
                     <div>
                        <h4 class="text-sm font-bold text-slate-900 mb-1">AI Recommendation</h4>
                        <p class="text-sm text-slate-700">
                           {{ activeProposal.similarity < 30 
                               ? 'The proposal appears sufficiently distinct and can proceed for review. The core concepts show strong originality.' 
                               : activeProposal.similarity < 60 
                                  ? 'Some parts overlap with previous proposals. Review the matched projects below to ensure your specific implementation or objectives are unique before the domain review.' 
                                  : 'This proposal is highly similar to previous work and will likely be rejected. It is strongly recommended to revise the core problem or solution to be more unique.' }}
                        </p>
                     </div>
                  </div>
               </div>

               <!-- Similarity Breakdown -->
               <h3 class="text-lg font-semibold text-slate-900 mb-4 mt-8">Semantic Breakdown</h3>
               <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                  <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                     <p class="text-xs font-medium text-slate-500 mb-1">Title</p>
                     <p class="text-lg font-bold text-slate-800">{{ Math.max(0, activeProposal.similarity - 5) }}%</p>
                  </div>
                  <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                     <p class="text-xs font-medium text-slate-500 mb-1">Problem</p>
                     <p class="text-lg font-bold text-slate-800">{{ Math.min(100, activeProposal.similarity + 12) }}%</p>
                  </div>
                  <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                     <p class="text-xs font-medium text-slate-500 mb-1">Solution</p>
                     <p class="text-lg font-bold text-slate-800">{{ activeProposal.similarity }}%</p>
                  </div>
                  <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                     <p class="text-xs font-medium text-slate-500 mb-1">Objectives</p>
                     <p class="text-lg font-bold text-slate-800">{{ Math.max(0, activeProposal.similarity - 8) }}%</p>
                  </div>
               </div>

               <!-- Top Similar Projects -->
               <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                  <div class="px-6 py-5 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                    <h3 class="text-base font-semibold text-slate-900">Top Similar Projects in Database</h3>
                    <button @click="activeProposal.similarity = Math.floor(Math.random() * 40) + 10" class="text-xs font-medium text-teal-600 hover:text-teal-800 bg-teal-50 px-3 py-1.5 rounded-md transition-colors">
                      Recheck Similarity
                    </button>
                  </div>
                  <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                      <thead class="bg-white">
                        <tr>
                          <th class="py-3.5 pl-6 pr-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Rank</th>
                          <th class="px-3 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Project Title</th>
                          <th class="px-3 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Domain</th>
                          <th class="px-3 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Similarity</th>
                          <th class="px-3 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Matched Keywords</th>
                          <th class="px-3 py-3.5 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider pr-6">Action</th>
                        </tr>
                      </thead>
                      <tbody class="divide-y divide-slate-100 bg-white">
                        <tr v-for="(match, index) in topMatches" :key="index" class="hover:bg-slate-50/50 transition-colors">
                          <td class="whitespace-nowrap py-4 pl-6 pr-3 text-sm font-medium text-slate-400">#{{ index + 1 }}</td>
                          <td class="whitespace-nowrap px-3 py-4 text-sm font-medium text-slate-900">{{ match.title }}</td>
                          <td class="whitespace-nowrap px-3 py-4 text-sm text-slate-600">{{ match.domain }}</td>
                          <td class="whitespace-nowrap px-3 py-4 text-sm font-semibold" :class="match.similarity < 30 ? 'text-teal-600' : 'text-amber-600'">{{ match.similarity }}%</td>
                          <td class="whitespace-nowrap px-3 py-4 text-xs text-slate-500">AI, Chatbot, Education</td>
                          <td class="whitespace-nowrap px-3 py-4 text-sm text-right pr-6">
                             <button class="text-teal-600 hover:text-teal-900 font-medium text-sm transition-colors">View Details</button>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
               </div>

               <div class="mt-8 text-center">
                  <button @click="currentView = 'Project Workspace'" class="inline-flex items-center text-sm font-medium text-slate-600 hover:text-slate-900 transition-colors">
                     <svg class="w-4 h-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                     Back to Workspace
                  </button>
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

        </div>
      </main>
    </div>

    <!-- New Proposal Modal -->
    <div v-if="showNewProposalForm" class="fixed inset-0 z-50 overflow-y-auto">
       <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
          <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="showNewProposalForm = false"></div>
          
          <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 w-full max-w-3xl flex flex-col max-h-[90vh]">
             <div class="border-b border-slate-100 px-6 py-5 bg-slate-50/50 flex justify-between items-center shrink-0">
                <h3 class="text-xl font-bold text-slate-900">Draft New Proposal</h3>
                <button @click="showNewProposalForm = false" class="text-slate-400 hover:text-slate-600 transition-colors">
                   <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
             </div>
             
             <div class="px-6 py-6 overflow-y-auto flex-1 space-y-5">
                <div>
                  <label class="block text-sm font-medium text-slate-700 mb-1">Proposal Title</label>
                  <input v-model="newProposal.title" type="text" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm px-4 py-2 border" placeholder="Enter a descriptive title">
                </div>
                
                <div>
                  <label class="block text-sm font-medium text-slate-700 mb-1">Domain</label>
                  <select v-model="newProposal.domain" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm px-4 py-2 border bg-white">
                     <option value="" disabled>Select Domain</option>
                     <option>Programming & Software Engineering</option>
                     <option>Networks & Security</option>
                     <option>Artificial Intelligence</option>
                     <option>Information Systems</option>
                  </select>
                </div>
                
                <div>
                  <label class="block text-sm font-medium text-slate-700 mb-1">Problem Statement</label>
                  <textarea v-model="newProposal.problem" rows="3" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm px-4 py-2 border" placeholder="What specific problem are you solving?"></textarea>
                </div>

                <div>
                  <label class="block text-sm font-medium text-slate-700 mb-1">Proposed Solution</label>
                  <textarea v-model="newProposal.solution" rows="3" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm px-4 py-2 border" placeholder="How does your project solve this problem?"></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                   <div>
                     <label class="block text-sm font-medium text-slate-700 mb-1">Objectives</label>
                     <textarea v-model="newProposal.objectives" rows="2" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm px-4 py-2 border" placeholder="Main goals..."></textarea>
                   </div>
                   <div>
                     <label class="block text-sm font-medium text-slate-700 mb-1">Core Functions</label>
                     <textarea v-model="newProposal.functions" rows="2" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm px-4 py-2 border" placeholder="Key features..."></textarea>
                   </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                   <div>
                     <label class="block text-sm font-medium text-slate-700 mb-1">Tags / Keywords</label>
                     <input v-model="newProposal.tags" type="text" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm px-4 py-2 border" placeholder="e.g. AI, Web App, Healthcare">
                   </div>
                   <div>
                     <label class="block text-sm font-medium text-slate-700 mb-1">Technology Used</label>
                     <input v-model="newProposal.tech" type="text" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm px-4 py-2 border" placeholder="e.g. Vue, Laravel, Python">
                   </div>
                </div>
             </div>
             
             <div class="bg-slate-50 px-6 py-4 flex flex-wrap-reverse sm:flex-nowrap justify-between gap-3 border-t border-slate-100 shrink-0">
                <button @click="showNewProposalForm = false" type="button" class="w-full sm:w-auto inline-flex justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 focus:outline-none transition-colors">
                  Cancel
                </button>
                <div class="flex gap-3 w-full sm:w-auto">
                   <button @click="saveAsDraft" type="button" class="w-full sm:w-auto inline-flex justify-center rounded-lg border border-teal-600 bg-white px-4 py-2.5 text-sm font-medium text-teal-700 shadow-sm hover:bg-teal-50 focus:outline-none transition-colors">
                     Save as Draft
                   </button>
                   <button @click="saveAndConfirmProposal" type="button" class="w-full sm:w-auto inline-flex justify-center rounded-lg border border-transparent bg-teal-600 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-teal-700 focus:outline-none transition-colors">
                     Confirm Proposal
                   </button>
                </div>
             </div>
          </div>
       </div>
    </div>

    <!-- Proposal Details Modal -->
    <div v-if="showProposalModal && selectedProposal" class="fixed inset-0 z-50 overflow-y-auto">
       <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
          <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="closeProposalDetails"></div>
          
          <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 w-full max-w-4xl flex flex-col max-h-[90vh]">
             <div class="border-b border-slate-100 px-6 py-5 bg-slate-50/50 flex justify-between items-start shrink-0">
                <div>
                   <div class="flex items-center gap-3 mb-1">
                      <span v-if="selectedProposalType === 'active'" class="inline-flex items-center rounded-md bg-teal-50 px-2.5 py-1 text-xs font-medium text-teal-700 ring-1 ring-inset ring-teal-700/10">Active Proposal</span>
                      <span v-if="selectedProposalType === 'draft'" class="inline-flex items-center rounded-md bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700 ring-1 ring-inset ring-slate-500/20">Draft Idea</span>
                      <span v-if="selectedProposalType === 'archived'" class="inline-flex items-center rounded-md bg-slate-200 px-2.5 py-1 text-xs font-medium text-slate-700 ring-1 ring-inset ring-slate-500/20">Archived</span>
                      
                      <span v-if="selectedProposal.status" class="inline-flex items-center rounded-md bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-800 ring-1 ring-inset ring-amber-600/20">{{ selectedProposal.status }}</span>
                   </div>
                   <h3 class="text-2xl font-bold text-slate-900">{{ selectedProposal.title }}</h3>
                </div>
                <button @click="closeProposalDetails" class="text-slate-400 hover:text-slate-600 transition-colors p-1">
                   <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
             </div>
             
             <div class="px-6 py-6 overflow-y-auto flex-1 bg-white">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                   <div class="lg:col-span-2 space-y-6">
                      <div>
                         <h4 class="text-sm font-semibold text-slate-900 uppercase tracking-wider mb-2">Problem Statement</h4>
                         <p class="text-slate-600 leading-relaxed text-sm bg-slate-50 p-4 rounded-lg border border-slate-100">{{ selectedProposal.problem || 'Not specified.' }}</p>
                      </div>
                      <div>
                         <h4 class="text-sm font-semibold text-slate-900 uppercase tracking-wider mb-2">Proposed Solution</h4>
                         <p class="text-slate-600 leading-relaxed text-sm bg-slate-50 p-4 rounded-lg border border-slate-100">{{ selectedProposal.solution || 'Not specified.' }}</p>
                      </div>
                      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                         <div>
                            <h4 class="text-sm font-semibold text-slate-900 uppercase tracking-wider mb-2">Objectives</h4>
                            <p class="text-slate-600 text-sm whitespace-pre-wrap">{{ selectedProposal.objectives || 'Not specified.' }}</p>
                         </div>
                         <div>
                            <h4 class="text-sm font-semibold text-slate-900 uppercase tracking-wider mb-2">Core Functions</h4>
                            <p class="text-slate-600 text-sm whitespace-pre-wrap">{{ selectedProposal.functions || 'Not specified.' }}</p>
                         </div>
                      </div>
                   </div>
                   
                   <div class="space-y-6 lg:border-l lg:border-slate-100 lg:pl-8">
                      <div>
                         <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Similarity Score</h4>
                         <div class="flex items-center mt-2">
                           <template v-if="selectedProposal.similarity !== null">
                              <span class="text-3xl font-black" :class="selectedProposal.similarity < 30 ? 'text-teal-600' : selectedProposal.similarity < 60 ? 'text-amber-500' : 'text-red-500'">{{ selectedProposal.similarity }}%</span>
                              <span class="ml-3 text-sm text-slate-500 font-medium">Match</span>
                           </template>
                           <template v-else>
                              <span class="text-lg font-medium text-slate-500">Not Checked</span>
                           </template>
                         </div>
                      </div>
                      <div>
                         <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Domain</h4>
                         <p class="text-sm font-medium text-slate-900">{{ selectedProposal.domain || 'Not assigned' }}</p>
                      </div>
                      <div>
                         <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Tags / Keywords</h4>
                         <div class="flex flex-wrap gap-2">
                            <span v-for="tag in (selectedProposal.tags ? selectedProposal.tags.split(',') : [])" :key="tag" class="inline-flex items-center rounded-md bg-slate-100 px-2 py-1 text-xs font-medium text-slate-600">
                              {{ tag.trim() }}
                            </span>
                         </div>
                      </div>
                      <div>
                         <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Technology Used</h4>
                         <div class="flex flex-wrap gap-2">
                            <span v-for="tech in (selectedProposal.tech ? selectedProposal.tech.split(',') : [])" :key="tech" class="inline-flex items-center rounded-md bg-blue-50 text-blue-700 px-2 py-1 text-xs font-medium border border-blue-100">
                              {{ tech.trim() }}
                            </span>
                         </div>
                      </div>
                      <div>
                         <h4 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Created Date</h4>
                         <p class="text-sm text-slate-600">{{ selectedProposal.date || 'Today' }}</p>
                      </div>
                   </div>
                </div>
             </div>
             
             <div class="bg-slate-50 px-6 py-4 flex flex-wrap sm:flex-nowrap justify-end gap-3 border-t border-slate-100 shrink-0">
                <button class="inline-flex justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 focus:outline-none transition-colors">
                  Edit Proposal
                </button>
                
                <template v-if="selectedProposalType === 'draft'">
                   <button @click="checkSimilarity(selectedProposal)" class="inline-flex justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 focus:outline-none transition-colors">
                     Check Similarity
                   </button>
                   <button @click="archiveProposal(selectedProposal)" class="inline-flex justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-red-600 shadow-sm hover:bg-red-50 hover:border-red-200 focus:outline-none transition-colors">
                     Archive
                   </button>
                   <button @click="confirmDraftProposal(selectedProposal)" class="inline-flex justify-center rounded-lg border border-transparent bg-teal-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-teal-700 focus:outline-none transition-colors">
                     Confirm Proposal
                   </button>
                </template>

                <template v-else-if="selectedProposalType === 'active'">
                   <button @click="viewReportFromModal" class="inline-flex justify-center rounded-lg border border-transparent bg-teal-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-teal-700 focus:outline-none transition-colors">
                     View Report
                   </button>
                </template>

                <template v-else-if="selectedProposalType === 'archived'">
                   <button @click="restoreProposal(selectedProposal)" class="inline-flex justify-center rounded-lg border border-transparent bg-teal-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-teal-700 focus:outline-none transition-colors">
                     Restore to Draft
                   </button>
                </template>
             </div>
          </div>
       </div>
    </div>

    <!-- Invite Member Modal -->
    <div v-if="showInviteModal" class="fixed inset-0 z-50 overflow-y-auto">
       <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
          <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="closeInviteModal"></div>
          
          <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 w-full max-w-md flex flex-col">
             <div class="border-b border-slate-100 px-6 py-5 bg-slate-50/50 flex justify-between items-center">
                <h3 class="text-lg font-bold text-slate-900">Invite Team Member</h3>
                <button @click="closeInviteModal" class="text-slate-400 hover:text-slate-600 transition-colors">
                   <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
             </div>
             
             <div class="px-6 py-6">
                <p class="text-sm text-slate-500 mb-4">Enter the registration number of the student you wish to invite to your project team.</p>
                
                <div>
                  <label class="block text-sm font-medium text-slate-700 mb-1">Registration Number</label>
                  <input v-model="inviteRegNumber" type="text" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-teal-500 focus:ring-teal-500 sm:text-sm px-4 py-2 border" placeholder="e.g. 2023055">
                  <p v-if="inviteError" class="mt-2 text-sm text-red-600">{{ inviteError }}</p>
                </div>
             </div>
             
             <div class="bg-slate-50 px-6 py-4 flex justify-end gap-3 border-t border-slate-100">
                <button @click="closeInviteModal" type="button" class="inline-flex justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 focus:outline-none transition-colors">
                  Cancel
                </button>
                <button @click="sendInvitation" type="button" class="inline-flex justify-center rounded-lg border border-transparent bg-teal-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-teal-700 focus:outline-none transition-colors">
                  Send Invitation
                </button>
             </div>
          </div>
       </div>
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
];

const currentView = ref('Overview');

// Workspace state
const workspaceTabs = ['Draft Ideas', 'Active Proposal', 'Archived Ideas'];
const workspaceTab = ref('Draft Ideas');

// Modal states
const showNewProposalForm = ref(false);
const showProposalModal = ref(false);
const showInviteModal = ref(false);
const selectedProposal = ref(null);
const selectedProposalType = ref(''); // 'draft', 'active', 'archived'

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
  date: 'Just now'
};

const newProposal = ref({ ...emptyProposal });

// Sample Data Structure adapted for Domain
const draftIdeas = ref([
  { id: 1, title: 'Smart IoT Home System', problem: 'Homes lack secure localized control.', solution: 'A system to manage home appliances securely via cloud-integrated IoT devices with local fallback capabilities.', domain: 'Information Systems', objectives: 'Improve security\nReduce latency', functions: 'Device control\nAlerts', tags: 'IoT, Smart Home, Automation', tech: 'Vue, Node, MQTT', similarity: 12, date: '2026-05-01' },
  { id: 2, title: 'E-commerce Recommendation Engine', problem: 'Users cannot find relevant products easily.', solution: 'Online store component that provides AI-based product recommendations based on user browsing history.', domain: 'Artificial Intelligence', objectives: 'Increase sales\nImprove UX', functions: 'Tracking\nSuggestion UI', tags: 'E-commerce, AI, Machine Learning', tech: 'Python, React', similarity: 45, date: '2026-05-05' },
]);

// Start with no active proposal to demonstrate the flow
const activeProposal = ref(null);

const archivedIdeas = ref([]);

const teamMembers = ref([
  { id: 1, name: 'Tayri Musa', role: 'Owner', regNumber: '2023001' },
]);

const topMatches = ref([
  { title: 'University Assistant Bot', similarity: 28, domain: 'Artificial Intelligence' },
  { title: 'Smart Student Helper', similarity: 15, domain: 'Programming & Software Engineering' },
]);

const versionHistory = ref([
  { version: 'v1.0', date: '2026-05-07', note: 'Project workspace created.' },
]);

const departmentFeedback = ref([]);

// Functions

function openNewProposal() {
  newProposal.value = { ...emptyProposal };
  showNewProposalForm.value = true;
}

function saveAsDraft() {
  if (!newProposal.value.title) {
    alert('Please enter at least a title to save as draft.');
    return;
  }
  const draft = {
    ...newProposal.value,
    id: Date.now(),
    similarity: null
  };
  draftIdeas.value.unshift(draft);
  showNewProposalForm.value = false;
  workspaceTab.value = 'Draft Ideas';
}

function saveAndConfirmProposal() {
  if (!newProposal.value.title || !newProposal.value.domain) {
    alert('Title and Domain are required to confirm a proposal.');
    return;
  }
  const prop = {
    ...newProposal.value,
    id: Date.now(),
    similarity: newProposal.value.similarity !== null ? newProposal.value.similarity : Math.floor(Math.random() * 40) + 10,
    status: 'Under Review'
  };
  activeProposal.value = prop;
  showNewProposalForm.value = false;
  workspaceTab.value = 'Active Proposal';
  versionHistory.value.unshift({ version: 'v1.1', date: 'Just now', note: 'Initial proposal submitted for review.' });
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

function checkSimilarity(proposal) {
  const score = Math.floor(Math.random() * 85) + 5; // Generate temp score 5 - 90
  proposal.similarity = score;
  
  // Also update in list
  if (selectedProposalType.value === 'draft') {
     const idx = draftIdeas.value.findIndex(d => d.id === proposal.id);
     if (idx !== -1) draftIdeas.value[idx].similarity = score;
  }
  selectedProposal.value.similarity = score;
}

function confirmDraftProposal(proposal) {
  if (!proposal.title || !proposal.domain) {
    alert('Title and Domain are required.');
    return;
  }
  if (proposal.similarity === null) {
    proposal.similarity = Math.floor(Math.random() * 40) + 10;
  }
  proposal.status = 'Under Review';
  
  activeProposal.value = { ...proposal };
  draftIdeas.value = draftIdeas.value.filter(d => d.id !== proposal.id);
  
  closeProposalDetails();
  workspaceTab.value = 'Active Proposal';
  versionHistory.value.unshift({ version: 'v1.1', date: 'Just now', note: 'Draft converted to active proposal.' });
}

function archiveProposal(proposal) {
  archivedIdeas.value.unshift({ ...proposal });
  draftIdeas.value = draftIdeas.value.filter(d => d.id !== proposal.id);
  closeProposalDetails();
}

function restoreProposal(proposal) {
  draftIdeas.value.unshift({ ...proposal });
  archivedIdeas.value = archivedIdeas.value.filter(a => a.id !== proposal.id);
  closeProposalDetails();
  workspaceTab.value = 'Draft Ideas';
}

function viewReportFromModal() {
  closeProposalDetails();
  currentView.value = 'Similarity Report';
}

function openInviteModal() {
  inviteError.value = '';
  inviteRegNumber.value = '';
  showInviteModal.value = true;
}

function closeInviteModal() {
  showInviteModal.value = false;
}

function sendInvitation() {
  if (!inviteRegNumber.value) {
    inviteError.value = 'Please enter a registration number.';
    return;
  }
  if (teamMembers.value.length >= 3) {
    inviteError.value = 'Maximum team size reached. You can only have 3 students per project.';
    return;
  }
  
  teamMembers.value.push({
    id: Date.now(),
    name: 'Student ' + inviteRegNumber.value,
    role: 'Member',
    regNumber: inviteRegNumber.value
  });
  
  closeInviteModal();
}
</script>
