<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class OAuth2AdminController extends Controller
{
    protected $clients;

    public function __construct(ClientRepository $clients)
    {
        $this->clients = $clients;
    }

    /**
     * Display a listing of the OAuth2 clients.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('Admin/OAuth2Clients', [
            'clients' => Client::query()
                ->orderBy('name')
                ->get()
                ->makeVisible('secret'), // Secrets are usually hidden by default in the model
            'status' => $request->session()->get('status'),
            'last_client' => $request->session()->get('last_client'),
        ]);
    }

    /**
     * Store a newly created client in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $payload = $request->validate([
            'name' => 'required|max:255',
            'redirect' => 'required|url',
        ]);

        $client = $this->clients->createAuthorizationCodeGrantClient(
            $payload['name'],
            [$payload['redirect']], // Passport v13 expects array of redirect URIs
            true // confidential
        );

        return back()->with([
            'status' => 'OAuth2 Client "' . $client->name . '" berhasil dibuat.',
            'last_client' => [
                'id' => $client->id,
                'secret' => $client->secret, // In standard setup, secret is returned in the model
                'name' => $client->name
            ]
        ]);
    }

    /**
     * Remove the specified client from storage.
     */
    public function destroy(Client $client): RedirectResponse
    {
        $client->delete();

        return back()->with('status', 'OAuth2 Client berhasil dihapus.');
    }
}
