import { mount } from '@vue/test-utils';
import { nextTick } from 'vue';
import { describe, expect, test, vi } from 'vitest';
import Cctv from './Cctv.vue';

vi.mock('@inertiajs/vue3', () => ({
    Head: {
        name: 'Head',
        template: '<div />',
    },
}));

const cameras = [
    {
        key: 'cam-1',
        name: 'PTSP Lobby',
        zone: 'Pelayanan',
        iframeSrc: 'https://example.test/cam-1',
        status: 'LIVE',
        updatedAt: '10:00',
        signal: 'Stabil',
        resolution: '1080p',
    },
    {
        key: 'cam-2',
        name: 'Koridor Pelayanan',
        zone: 'Pelayanan',
        iframeSrc: 'https://example.test/cam-2',
        status: 'LIVE',
        updatedAt: '10:01',
        signal: 'Stabil',
        resolution: '1080p',
    },
    {
        key: 'cam-3',
        name: 'Pintu Belakang',
        zone: 'Keamanan',
        iframeSrc: 'https://example.test/cam-3',
        status: 'LIVE',
        updatedAt: '10:02',
        signal: 'Stabil',
        resolution: '1080p',
    },
];

function mountCctv() {
    const storage = {
        getItem: vi.fn(() => null),
        setItem: vi.fn(),
        removeItem: vi.fn(),
        clear: vi.fn(),
    };

    Object.defineProperty(window, 'localStorage', {
        value: storage,
        configurable: true,
    });

    return mount(Cctv, {
        props: {
            appMeta: { status: 'Operasional' },
            navGroups: [],
            cameras,
            networkSummary: {
                locationCount: 2,
                status: 'Jaringan stabil',
                latency: '1.2 detik',
            },
        },
        global: {
            stubs: {
                LawangsewuLayout: {
                    template: '<div><slot /></div>',
                },
                transition: false,
            },
        },
    });
}

describe('Cctv.vue interactions', () => {
    test('filters camera tiles by zone', async () => {
        const wrapper = mountCctv();

        expect(wrapper.findAll('[data-testid="camera-tile"]').length).toBe(3);

        await wrapper.get('[data-testid="zone-filter-Pelayanan"]').trigger('click');
        await nextTick();

        expect(wrapper.findAll('[data-testid="camera-tile"]').length).toBe(2);

        await wrapper.get('[data-testid="zone-filter-Keamanan"]').trigger('click');
        await nextTick();

        expect(wrapper.findAll('[data-testid="camera-tile"]').length).toBe(1);
    });

    test('opens expanded camera modal from a tile click', async () => {
        const wrapper = mountCctv();

        expect(wrapper.find('[data-testid="expanded-camera"]').exists()).toBe(false);

        await wrapper.get('[data-testid="camera-tile"]').trigger('click');
        await nextTick();

        expect(wrapper.find('[data-testid="expanded-camera"]').exists()).toBe(true);
    });
});
