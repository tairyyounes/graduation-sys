import { watch, onMounted } from 'vue'

export function usePersistedForm(key, formObj) {
  onMounted(() => {
    try {
      const saved = localStorage.getItem(key)
      if (saved) {
        Object.assign(formObj, JSON.parse(saved))
      }
    } catch (e) {
      console.warn('Could not load persisted form from localStorage', e)
    }
  })

  watch(
    formObj,
    (newVal) => {
      try {
        localStorage.setItem(key, JSON.stringify(newVal))
      } catch (e) {
        console.warn('Could not save persisted form to localStorage', e)
      }
    },
    { deep: true }
  )

  const clearPersistedForm = () => {
    try {
      localStorage.removeItem(key)
    } catch (e) {
      console.warn('Could not remove persisted form from localStorage', e)
    }
  }

  return { clearPersistedForm }
}
