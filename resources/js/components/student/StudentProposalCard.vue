<template>
  <div
    @click="$emit('open')"
    class="cursor-pointer group rounded-xl border border-slate-200 shadow-sm hover:shadow-md transition-all overflow-hidden flex flex-col"
    :class="type === 'archived'
      ? 'bg-slate-50 hover:border-slate-300 opacity-75 hover:opacity-100'
      : 'bg-white hover:border-teal-300'"
  >
    <!-- Card Header -->
    <div
      class="px-6 py-5 border-b flex justify-between items-start"
      :class="type === 'archived' ? 'border-slate-200 bg-slate-100/50' : 'border-slate-100 bg-slate-50/50'"
    >
      <h3
        class="text-base font-semibold transition-colors"
        :class="type === 'archived' ? 'text-slate-700' : 'text-slate-900 group-hover:text-teal-700'"
      >
        {{ proposal.title }}
      </h3>

      <span v-if="type === 'archived'" class="ms-3 inline-flex items-center rounded-full bg-slate-200 px-2.5 py-0.5 text-xs font-medium text-slate-700">
        {{ $t('student.card.archived') }}
      </span>
      <template v-else>
        <span v-if="proposal.similarity !== null" :class="[
          proposal.similarity < 30 ? 'bg-teal-50 text-teal-800 border-teal-200'
          : proposal.similarity < 60 ? 'bg-amber-50 text-amber-800 border-amber-200'
          : 'bg-red-50 text-red-800 border-red-200',
          'ms-3 inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium border'
        ]">
          {{ $t('student.card.match', { percent: proposal.similarity }) }}
        </span>
        <span v-else class="ms-3 inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600 border border-slate-200">
          {{ $t('student.card.unchecked') }}
        </span>
      </template>
    </div>

    <!-- Card Body -->
    <div class="p-6 flex-1 flex flex-col">
      <p
        class="text-sm line-clamp-3 mb-4 flex-1"
        :class="type === 'archived' ? 'text-slate-500' : 'text-slate-600'"
      >
        {{ proposal.problem }}
      </p>
      <div class="mt-auto">
        <template v-if="type === 'archived'">
          <span class="text-xs text-slate-400 font-medium">{{ $t('fields.domain') }}: {{ proposal.domain }}</span>
        </template>
        <div v-else class="flex flex-wrap gap-2">
          <template v-if="proposal.tags">
            <span v-for="tag in proposal.tags.split(',').slice(0, 3)" :key="tag" class="inline-flex items-center rounded-md bg-slate-100 px-2 py-1 text-[11px] font-medium text-slate-600">
              {{ tag.trim() }}
            </span>
            <span v-if="proposal.tags.split(',').length > 3" class="inline-flex items-center rounded-md bg-slate-50 px-2 py-1 text-[11px] font-medium text-slate-400">
              +{{ proposal.tags.split(',').length - 3 }}
            </span>
          </template>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  proposal: {
    type: Object,
    required: true,
  },
  type: {
    type: String,
    default: 'draft', // 'draft' | 'archived'
  },
})

defineEmits(['open'])
</script>
