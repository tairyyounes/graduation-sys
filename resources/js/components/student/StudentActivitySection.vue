<template>
  <div class="space-y-6">
    <div class="rounded-xl border border-slate-200 bg-white p-6 sm:p-8 shadow-sm">
      <h3 class="text-lg font-bold text-slate-900 mb-6 flex items-center gap-2">
        <svg class="h-5 w-5 text-teal-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        Activity Timeline
      </h3>

      <div class="relative">
        <!-- Vertical Line -->
        <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-slate-100 lg:left-6"></div>

        <div class="space-y-8">
          <div v-for="(group, date) in groupedActivities" :key="date" class="relative">
            <!-- Date Header -->
            <div class="sticky top-0 z-10 mb-4 flex items-center">
              <div class="ml-10 rounded-full bg-slate-50 px-3 py-1 text-[11px] font-bold uppercase tracking-wider text-slate-500 border border-slate-200 lg:ml-14">
                {{ date }}
              </div>
            </div>

            <div class="space-y-6">
              <div v-for="activity in group" :key="activity.id" class="relative flex items-start group">
                <!-- Icon and Indicator -->
                <div 
                  class="relative z-10 flex h-8 w-8 shrink-0 items-center justify-center rounded-full ring-4 ring-white lg:h-12 lg:w-12"
                  :class="getStatusBg(activity.status)"
                >
                  <span :class="getStatusTextColor(activity.status)" v-html="getActivityIcon(activity.type)"></span>
                </div>

                <!-- Content -->
                <div class="ml-4 min-w-0 flex-1 pt-1 lg:ml-6 lg:pt-3">
                  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1">
                    <p class="text-sm font-bold text-slate-900 lg:text-base">
                      {{ activity.action }}
                    </p>
                    <time class="text-[11px] font-medium text-slate-400 sm:text-xs">
                      {{ activity.time }}
                    </time>
                  </div>
                  <p class="mt-1 text-sm text-slate-600 leading-relaxed max-w-2xl">
                    {{ activity.description }}
                  </p>
                  
                  <!-- Metadata/Status Badge -->
                  <div v-if="activity.meta" class="mt-2 flex items-center gap-3">
                    <span 
                      v-if="activity.statusLabel"
                      class="inline-flex items-center rounded-md px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider ring-1 ring-inset"
                      :class="getStatusBadgeClass(activity.status)"
                    >
                      {{ activity.statusLabel }}
                    </span>
                    <span v-if="activity.target" class="text-[11px] font-medium text-slate-400">
                      Ref: {{ activity.target }}
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Load More Placeholder -->
      <div class="mt-10 text-center">
        <button class="text-sm font-semibold text-slate-500 hover:text-teal-600 transition-colors">
          View Older Activity
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  activities: {
    type: Array,
    required: true
  }
});

const groupedActivities = computed(() => {
  const groups = {};
  props.activities.forEach(activity => {
    const dateGroup = activity.dateGroup || 'Recent';
    if (!groups[dateGroup]) {
      groups[dateGroup] = [];
    }
    groups[dateGroup].push(activity);
  });
  return groups;
});

const getStatusBg = (status) => {
  switch (status) {
    case 'blue': return 'bg-blue-50';
    case 'green': return 'bg-emerald-50';
    case 'yellow': return 'bg-amber-50';
    case 'red': return 'bg-rose-50';
    default: return 'bg-slate-50';
  }
};

const getStatusTextColor = (status) => {
  switch (status) {
    case 'blue': return 'text-blue-600';
    case 'green': return 'text-emerald-600';
    case 'yellow': return 'text-amber-600';
    case 'red': return 'text-rose-600';
    default: return 'text-slate-600';
  }
};

const getStatusBadgeClass = (status) => {
  switch (status) {
    case 'blue': return 'bg-blue-50 text-blue-700 ring-blue-600/20';
    case 'green': return 'bg-emerald-50 text-emerald-700 ring-emerald-600/20';
    case 'yellow': return 'bg-amber-50 text-amber-700 ring-amber-600/20';
    case 'red': return 'bg-rose-50 text-rose-700 ring-rose-600/20';
    default: return 'bg-slate-50 text-slate-700 ring-slate-600/20';
  }
};

const getActivityIcon = (type) => {
  const icons = {
    proposal: '<svg class="h-4 w-4 lg:h-6 lg:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>',
    analysis: '<svg class="h-4 w-4 lg:h-6 lg:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>',
    version: '<svg class="h-4 w-4 lg:h-6 lg:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
    team: '<svg class="h-4 w-4 lg:h-6 lg:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>',
    archive: '<svg class="h-4 w-4 lg:h-6 lg:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" /></svg>',
    submission: '<svg class="h-4 w-4 lg:h-6 lg:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
    feedback: '<svg class="h-4 w-4 lg:h-6 lg:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" /></svg>',
    status: '<svg class="h-4 w-4 lg:h-6 lg:w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>',
  };
  return icons[type] || icons.status;
};
</script>
