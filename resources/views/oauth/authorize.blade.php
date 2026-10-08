<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Otorisasi Akses — {{ config('app.name', 'Lawangsewu') }}</title>
    <link rel="icon" type="image/gif" href="/logo-pa.gif">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Figtree', system-ui, sans-serif;
            background: #0f172a;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            background-image:
                radial-gradient(ellipse at 20% 50%, rgba(59, 130, 246, 0.08) 0%, transparent 60%),
                radial-gradient(ellipse at 80% 20%, rgba(139, 92, 246, 0.06) 0%, transparent 60%);
        }

        .card {
            background: rgba(30, 41, 59, 0.9);
            border: 1px solid rgba(71, 85, 105, 0.5);
            border-radius: 1.25rem;
            padding: 2.5rem;
            max-width: 460px;
            width: 100%;
            box-shadow:
                0 25px 50px rgba(0, 0, 0, 0.5),
                0 0 0 1px rgba(255,255,255,0.03) inset;
            backdrop-filter: blur(16px);
        }

        .header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo-wrap {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 56px;
            height: 56px;
            border-radius: 14px;
            background: linear-gradient(135deg, #1d4ed8, #7c3aed);
            margin-bottom: 1rem;
            box-shadow: 0 8px 24px rgba(29, 78, 216, 0.35);
        }

        .logo-wrap img {
            width: 36px;
            height: 36px;
            object-fit: contain;
        }

        .header h1 {
            font-size: 1.35rem;
            font-weight: 700;
            color: #f1f5f9;
            letter-spacing: -0.02em;
        }

        .header p {
            font-size: 0.85rem;
            color: #64748b;
            margin-top: 0.35rem;
        }

        .divider {
            height: 1px;
            background: rgba(71, 85, 105, 0.4);
            margin: 1.5rem 0;
        }

        /* App info box */
        .app-box {
            display: flex;
            align-items: center;
            gap: 1rem;
            background: rgba(15, 23, 42, 0.5);
            border: 1px solid rgba(71, 85, 105, 0.4);
            border-radius: 0.875rem;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
        }

        .app-icon {
            width: 46px;
            height: 46px;
            border-radius: 10px;
            background: linear-gradient(135deg, #0ea5e9, #6366f1);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 1.4rem;
        }

        .app-info {
            flex: 1;
            min-width: 0;
        }

        .app-info .app-name {
            font-size: 1rem;
            font-weight: 600;
            color: #e2e8f0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .app-info .app-label {
            font-size: 0.75rem;
            color: #64748b;
            margin-top: 0.15rem;
        }

        /* Request detail */
        .request-label {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #475569;
            margin-bottom: 0.75rem;
        }

        .user-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(71, 85, 105, 0.4);
            border-radius: 999px;
            padding: 0.4rem 0.85rem 0.4rem 0.5rem;
            margin-bottom: 1.25rem;
        }

        .user-avatar {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: linear-gradient(135deg, #3b82f6, #8b5cf6);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            font-weight: 700;
            color: white;
            flex-shrink: 0;
        }

        .user-avatar img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }

        .user-pill .user-name {
            font-size: 0.82rem;
            color: #cbd5e1;
            font-weight: 500;
        }

        /* Scopes */
        .scopes-list {
            list-style: none;
            margin-bottom: 1.75rem;
        }

        .scopes-list li {
            display: flex;
            align-items: flex-start;
            gap: 0.65rem;
            padding: 0.6rem 0;
            border-bottom: 1px solid rgba(71, 85, 105, 0.2);
            font-size: 0.875rem;
            color: #94a3b8;
        }

        .scopes-list li:last-child {
            border-bottom: none;
        }

        .scope-icon {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            background: rgba(34, 197, 94, 0.15);
            border: 1px solid rgba(34, 197, 94, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-top: 1px;
        }

        .scope-icon svg {
            width: 10px;
            height: 10px;
            stroke: #4ade80;
            fill: none;
            stroke-width: 2.5;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .no-scope {
            font-size: 0.82rem;
            color: #475569;
            font-style: italic;
            padding: 0.5rem 0;
        }

        /* Buttons */
        .actions {
            display: flex;
            gap: 0.75rem;
        }

        .btn {
            flex: 1;
            padding: 0.75rem 1rem;
            border-radius: 0.75rem;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
        }

        .btn-approve {
            background: linear-gradient(135deg, #1d4ed8, #7c3aed);
            color: #fff;
            box-shadow: 0 4px 14px rgba(29, 78, 216, 0.35);
        }

        .btn-approve:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(29, 78, 216, 0.5);
            filter: brightness(1.1);
        }

        .btn-approve:active {
            transform: translateY(0);
        }

        .btn-deny {
            background: rgba(71, 85, 105, 0.2);
            border: 1px solid rgba(71, 85, 105, 0.4);
            color: #94a3b8;
        }

        .btn-deny:hover {
            background: rgba(239, 68, 68, 0.1);
            border-color: rgba(239, 68, 68, 0.35);
            color: #fca5a5;
        }

        .footer-note {
            text-align: center;
            font-size: 0.75rem;
            color: #334155;
            margin-top: 1.5rem;
        }

        .footer-note strong {
            color: #475569;
        }

        /* Animate in */
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .card {
            animation: slideUp 0.35s cubic-bezier(0.16, 1, 0.3, 1);
        }
    </style>
</head>
<body>
    <div class="card">
        <!-- Header -->
        <div class="header">
            <div class="logo-wrap">
                <img src="/logo-pa.gif" alt="Lawangsewu" onerror="this.style.display='none'; this.parentElement.innerHTML='⚖️';">
            </div>
            <h1>Izin Akses Diperlukan</h1>
            <p>{{ config('app.name', 'Lawangsewu') }} · SSO Portal</p>
        </div>

        <div class="divider"></div>

        <!-- Aplikasi yang meminta -->
        <p class="request-label">Aplikasi yang meminta akses</p>
        <div class="app-box">
            <div class="app-icon">🔌</div>
            <div class="app-info">
                <div class="app-name">{{ $client->name }}</div>
                <div class="app-label">Meminta akses ke akun Lawangsewu Anda</div>
            </div>
        </div>

        <!-- User yang sedang login -->
        <p class="request-label">Akun yang akan diotorisasi</p>
        <div class="user-pill">
            <div class="user-avatar">
                @if($user->avatar ?? null)
                    <img src="{{ $user->avatar }}" alt="{{ $user->name }}"
                         onerror="this.style.display='none'; this.parentElement.textContent='{{ strtoupper(substr($user->name ?? $user->email, 0, 1)) }}';">
                @else
                    {{ strtoupper(substr($user->name ?? $user->email, 0, 1)) }}
                @endif
            </div>
            <span class="user-name">{{ $user->name ?? $user->email }}</span>
        </div>

        <!-- Scopes -->
        <p class="request-label">Izin yang diminta</p>
        @if(count($scopes) > 0)
            <ul class="scopes-list">
                @foreach($scopes as $scope)
                    <li>
                        <span class="scope-icon">
                            <svg viewBox="0 0 12 12"><polyline points="2,6 5,9 10,3"/></svg>
                        </span>
                        <span>{{ $scope->description ?? $scope->id }}</span>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="no-scope">Akses dasar profil (tanpa izin khusus).</p>
        @endif

        <!-- Tombol aksi -->
        <div class="actions">
            <!-- Tombol TOLAK -->
            <form method="post" action="{{ route('passport.authorizations.deny') }}" style="flex:1; display:contents;">
                @csrf
                @method('DELETE')
                <input type="hidden" name="auth_token" value="{{ $authToken }}">
                <button type="submit" class="btn btn-deny" id="btn-deny-access">
                    <span>✕</span> Tolak
                </button>
            </form>

            <!-- Tombol SETUJU -->
            <form method="post" action="{{ route('passport.authorizations.approve') }}" style="flex:1; display:contents;">
                @csrf
                <input type="hidden" name="auth_token" value="{{ $authToken }}">
                <button type="submit" class="btn btn-approve" id="btn-approve-access">
                    <span>✓</span> Izinkan
                </button>
            </form>
        </div>

        <p class="footer-note">
            Dengan mengklik <strong>Izinkan</strong>, Anda memberi akses aplikasi tersebut
            sesuai izin di atas. Anda dapat mencabut akses kapan saja melalui pengaturan akun.
        </p>
    </div>
</body>
</html>
