import { createI18n } from 'vue-i18n';
import en from '../../lang/en.json';
import ar from '../../lang/ar.json';

const unflatten = (data) => {
  const result = {};
  for (const i in data) {
    const keys = i.split('.');
    keys.reduce((r, e, j) => {
      return r[e] || (r[e] = isNaN(Number(keys[j + 1])) ? (keys.length - 1 === j ? data[i] : {}) : []);
    }, result);
  }
  return result;
};

const i18n = createI18n({
  legacy: false,
  globalInjection: true,
  locale: localStorage.getItem('locale') || 'en',
  fallbackLocale: 'en',
  messages: {
    en: unflatten(en),
    ar: unflatten(ar)
  }
});

export default i18n;
