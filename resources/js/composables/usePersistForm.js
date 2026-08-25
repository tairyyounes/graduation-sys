import { watch, onMounted, isRef, isReactive } from 'vue';

/**
 * Persists a form object (ref or reactive) to localStorage and hydrates it on load.
 * 
 * @param {string} key - The unique localStorage key
 * @param {import('vue').Ref<object>|object} form - The reactive form reference or reactive object
 * @param {object} options - Options for persistence
 * @param {function} options.shouldPersist - Function returning boolean to determine if changes should be saved
 * @returns {function} clear - A function to clear the persisted data from localStorage
 */
export function usePersistForm(key, form, options = {}) {
  const load = () => {
    try {
      const saved = localStorage.getItem(key);
      if (saved) {
        const parsed = JSON.parse(saved);
        const target = isRef(form) ? form.value : form;
        
        if (target && typeof target === 'object') {
          for (const k in parsed) {
            target[k] = parsed[k];
          }
        } else if (isRef(form)) {
          form.value = parsed;
        }
        return true;
      }
    } catch (e) {
      console.warn('Failed to load persisted form:', e);
    }
    return false;
  };

  // Restore on mount
  onMounted(() => {
    load();
  });

  // Save on change
  watch(
    form,
    (newVal) => {
      try {
        if (options.shouldPersist && !options.shouldPersist()) {
          return;
        }
        const valueToSave = isRef(form) ? newVal : form;
        localStorage.setItem(key, JSON.stringify(valueToSave));
      } catch (e) {
        console.warn('Failed to persist form:', e);
      }
    },
    { deep: true }
  );

  const clear = () => {
    localStorage.removeItem(key);
  };

  return { clear, load };
}
