import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import RestoreModal from '../RestoreModal.vue'

describe('RestoreModal.vue', () => {
  const defaultProps = {
    isOpen: true,
    backupItem: {
      file_name: 'test-backup.zip',
      disk: 'backups',
      formatted_size: '12.5 MB',
    },
    loading: false,
  }

  const globalMocks = {
    mocks: {
      $t: (key) => key,
    },
  }

  it('renders correctly when open', () => {
    const wrapper = mount(RestoreModal, {
      props: defaultProps,
      global: globalMocks,
    })

    expect(wrapper.text()).toContain('test-backup.zip')
    expect(wrapper.text()).toContain('12.5 MB')
  })

  it('keeps the restore submit button disabled until RESTORE is typed and password is provided', async () => {
    const wrapper = mount(RestoreModal, {
      props: defaultProps,
      global: globalMocks,
    })

    const submitBtn = wrapper.find('button[type="submit"]')
    expect(submitBtn.attributes('disabled')).toBeDefined()

    // Enter only password
    const passwordInput = wrapper.find('input[type="password"]')
    await passwordInput.setValue('secret123')
    expect(submitBtn.attributes('disabled')).toBeDefined()

    // Enter incorrect phrase
    const phraseInput = wrapper.find('input[placeholder="RESTORE"]')
    await phraseInput.setValue('restore') // lowercase
    expect(submitBtn.attributes('disabled')).toBeDefined()

    // Enter exact uppercase RESTORE
    await phraseInput.setValue('RESTORE')
    expect(submitBtn.attributes('disabled')).toBeUndefined()
  })

  it('emits confirm event with backup item, password, and phrase on submit', async () => {
    const wrapper = mount(RestoreModal, {
      props: defaultProps,
      global: globalMocks,
    })

    const passwordInput = wrapper.find('input[type="password"]')
    const phraseInput = wrapper.find('input[placeholder="RESTORE"]')

    await passwordInput.setValue('admin-pass')
    await phraseInput.setValue('RESTORE')

    await wrapper.find('form').trigger('submit.prevent')

    expect(wrapper.emitted('confirm')).toBeTruthy()
    expect(wrapper.emitted('confirm')[0][0]).toEqual({
      backupItem: defaultProps.backupItem,
      password: 'admin-pass',
      phrase: 'RESTORE',
    })
  })
})
