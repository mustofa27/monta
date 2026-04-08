<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SSO Dashboard</title>
    <style>
        body {
            margin: 0;
            font-family: "IBM Plex Sans", sans-serif;
            background: linear-gradient(140deg, #f6f4ed 0%, #ebe6d8 45%, #d9e6f4 100%);
            color: #1f2937;
            min-height: 100vh;
            display: grid;
            place-items: center;
        }

        .card {
            width: min(940px, 92vw);
            border-radius: 18px;
            padding: 24px;
            background: rgba(255, 255, 255, 0.9);
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.12);
            backdrop-filter: blur(4px);
        }

        h1 {
            margin: 0 0 8px;
            font-size: 1.6rem;
        }

        p {
            margin: 0 0 18px;
            color: #4b5563;
        }

        .meta {
            display: grid;
            gap: 8px;
            margin-bottom: 16px;
        }

        .meta strong {
            display: inline-block;
            width: 140px;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 16px 0;
        }

        button {
            border: 0;
            padding: 10px 14px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
        }

        .btn-primary {
            background: #0f766e;
            color: #fff;
        }

        .btn-danger {
            background: #b91c1c;
            color: #fff;
        }

        pre {
            padding: 14px;
            border-radius: 12px;
            background: #111827;
            color: #d1d5db;
            overflow: auto;
            max-height: 320px;
        }
    </style>
</head>
<body>
    <section class="card">
        <h1>Signed in with SSO</h1>
        <p>Your local session is active and mapped to SSO identity data.</p>

        <div class="meta">
            <div><strong>Name:</strong> {{ $user->name }}</div>
            <div><strong>Email:</strong> {{ $user->email }}</div>
            <div><strong>SSO Subject:</strong> {{ $user->sso_sub }}</div>
            <div><strong>User Type:</strong> {{ $user->sso_user_type ?? '-' }}</div>
        </div>

        <div class="actions">
            <form method="POST" action="{{ route('sso.refresh-token') }}">
                @csrf
                <button class="btn-primary" type="submit">Refresh access token</button>
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button class="btn-danger" type="submit">Logout</button>
            </form>
        </div>

        <h2>SSO Token Session Data</h2>
        <pre>{{ json_encode($ssoTokens, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>

        <h2>Raw SSO Profile (Stored)</h2>
        <pre>{{ json_encode($user->sso_profile, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
    </section>
</body>
</html>
