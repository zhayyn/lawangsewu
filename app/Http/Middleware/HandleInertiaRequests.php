<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
                'isSuperAdmin' => $request->user()
                    ? $request->user()->isSuperAdmin()
                    : false,
            ],
            'status' => $request->session()->get('status'),
            'error' => $request->session()->get('error'),
            'flash' => array_merge(
                (array) $request->session()->get('flash', []),
                array_filter([
                    'success' => $request->session()->get('success'),
                    'error' => $request->session()->get('error'),
                ]),
            ),
            'login_success' => $request->session()->get('login_success'),
        ];
    }
}
