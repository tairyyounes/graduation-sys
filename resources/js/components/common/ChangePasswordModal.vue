<template>
  <div
    v-if="isOpen"
    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-xs px-4 py-8"
    @click.self="closeModal"
  >
    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl transition-all">
      <div class="mb-5 flex items-center justify-between">
        <div class="flex items-center gap-2.5">
          <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-teal-50 text-teal-600">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
            </svg>
          </div>
          <h2 class="text-lg font-bold text-slate-900">
            {{ $t('profile.change_password') }}
          </h2>
        </div>
        <button
          class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition"
          @click="closeModal"
        >
          <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <form class="space-y-4" @submit.prevent="submitForm">
        <!-- Current Password -->
        <div>
          <label class="mb-1.5 block text-sm font-medium text-slate-700">
            {{ $t('profile.current_password') }} <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <input
              v-model="form.current_password"
              :type="showCurrent ? 'text' : 'password'"
              class="w-full rounded-xl border px-3.5 py-2.5 pe-10 text-sm shadow-xs outline-none transition focus:ring-2"
              :class="errors.current_password ? 'border-red-300 focus:border-red-500 focus:ring-red-500/20' : 'border-slate-300 focus:border-teal-500 focus:ring-teal-500/20'"
              placeholder="••••••••"
              required
            />
            <button
              type="button"
              @click="showCurrent = !showCurrent"
              class="absolute inset-y-0 end-0 flex items-center pe-3 text-slate-400 hover:text-slate-600"
            >
              <svg v-if="!showCurrent" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
              <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
            </button>
          </div>
          <p v-if="errors.current_password" class="mt-1 text-xs text-red-600">{{ errors.current_password[0] }}</p>
        </div>

        <!-- New Password -->
        <div>
          <label class="mb-1.5 block text-sm font-medium text-slate-700">
            {{ $t('profile.new_password') }} <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <input
              v-model="form.password"
              :type="showNew ? 'text' : 'password'"
              class="w-full rounded-xl border px-3.5 py-2.5 pe-10 text-sm shadow-xs outline-none transition focus:ring-2"
              :class="errors.password ? 'border-red-300 focus:border-red-500 focus:ring-red-500/20' : 'border-slate-300 focus:border-teal-500 focus:ring-teal-500/20'"
              placeholder="••••••••"
              required
            />
            <button
              type="button"
              @click="showNew = !showNew"
              class="absolute inset-y-0 end-0 flex items-center pe-3 text-slate-400 hover:text-slate-600"
            >
              <svg v-if="!showNew" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
              <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
            </button>
          </div>
          <p v-if="errors.password" class="mt-1 text-xs text-red-600">{{ errors.password[0] }}</p>
          <p v-else class="mt-1 text-[11px] text-slate-400">{{ $t('profile.password_min_length') }}</p>
        </div>

        <!-- Confirm Password -->
        <div>
          <label class="mb-1.5 block text-sm font-medium text-slate-700">
            {{ $t('profile.confirm_new_password') }} <span class="text-red-500">*</span>
          </label>
          <div class="relative">
            <input
              v-model="form.password_confirmation"
              :type="showConfirm ? 'text' : 'password'"
              class="w-full rounded-xl border px-3.5 py-2.5 pe-10 text-sm shadow-xs outline-none transition focus:ring-2"
              :class="errors.password_confirmation || (form.password && form.password_confirmation && form.password !== form.password_confirmation) ? 'border-red-300 focus:border-red-500 focus:ring-red-500/20' : 'border-slate-300 focus:border-teal-500 focus:ring-teal-500/20'"
              placeholder="••••••••"
              required
            />
            <button
              type="button"
              @click="showConfirm = !showConfirm"
              class="absolute inset-y-0 end-0 flex items-center pe-3 text-slate-400 hover:text-slate-600"
            >
              <svg v-if="!showConfirm" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
              <svg v-else class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
            </button>
          </div>
          <p v-if="form.password && form.password_confirmation && form.password !== form.password_confirmation" class="mt-1 text-xs text-red-600">
            {{ $t('profile.passwords_must_match') }}
          </p>
        </div>

        <p v-if="errors.general" class="text-sm text-red-600 font-medium">{{ errors.general }}</p>

        <!-- Actions -->
        <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2.5 pt-3 border-t border-slate-100">
          <button
            type="button"
            class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition focus:outline-none focus:ring-2 focus:ring-slate-400"
            @click="closeModal"
          >
            {{ $t('common.cancel') }}
          </button>
          <button
            type="submit"
            class="inline-flex items-center justify-center gap-2 rounded-xl bg-teal-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-teal-700 transition focus:outline-none focus:ring-2 focus:ring-teal-500 disabled:opacity-50"
            :disabled="submitting"
          >
            <svg v-if="submitting" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ submitting ? $t('common.saving') : $t('profile.save_password') }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue';
import { useToast } from 'vue-toastification';
import { useI18n } from 'vue-i18n';

const props = defineProps({
  isOpen: {
    type: Boolean,
    required: true,
  },
});

const emit = defineEmits(['close']);

const toast = useToast();
const { t } = useI18n();

const form = reactive({
  current_password: '',
  password: '',
  password_confirmation: '',
});

const errors = ref({});
const submitting = ref(false);
const showCurrent = ref(false);
const showNew = ref(false);
const showConfirm = ref(false);

const getCsrfToken = () => {
  return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
};

const closeModal = () => {
  form.current_password = '';
  form.password = '';
  form.password_confirmation = '';
  errors.value = {};
  showCurrent.value = false;
  showNew.value = false;
  showConfirm.value = false;
  emit('close');
};

const submitForm = async () => {
  errors.value = {};

  if (form.password !== form.password_confirmation) {
    errors.value = { password_confirmation: [t('profile.passwords_must_match')] };
    return;
  }

  submitting.value = true;
  try {
    const response = await fetch('/password', {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
      body: JSON.stringify({
        current_password: form.current_password,
        password: form.password,
        password_confirmation: form.password_confirmation,
      }),
    });

    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
      if (data.errors) {
        errors.value = data.errors;
      } else {
        errors.value = { general: data.message || t('profile.update_failed') };
      }
      return;
    }

    toast.success(data.message || t('profile.password_updated_success'));
    closeModal();
  } catch (err) {
    errors.value = { general: t('profile.update_failed') };
  } finally {
    submitting.value = false;
  }
};
</script>
