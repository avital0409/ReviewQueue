import { mount } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import axios from 'axios';
import RejectionEmailModal from '../components/RejectionEmailModal.vue';

// Mock axios
vi.mock('axios');

describe('RejectionEmailModal.vue', () => {
  const mockItem = {
    id: 42,
    author_email: 'spammer@violation.com',
    content: 'Violating spam text content.'
  };

  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders standard rejection notifications when isBan is false', () => {
    const wrapper = mount(RejectionEmailModal, {
      props: {
        isOpen: true,
        item: mockItem,
        reviewerNote: 'Policy breach.',
        isBan: false
      }
    });

    expect(wrapper.find('h3').text()).toBe('Rejection Notification Email');
    expect(wrapper.text()).toContain('spammer@violation.com');
    expect(wrapper.text()).toContain('Submission Rejection Notice - ReviewQueue');
  });

  it('renders suspension notices correctly when isBan is true', () => {
    const wrapper = mount(RejectionEmailModal, {
      props: {
        isOpen: true,
        item: mockItem,
        reviewerNote: 'Repeat offender.',
        isBan: true
      }
    });

    expect(wrapper.find('h3').text()).toBe('Account Suspension & Ban Notice');
    expect(wrapper.text()).toContain('spammer@violation.com');
    expect(wrapper.text()).toContain('Account Suspension & Permanent Ban Notice - ReviewQueue');
  });

  it('calls close event emit when clicking the exit button', async () => {
    const wrapper = mount(RejectionEmailModal, {
      props: {
        isOpen: true,
        item: mockItem,
        reviewerNote: 'Reason',
        isBan: false
      }
    });

    const exitBtn = wrapper.find('button[class*="text-slate-400"]');
    await exitBtn.trigger('click');

    expect(wrapper.emitted()).toHaveProperty('close');
  });

  it('makes API request to fetch draft upon opening and sets textarea content', async () => {
    const mockResponse = { data: { draft: 'AI generated draft response' } };
    axios.post.mockResolvedValueOnce(mockResponse);

    const wrapper = mount(RejectionEmailModal, {
      props: {
        isOpen: false,
        item: mockItem,
        reviewerNote: 'Breach of rule 4',
        isBan: false
      }
    });

    // Toggle open state
    await wrapper.setProps({ isOpen: true });

    expect(axios.post).toHaveBeenCalledWith('/api/items/42/rejection-draft', {
      reviewer_note: 'Breach of rule 4',
      is_ban: false
    });

    await wrapper.vm.$nextTick();
    await new Promise((resolve) => setTimeout(resolve, 0));

    expect(wrapper.find('textarea').element.value).toBe('AI generated draft response');
  });
});
