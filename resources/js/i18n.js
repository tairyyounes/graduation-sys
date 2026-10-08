import { createI18n } from 'vue-i18n'
import en from './locales/en.json'
import ar from './locales/ar.json'

// اللغات المدعومة والاتجاه متاع كل وحدة
export const SUPPORTED_LOCALES = ['en', 'ar']
export const RTL_LOCALES = ['ar']
const STORAGE_KEY = 'app_locale'

// يقرا اللغة الابتدائية بالترتيب: localStorage -> cookie -> <html lang> -> افتراضي 'en'
function detectLocale() {
  const stored = localStorage.getItem(STORAGE_KEY)
  if (stored && SUPPORTED_LOCALES.includes(stored)) return stored

  const cookieMatch = document.cookie.match(/(?:^|;\s*)app_locale=([^;]+)/)
  if (cookieMatch && SUPPORTED_LOCALES.includes(cookieMatch[1])) return cookieMatch[1]

  const htmlLang = document.documentElement.getAttribute('lang')
  if (htmlLang && SUPPORTED_LOCALES.includes(htmlLang.slice(0, 2))) return htmlLang.slice(0, 2)

  return 'en'
}

export function isRtl(locale) {
  return RTL_LOCALES.includes(locale)
}

// يطبّق الاتجاه واللغة على <html>
export function applyDirection(locale) {
  const html = document.documentElement
  html.setAttribute('lang', locale)
  html.setAttribute('dir', isRtl(locale) ? 'rtl' : 'ltr')
}

const initialLocale = detectLocale()

export const i18n = createI18n({
  legacy: false,          // نستعملو Composition API ($t يخدم في القوالب عادي)
  globalInjection: true,  // باش $t متاح في كل الـcomponents بلا import
  locale: initialLocale,
  fallbackLocale: 'en',
  messages: { en, ar },
})

// يبدّل اللغة: يحدّث i18n + يحفظ (localStorage + cookie) + يقلب الاتجاه
export function setLocale(locale) {
  if (!SUPPORTED_LOCALES.includes(locale)) return

  i18n.global.locale.value = locale
  localStorage.setItem(STORAGE_KEY, locale)
  // cookie تدوم سنة، path=/ باش الـBackend يقراها في كل الطلبات
  document.cookie = `${STORAGE_KEY}=${locale};path=/;max-age=${60 * 60 * 24 * 365};SameSite=Lax`
  applyDirection(locale)
}

// نطبّقو الاتجاه أول ما يتحمّل الملف (قبل أي تفاعل)
applyDirection(initialLocale)
