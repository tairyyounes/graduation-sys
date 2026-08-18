<template>
  <div class="min-h-screen bg-gradient-to-b from-[#f4fbfc] to-[#f8fcfd] font-sans text-gray-900 relative overflow-hidden">
    <!-- Navbar -->
    <nav class="flex items-center justify-between px-6 py-4 bg-transparent max-w-7xl mx-auto relative z-10">
      <div class="flex items-center space-x-3">
        <!-- Logo -->
        <div class="w-10 h-10 bg-[#16516f] rounded-lg flex items-center justify-center text-white shrink-0 shadow-sm">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
          </svg>
        </div>
        <div>
          <div class="font-bold text-[#0f2130] text-lg leading-tight">{{ $t('proposals.proposalguard_ai') }}</div>
          <div class="text-[11px] text-gray-500 font-medium tracking-wide">{{ $t('messages.college_of_computer_technology') }}</div>
        </div>
      </div>

      <div class="hidden md:flex space-x-8 text-sm font-semibold text-gray-500">
        <a href="#" class="text-gray-800 transition-colors">{{ $t('common.home') }}</a>
        <a href="#" class="hover:text-gray-800 transition-colors">{{ $t('common.features') }}</a>
        <a href="#" class="hover:text-gray-800 transition-colors">{{ $t('common.how_it_works') }}</a>
      </div>

      <div class="flex items-center space-x-4">
        <LanguageSwitcher />
        <a
          v-if="!isAuthenticated"
          :href="loginUrl"
          class="px-5 py-2.5 text-sm font-semibold text-white bg-[#193652] rounded-lg hover:bg-[#0f2130] transition-colors shadow-sm"
        >
          {{ $t('auth.login') }}
        </a>

        <div v-else class="flex items-center space-x-3">
          <a
            href="/dashboard"
            class="px-5 py-2.5 text-sm font-semibold text-[#193652] bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors shadow-sm"
          >
            {{ $t('dashboard.dashboard') }}
          </a>
          <form :action="logoutUrl" method="POST" class="m-0 p-0">
            <input type="hidden" name="_token" :value="csrfToken" />
            <button
              type="submit"
              class="px-5 py-2.5 text-sm font-semibold text-white bg-[#193652] rounded-lg hover:bg-[#0f2130] transition-colors shadow-sm"
            >
              {{ $t('auth.logout') }}
            </button>
          </form>
        </div>
      </div>
    </nav>

    <!-- Hero Section -->
    <main class="max-w-[1000px] mx-auto px-6 pt-24 pb-20 text-center relative z-10">

      <h1 class="text-[3.5rem] md:text-[4rem] font-[800] text-[#0a1827] tracking-tight leading-[1.1] mb-6">
        <span v-html="$t('proposals.detect_similarity_br')"></span>
        <span class="text-[#0d7f95]">{{ $t('messages.before_approval') }}</span>
      </h1>

      <p class="text-lg md:text-[1.15rem] text-gray-500 max-w-[800px] mx-auto mb-12 leading-relaxed font-medium">
        {{ $t('students.an_academic_platform_that_helpss_submit_proposals_and_helps_departments_make_confident_accept_reject_or_revise_decisions_backed_by_semantic_ai') }}
      </p>

      <div class="flex flex-col sm:flex-row items-center justify-center space-y-4 sm:space-y-0 sm:space-x-4 mb-24">
        <template v-if="!isAuthenticated">
          <a href="#" class="px-6 py-3.5 bg-[#193652] text-white text-[15px] font-semibold rounded-xl hover:bg-[#0f2130] transition-colors flex items-center shadow-lg shadow-[#193652]/20">
            <span>{{ $t('proposals.submit_a') }}</span>
            <svg class="w-4 h-4 ml-2 rtl:mr-2 rtl:ml-0 rtl:-scale-x-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
          </a>
        </template>
        <template v-else>
          <a href="/dashboard" class="px-6 py-3.5 bg-[#193652] text-white text-[15px] font-semibold rounded-xl hover:bg-[#0f2130] transition-colors flex items-center shadow-lg shadow-[#193652]/20">
            <span>{{ $t('dashboard.go_to_dashboard') }}</span>
            <svg class="w-4 h-4 ml-2 rtl:mr-2 rtl:ml-0 rtl:-scale-x-100" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
          </a>
        </template>
      </div>

      <!-- Stats -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-6 max-w-5xl mx-auto">
        <div class="bg-white rounded-2xl py-8 px-6 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)] border border-gray-100 flex flex-col items-center justify-center hover:-translate-y-1 transition-transform duration-300">
          <div class="text-[2rem] font-[800] text-[#0a1827] mb-1">1,240+</div>
          <div class="text-[13px] text-gray-500 font-semibold">{{ $t('proposals.proposals_analyzed') }}</div>
        </div>
        <div class="bg-white rounded-2xl py-8 px-6 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)] border border-gray-100 flex flex-col items-center justify-center hover:-translate-y-1 transition-transform duration-300">
          <div class="text-[2rem] font-[800] text-[#0a1827] mb-1">94%</div>
          <div class="text-[13px] text-gray-500 font-semibold">{{ $t('messages.semantic_accuracy') }}</div>
        </div>
        <div class="bg-white rounded-2xl py-8 px-6 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)] border border-gray-100 flex flex-col items-center justify-center hover:-translate-y-1 transition-transform duration-300">
          <div class="text-[2rem] font-[800] text-[#0a1827] mb-1">3</div>
          <div class="text-[13px] text-gray-500 font-semibold">{{ $t('departments.departments') }}</div>
        </div>
        <div class="bg-white rounded-2xl py-8 px-6 shadow-[0_2px_10px_-3px_rgba(6,81,237,0.05)] border border-gray-100 flex flex-col items-center justify-center hover:-translate-y-1 transition-transform duration-300">
          <div class="text-[2rem] font-[800] text-[#0a1827] mb-1">&lt; 30s</div>
          <div class="text-[13px] text-gray-500 font-semibold">{{ $t('decisions.avg_review_time') }}</div>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import LanguageSwitcher from './common/LanguageSwitcher.vue'

const appRoot = document.getElementById('app')
const isAuthenticated = ref(appRoot?.dataset.authenticated === '1')
const loginUrl = ref(appRoot?.dataset.loginUrl || '/login')
const logoutUrl = ref(appRoot?.dataset.logoutUrl || '/logout')
const csrfToken = ref(document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '')
</script>
