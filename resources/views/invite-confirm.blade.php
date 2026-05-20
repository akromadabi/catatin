<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Konfirmasi Undangan – Catat-in</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100dvh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            background: linear-gradient(135deg, #f0f0ff 0%, #e8f4ff 50%, #f5f0ff 100%);
            position: relative;
            overflow: hidden;
        }

        /* Decorative blobs */
        body::before {
            content: '';
            position: fixed;
            top: -120px; left: -80px;
            width: 400px; height: 400px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(108,99,255,.15) 0%, transparent 70%);
            pointer-events: none;
        }
        body::after {
            content: '';
            position: fixed;
            bottom: -80px; right: -60px;
            width: 350px; height: 350px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(99,172,255,.12) 0%, transparent 70%);
            pointer-events: none;
        }

        .card {
            background: rgba(255,255,255,.96);
            backdrop-filter: blur(16px);
            border-radius: 28px;
            padding: 2.25rem 2rem;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 20px 60px rgba(108,99,255,.12), 0 2px 8px rgba(0,0,0,.06);
            text-align: center;
            animation: slideUp .4s cubic-bezier(.16,1,.3,1);
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* App logo */
        .app-logo {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            margin-bottom: 1.75rem;
            font-weight: 800;
            font-size: 1.1rem;
            color: #6c63ff;
        }
        .app-logo span.dot { color: #3b82f6; }

        /* Invite illustration */
        .invite-icon {
            width: 72px; height: 72px;
            border-radius: 20px;
            background: linear-gradient(135deg, #6c63ff, #a78bfa);
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.25rem;
            font-size: 2rem;
            color: #fff;
            box-shadow: 0 8px 24px rgba(108,99,255,.35);
        }

        h1 {
            font-size: 1.35rem;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: .5rem;
            line-height: 1.3;
        }

        .subtitle {
            font-size: .875rem;
            color: #64748b;
            margin-bottom: 1.75rem;
            line-height: 1.6;
        }

        /* Project card */
        .project-card {
            background: linear-gradient(135deg, #6c63ff15, #a78bfa10);
            border: 1.5px solid #6c63ff25;
            border-radius: 18px;
            padding: 1.1rem 1.25rem;
            margin-bottom: 1.75rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            text-align: left;
        }
        .project-icon {
            width: 48px; height: 48px; min-width: 48px;
            border-radius: 14px;
            background: linear-gradient(135deg, #6c63ff, #a78bfa);
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 1.2rem;
        }
        .project-info .label { font-size: .65rem; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .06em; }
        .project-info .name  { font-size: 1rem; font-weight: 800; color: #0f172a; margin-top: .15rem; }
        .project-info .meta  { font-size: .75rem; color: #64748b; margin-top: .2rem; }

        /* Owner chip */
        .owner-chip {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            background: #f1f5f9;
            border-radius: 999px;
            padding: .35rem .85rem .35rem .4rem;
            margin-bottom: 1.75rem;
            font-size: .8rem;
            color: #475569;
            font-weight: 600;
        }
        .owner-avatar {
            width: 28px; height: 28px;
            border-radius: 50%;
            background: linear-gradient(135deg, #6c63ff, #3b82f6);
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: .7rem; font-weight: 700;
        }

        /* Buttons */
        .btn-row { display: flex; gap: .75rem; }
        .btn {
            flex: 1;
            padding: .875rem 1rem;
            border-radius: 14px;
            font-size: .9rem;
            font-weight: 700;
            border: none;
            cursor: pointer;
            transition: all .2s;
            font-family: inherit;
        }
        .btn-decline {
            background: #f1f5f9;
            color: #64748b;
        }
        .btn-decline:hover { background: #e2e8f0; }
        .btn-accept {
            background: linear-gradient(135deg, #6c63ff, #a78bfa);
            color: #fff;
            box-shadow: 0 6px 20px rgba(108,99,255,.35);
        }
        .btn-accept:hover { transform: translateY(-1px); box-shadow: 0 10px 28px rgba(108,99,255,.45); }
        .btn-accept:active { transform: translateY(0); }

        /* Flash messages */
        .alert {
            padding: .85rem 1.1rem;
            border-radius: 14px;
            margin-bottom: 1.25rem;
            font-size: .85rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: .6rem;
        }
        .alert-error   { background: #fff1f2; color: #e11d48; border: 1px solid #fecdd3; }
        .alert-success { background: #f0fdf4; color: #16a34a; border: 1px solid #bbf7d0; }
        .alert-info    { background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; }

        .footer-note {
            margin-top: 1.5rem;
            font-size: .7rem;
            color: #94a3b8;
        }
    </style>
</head>
<body>

<div class="card">
    <!-- App logo -->
    <div class="app-logo">
        <i class="fas fa-book-open"></i>
        Catat<span class="dot">.</span>in
    </div>

    @if(session('error'))
    <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> {{ session('error') }}</div>
    @endif
    @if(session('success'))
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>
    @endif

    <!-- Invite icon -->
    <div class="invite-icon">
        <i class="fas fa-user-plus"></i>
    </div>

    <h1>Kamu Diundang!</h1>
    <p class="subtitle">
        Kamu mendapat undangan untuk bergabung dan berkolaborasi dalam sebuah proyek.
    </p>

    <!-- Who invited -->
    <div class="owner-chip">
        <div class="owner-avatar">{{ strtoupper(substr($ownerName, 0, 1)) }}</div>
        Diundang oleh <strong>{{ $ownerName }}</strong>
    </div>

    <!-- Project card -->
    <div class="project-card">
        <div class="project-icon">
            <i class="{{ $project->icon ?? 'fas fa-folder' }}"></i>
        </div>
        <div class="project-info">
            <div class="label">Proyek</div>
            <div class="name">{{ $project->name }}</div>
            <div class="meta"><i class="fas fa-users" style="font-size:.65rem;"></i> Kolaborasi bersama</div>
        </div>
    </div>

    <!-- Action buttons -->
    <div class="btn-row">
        <form method="POST" action="/invite/{{ $token }}/decline" style="flex:1">
            @csrf
            <button type="submit" class="btn btn-decline" style="width:100%">
                <i class="fas fa-times" style="margin-right:.4rem"></i>Tolak
            </button>
        </form>
        <form method="POST" action="/invite/{{ $token }}/confirm" style="flex:1">
            @csrf
            <button type="submit" class="btn btn-accept" style="width:100%">
                <i class="fas fa-check" style="margin-right:.4rem"></i>Bergabung
            </button>
        </form>
    </div>

    <p class="footer-note">
        Dengan bergabung, kamu bisa melihat dan mengedit data proyek bersama anggota lain.
    </p>
</div>

</body>
</html>
