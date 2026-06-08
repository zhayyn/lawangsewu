<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Akses Ditolak</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script type="module" src="https://unpkg.com/@splinetool/viewer@1.0.94/build/spline-viewer.js"></script>
    <style>
        body {
            background-color: #030712; /* Tailwind gray-950 */
            color: #f3f4f6;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            overflow: hidden;
        }
        .spline-container {
            position: absolute;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            z-index: 1;
            opacity: 0.85;
            pointer-events: auto;
        }
        .content-overlay {
            position: relative;
            z-index: 10;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            text-align: center;
            background: radial-gradient(circle at center, rgba(3,7,18,0.2) 0%, rgba(3,7,18,0.8) 100%);
            padding: 2rem;
            pointer-events: none;
        }
        .glass-panel {
            background: rgba(17, 24, 39, 0.4);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1.5rem;
            padding: 3rem;
            max-width: 32rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5), 0 0 40px rgba(56, 189, 248, 0.1);
            pointer-events: auto;
            transform: translateY(20px);
            animation: float-in 0.8s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
        }
        .glass-panel:hover {
            border-color: rgba(56, 189, 248, 0.3);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6), 0 0 60px rgba(56, 189, 248, 0.15);
            transition: all 0.4s ease;
        }
        .error-code {
            font-size: 6rem;
            font-weight: 900;
            line-height: 1;
            background: linear-gradient(135deg, #38bdf8 0%, #3b82f6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
            filter: drop-shadow(0 0 20px rgba(56, 189, 248, 0.4));
        }
        .btn-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.875rem 2rem;
            background: linear-gradient(135deg, #0284c7 0%, #2563eb 100%);
            color: white;
            font-weight: 600;
            font-size: 0.875rem;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            border-radius: 9999px;
            transition: all 0.3s ease;
            text-decoration: none;
            box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.3);
            border: 1px solid rgba(255,255,255,0.1);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(37, 99, 235, 0.4), 0 0 20px rgba(56, 189, 248, 0.4);
            background: linear-gradient(135deg, #0ea5e9 0%, #3b82f6 100%);
        }
        @keyframes float-in {
            0% { opacity: 0; transform: translateY(40px); }
            100% { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <!-- Background 3D Spline Viewer -->
    <!-- Lightweight abstract 3D object from Spline Community -->
    <div class="spline-container">
        <spline-viewer url="https://prod.spline.design/6Wq1Q7YGyM-iab9i/scene.splinecode"></spline-viewer>
    </div>

    <!-- Foreground Content -->
    <div class="content-overlay">
        <div class="glass-panel">
            <div class="mb-4 inline-flex items-center justify-center rounded-full bg-red-500/10 p-4 ring-1 ring-red-500/20">
                <svg class="h-8 w-8 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>
            
            <h1 class="error-code">403</h1>
            <h2 class="text-2xl font-bold tracking-tight text-white mb-2">Akses Ditolak</h2>
            
            <p class="text-gray-400 text-sm leading-relaxed mb-8">
                {{ $exception->getMessage() ?: 'Fitur ini tidak diaktifkan untuk akun Anda. Silakan hubungi Administrator sistem jika Anda membutuhkan akses.' }}
            </p>

            <a href="{{ url('/') }}" class="btn-primary group">
                <svg class="w-4 h-4 mr-2 transition-transform group-hover:-translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Kembali ke Dashboard
            </a>
        </div>
        
        <div class="absolute bottom-8 text-xs font-semibold uppercase tracking-[0.2em] text-gray-600">
            Lawangsewu Security System
        </div>
    </div>
</body>
</html>
