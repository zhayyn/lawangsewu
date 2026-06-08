import { mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, test, vi } from 'vitest';
import Login from './Login.vue';

vi.mock('@inertiajs/vue3', () => ({
    Head: {
        name: 'Head',
        template: '<div />',
    },
    Link: {
        name: 'Link',
        props: ['href'],
        template: '<a :href="href"><slot /></a>',
    },
    useForm: () => ({
        email: '',
        password: '',
        remember: false,
        processing: false,
        errors: {},
        post: vi.fn(),
        reset: vi.fn(),
    }),
}));

const baseProps = {
    canResetPassword: true,
    canRegister: true,
    canGoogleAuth: true,
    googleClientId: '',
    status: '',
    error: '',
};

function mountLogin(props = {}) {
    return mount(Login, {
        props: {
            ...baseProps,
            ...props,
        },
        global: {
            mocks: {
                route: globalThis.route,
            },
            stubs: {
                GuestLayout: { template: '<div><slot /></div>' },
                Checkbox: { template: '<input type="checkbox" />' },
                ThemeToggle: { template: '<button type="button">theme</button>' },
                InputError: { props: ['message'], template: '<div>{{ message }}</div>' },
                InputLabel: { props: ['value', 'for'], template: '<label>{{ value }}</label>' },
                PrimaryButton: { template: '<button type="submit"><slot /></button>' },
                TextInput: {
                    props: ['modelValue', 'type', 'id', 'required', 'autocomplete', 'autofocus', 'placeholder'],
                    emits: ['update:modelValue'],
                    template: '<input :id="id" :type="type" :value="modelValue" :required="required" :autocomplete="autocomplete" :autofocus="autofocus" :placeholder="placeholder" @input="$emit(\'update:modelValue\', $event.target.value)" />',
                },
            },
        },
    });
}

describe('Login.vue interactions', () => {
    beforeEach(() => {
        vi.useFakeTimers();

        globalThis.route = vi.fn((name) => `/${name}`);

        Object.defineProperty(window, 'axios', {
            value: {
                post: vi.fn(),
            },
            configurable: true,
        });

        Object.defineProperty(window.navigator, 'userAgent', {
            value: 'Mozilla/5.0 (iPad; CPU OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
            configurable: true,
        });

        Object.defineProperty(window.navigator, 'platform', {
            value: 'MacIntel',
            configurable: true,
        });

        Object.defineProperty(window.navigator, 'maxTouchPoints', {
            value: 5,
            configurable: true,
        });
    });

    afterEach(() => {
        vi.clearAllTimers();
        vi.useRealTimers();
        vi.restoreAllMocks();
    });

    test('reveals password for 3 seconds after eye button click', async () => {
        const wrapper = mountLogin({ canGoogleAuth: false });

        const passwordInput = wrapper.get('[data-testid="password-input"]');
        expect(passwordInput.attributes('type')).toBe('password');

        await wrapper.get('[data-testid="password-visibility-toggle"]').trigger('click');
        expect(wrapper.get('[data-testid="password-input"]').attributes('type')).toBe('text');

        vi.advanceTimersByTime(3000);
        await wrapper.vm.$nextTick();

        expect(wrapper.get('[data-testid="password-input"]').attributes('type')).toBe('password');
    });

    test('does not force touch devices into redirect mode copy', async () => {
        const wrapper = mountLogin();

        expect(wrapper.text()).not.toContain('Browser ini memakai alur Google yang paling stabil untuk perangkat sentuh.');
    });
});
