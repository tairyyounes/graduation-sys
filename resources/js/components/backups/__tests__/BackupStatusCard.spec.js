import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import BackupStatusCard from '../BackupStatusCard.vue'

describe('BackupStatusCard.vue', () => {
  const globalMocks = {
    mocks: {
      $t: (key) => key,
    },
  }

  it('displays warning badge when last backup is older than 25 hours', () => {
    const summaryOverdue = {
      total_backups: 5,
      total_size_bytes: 52428800,
      formatted_total_size: '50 MB',
      last_backup_date: '2026-10-13T10:00:00Z',
      last_backup_relative: '2 days ago',
      last_backup_age_hours: 48,
      is_older_than_25_hours: true,
      selected_disk: 'backups',
    }

    const wrapper = mount(BackupStatusCard, {
      props: {
        statusSummary: summaryOverdue,
      },
      global: globalMocks,
    })

    expect(wrapper.text()).toContain('backups.card.warning')
    expect(wrapper.text()).toContain('50 MB')
    expect(wrapper.text()).toContain('5')
  })

  it('displays healthy badge when last backup is fresh (< 25 hours)', () => {
    const summaryFresh = {
      total_backups: 1,
      total_size_bytes: 10485760,
      formatted_total_size: '10 MB',
      last_backup_date: '2026-10-15T10:00:00Z',
      last_backup_relative: '2 hours ago',
      last_backup_age_hours: 2,
      is_older_than_25_hours: false,
      selected_disk: 'backups',
    }

    const wrapper = mount(BackupStatusCard, {
      props: {
        statusSummary: summaryFresh,
      },
      global: globalMocks,
    })

    expect(wrapper.text()).toContain('backups.card.healthy')
    expect(wrapper.text()).toContain('10 MB')
  })

  it('renders active job indicator when an active job is provided', () => {
    const wrapper = mount(BackupStatusCard, {
      props: {
        statusSummary: {
          total_backups: 0,
          total_size_bytes: 0,
          formatted_total_size: '0 B',
          last_backup_date: null,
          last_backup_relative: null,
          last_backup_age_hours: null,
          is_older_than_25_hours: true,
          selected_disk: 'backups',
        },
        activeJob: {
          id: 42,
          type: 'backup',
          status: 'running',
        },
      },
      global: globalMocks,
    })

    expect(wrapper.text()).toContain('Job #42')
    expect(wrapper.text()).toContain('backup')
  })
})
