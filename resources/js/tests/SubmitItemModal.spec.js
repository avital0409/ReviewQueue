import { mount } from '@vue/test-utils';
import { describe, it, expect, vi, beforeEach } from 'vitest';
import axios from 'axios';
import SubmitItemModal from '../components/SubmitItemModal.vue';

// Mock axios
vi.mock('axios');

describe('SubmitItemModal.vue', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders modal content correctly when isOpen is true', () => {
    const wrapper = mount(SubmitItemModal, {
      props: {
        isOpen: true
      }
    });

    expect(wrapper.find('h3').text()).toContain('Submit Content for Review');
    expect(wrapper.find('input[type="email"]').exists()).toBe(true);
    expect(wrapper.find('textarea').exists()).toBe(true);
  });

  it('does not render modal when isOpen is false', () => {
    const wrapper = mount(SubmitItemModal, {
      props: {
        isOpen: false
      }
    });

    expect(wrapper.find('h3').exists()).toBe(false);
  });

  it('calls closeModal and emits close when clicking close button', async () => {
    const wrapper = mount(SubmitItemModal, {
      props: {
        isOpen: true
      }
    });

    const closeBtn = wrapper.find('button[class*="text-slate-400"]');
    await closeBtn.trigger('click');

    expect(wrapper.emitted()).toHaveProperty('close');
  });

  it('submits form correctly and emits submitted event', async () => {
    const mockResponse = { data: { id: 100, author_email: 'new@example.com', content: 'Fresh test body' } };
    let capturedPayload;
    axios.post.mockImplementationOnce((url, payload) => {
      capturedPayload = { ...payload };
      return Promise.resolve(mockResponse);
    });

    const wrapper = mount(SubmitItemModal, {
      props: {
        isOpen: true
      }
    });

    // Populate inputs
    const emailInput = wrapper.find('input[type="email"]');
    await emailInput.setValue('new@example.com');

    const contentTextarea = wrapper.find('textarea');
    await contentTextarea.setValue('Fresh test body');

    // Trigger form submit
    await wrapper.find('form').trigger('submit.prevent');

    expect(axios.post).toHaveBeenCalled();
    expect(capturedPayload).toEqual({
      author_email: 'new@example.com',
      content: 'Fresh test body'
    });
    
    // Wait for promise tick
    await wrapper.vm.$nextTick();
    await new Promise((resolve) => setTimeout(resolve, 0));

    expect(wrapper.emitted()).toHaveProperty('submitted');
    expect(wrapper.emitted('submitted')[0][0]).toEqual(mockResponse.data);
  });
});
