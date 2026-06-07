<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CctvCamera;
use App\Support\LawangsewuPortal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CctvCameraController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Admin/CctvManager', [
            'appMeta' => LawangsewuPortal::appMeta(),
            'navGroups' => LawangsewuPortal::navGroups(),
            'cameras' => CctvCamera::query()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->map(fn (CctvCamera $camera) => [
                    'id' => $camera->id,
                    'key' => $camera->key,
                    'name' => $camera->name,
                    'zone' => $camera->zone,
                    'iframe_src' => $camera->iframe_src,
                    'primary_sd_src' => $camera->primary_sd_src,
                    'primary_hd_src' => $camera->primary_hd_src,
                    'fallback_src' => $camera->fallback_src,
                    'stream_provider' => $camera->stream_provider,
                    'sort_order' => $camera->sort_order,
                    'is_active' => $camera->is_active,
                    'is_featured' => $camera->is_featured,
                    'updated_at' => optional($camera->updated_at)?->setTimezone('Asia/Jakarta')->format('d M Y H:i') . ' WIB',
                ])
                ->values(),
            'status' => $request->session()->get('status'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'is_active' => $request->has('is_active')
                ? $request->boolean('is_active')
                : true,
            'is_featured' => $request->has('is_featured')
                ? $request->boolean('is_featured')
                : false,
        ]);

        $payload = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'zone' => ['nullable', 'string', 'max:255'],
            'iframe_src' => ['required', 'string', 'max:2000', 'starts_with:https://,http://'],
            'primary_sd_src' => ['nullable', 'string', 'max:2000', 'starts_with:https://,http://'],
            'primary_hd_src' => ['nullable', 'string', 'max:2000', 'starts_with:https://,http://'],
            'fallback_src' => ['nullable', 'string', 'max:2000', 'starts_with:https://,http://'],
            'stream_provider' => ['nullable', 'string', 'max:40'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['required', 'boolean'],
            'is_featured' => ['required', 'boolean'],
        ]);

        CctvCamera::query()->create([
            'key' => $this->generateKey($payload['name']),
            'name' => $payload['name'],
            'zone' => $payload['zone'] ?? null,
            'iframe_src' => $payload['iframe_src'],
            'primary_sd_src' => $payload['primary_sd_src'] ?? null,
            'primary_hd_src' => $payload['primary_hd_src'] ?? null,
            'fallback_src' => $payload['fallback_src'] ?? $payload['iframe_src'],
            'stream_provider' => $payload['stream_provider'] ?? 'custom',
            'sort_order' => $payload['sort_order'] ?? 0,
            'is_active' => $payload['is_active'],
            'is_featured' => $payload['is_featured'],
        ]);

        return back()->with('status', 'Kamera CCTV baru berhasil ditambahkan.');
    }

    public function update(Request $request, CctvCamera $camera): RedirectResponse
    {
        $request->merge([
            'is_active' => $request->has('is_active')
                ? $request->boolean('is_active')
                : false,
            'is_featured' => $request->has('is_featured')
                ? $request->boolean('is_featured')
                : false,
        ]);

        $payload = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'zone' => ['nullable', 'string', 'max:255'],
            'iframe_src' => ['required', 'string', 'max:2000', 'starts_with:https://,http://'],
            'primary_sd_src' => ['nullable', 'string', 'max:2000', 'starts_with:https://,http://'],
            'primary_hd_src' => ['nullable', 'string', 'max:2000', 'starts_with:https://,http://'],
            'fallback_src' => ['nullable', 'string', 'max:2000', 'starts_with:https://,http://'],
            'stream_provider' => ['nullable', 'string', 'max:40'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['required', 'boolean'],
            'is_featured' => ['required', 'boolean'],
        ]);

        $payload['fallback_src'] = $payload['fallback_src'] ?? $payload['iframe_src'];
        $payload['stream_provider'] = $payload['stream_provider'] ?? $camera->stream_provider ?? 'custom';

        $camera->update($payload);

        return back()->with('status', 'Konfigurasi CCTV berhasil diperbarui.');
    }

    private function generateKey(string $name): string
    {
        $base = Str::slug($name);

        if ($base === '') {
            $base = 'camera';
        }

        $key = $base;
        $suffix = 2;

        while (CctvCamera::query()->where('key', $key)->exists()) {
            $key = sprintf('%s-%d', $base, $suffix);
            $suffix++;
        }

        return $key;
    }
}
