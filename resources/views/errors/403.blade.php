<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport"
        content="width=device-width, initial-scale=1.0">
  <title>Akses Ditolak — 126 Club</title>
  <style>
    *,
    *::before,
    *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: ui-sans-serif, system-ui, -apple-system, sans-serif;
      background: #0f172a;
      color: #e2e8f0;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .card {
      background: #1e293b;
      border: 1px solid #334155;
      border-radius: 1rem;
      padding: 3rem 2.5rem;
      max-width: 420px;
      width: 90%;
      text-align: center;
    }

    .icon {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 5rem;
      height: 5rem;
      background: rgba(239, 68, 68, 0.15);
      border-radius: 50%;
      margin-bottom: 1.5rem;
    }

    .icon svg {
      width: 2.5rem;
      height: 2.5rem;
      color: #ef4444;
    }

    h1 {
      font-size: 1.5rem;
      font-weight: 700;
      margin-bottom: 0.5rem;
    }

    .code {
      font-size: 3.5rem;
      font-weight: 800;
      color: #ef4444;
      line-height: 1;
      margin-bottom: 0.5rem;
    }

    p {
      color: #94a3b8;
      line-height: 1.6;
      margin-bottom: 1.5rem;
      font-size: 0.9rem;
    }

    .role-badge {
      display: inline-block;
      background: #334155;
      color: #cbd5e1;
      padding: 0.25rem 0.75rem;
      border-radius: 9999px;
      font-size: 0.8rem;
      margin-bottom: 2rem;
    }

    .btn {
      display: inline-block;
      background: #3b82f6;
      color: #fff;
      padding: 0.625rem 1.5rem;
      border-radius: 0.5rem;
      text-decoration: none;
      font-size: 0.875rem;
      font-weight: 500;
      transition: background 0.15s;
    }

    .btn:hover {
      background: #2563eb;
    }
  </style>
</head>

<body>
  <div class="card">
    <div class="icon">
      <svg fill="none"
           stroke="currentColor"
           viewBox="0 0 24 24">
        <path stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
      </svg>
    </div>

    <div class="code">403</div>
    <h1>Akses Ditolak</h1>

    @auth
      <div class="role-badge">
        {{ auth()->user()->getRoleNames()->first() ?? 'Tanpa Role' }}
      </div>
    @endauth

    <p>Anda tidak memiliki izin untuk mengakses halaman ini.<br>Hubungi administrator jika Anda merasa ini adalah kesalahan.</p>

    <a href="{{ url()->previous('#') !== '#' && url()->previous() !== url()->current() ? url()->previous() : route('admin.dashboard') }}"
       class="btn">
      &larr; Kembali
    </a>
  </div>
</body>

</html>
