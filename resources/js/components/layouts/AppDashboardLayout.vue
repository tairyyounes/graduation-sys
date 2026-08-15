<template>
  <div class="min-h-screen bg-slate-50 text-slate-800">
    <div class="flex min-h-screen w-full">
      <div
        v-if="sidebarOpen"
        class="fixed inset-0 z-30 bg-slate-900/40 backdrop-blur-[1px] lg:hidden"
        @click="sidebarOpen = false"
      ></div>

      <aside
        class="fixed inset-y-0 start-0 z-40 flex w-72 flex-col border-e border-slate-200 bg-white transition-transform duration-300 ease-out lg:static lg:w-64 lg:translate-x-0"
        :class="sidebarOpen ? 'translate-x-0' : 'max-lg:-translate-x-full max-lg:rtl:translate-x-full'"
      >
        <div class="border-b border-slate-100 px-5 py-6">
          <div class="flex items-start gap-3">
            <div class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-teal-500 to-cyan-600 text-white shadow-md shadow-teal-500/20">
              <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2l7 4v6c0 5-3.5 8.5-7 10-3.5-1.5-7-5-7-10V6l7-4z" />
              </svg>
            </div>
            <div>
              <p class="text-lg font-bold tracking-tight text-slate-900">{{ brandTitle }}</p>
              <p class="mt-1 text-xs leading-4 text-slate-500">{{ brandSubtitle }}</p>
            </div>
          </div>
        </div>

        <nav class="flex-1 px-4 py-6 overflow-y-auto">
          <p class="mb-4 px-2 text-[11px] font-semibold uppercase tracking-wider text-slate-500">{{ navTitle }}</p>
          <ul class="space-y-1.5">
            <li v-for="item in navItems" :key="item.key">
              <router-link
                :to="{ name: item.routeName }"
                class="group flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-medium transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2"
                active-class="bg-teal-50 text-teal-800 shadow-sm ring-1 ring-teal-500/10"
                exact-active-class="bg-teal-50 text-teal-800 shadow-sm ring-1 ring-teal-500/10"
                :class="$route.name !== item.routeName ? 'text-slate-600 hover:bg-slate-50 hover:text-slate-900' : ''"
                @click="sidebarOpen = false"
              >
                <span :class="[$route.name === item.routeName ? 'text-teal-600' : 'text-slate-400 group-hover:text-slate-600']" v-html="item.icon"></span>
                <span>{{ item.label }}</span>
              </router-link>
            </li>
          </ul>
        </nav>

        <div class="mt-auto border-t border-slate-100 bg-slate-50/50 px-5 py-5">
          <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-teal-100 text-sm font-bold text-teal-800">
              {{ user.initials }}
            </div>
            <div class="overflow-hidden">
              <p class="truncate text-sm font-semibold text-slate-900">{{ user.name }}</p>
              <p class="truncate text-xs text-slate-500">{{ user.email }}</p>
            </div>
          </div>
          <div class="mt-4">
             <div class="flex items-center justify-between">
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

      <main class="flex-1 p-4 sm:p-6 lg:p-8 overflow-y-auto relative">
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

          <div class="hidden lg:block text-2xl font-bold text-slate-900 tracking-tight">{{ currentTitle }}</div>

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

        <div class="lg:hidden text-2xl font-bold text-slate-900 tracking-tight mb-6">{{ currentTitle }}</div>

        <slot name="alerts" />
        <router-view v-slot="{ Component }">
          <transition name="fade">
            <component :is="Component" />
          </transition>
        </router-view>
      </main>
    </div>
  </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.25s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>

<script setup>
import { ref, computed } from 'vue'
import { useRoute } from 'vue-router'
import LangToggle from '../common/LangToggle.vue'

const props = defineProps({
  navItems: {
    type: Array,
    required: true,
  },
  brandTitle: {
    type: String,
    default: 'ProposalGuard AI',
  },
  brandSubtitle: {
    type: String,
    default: 'College of Computer Technology - Tripoli',
  },
  navTitle: {
    type: String,
    default: 'Dashboard',
  },
  user: {
    type: Object,
    required: true,
  },
})

const route = useRoute()
const currentTitle = computed(() => {
  const activeItem = props.navItems.find(i => i.routeName === route.name)
  return activeItem ? activeItem.label : props.navTitle
})

const sidebarOpen = ref(false)
const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
</script>
