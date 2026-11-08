<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Game Shared With You</title>
</head>
<body style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #0f172a; color: #f8fafc; padding: 40px 20px; margin: 0;">
    <div style="max-width: 520px; margin: 0 auto; background-color: #1e293b; border-radius: 14px; padding: 32px; border: 1px solid #334155; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.4);">
        <div style="text-align: center; margin-bottom: 24px;">
            <h1 style="color: #38bdf8; font-size: 22px; font-weight: 900; letter-spacing: -0.025em; margin: 0;">PROKEEPER</h1>
            <p style="color: #94a3b8; font-size: 13px; margin-top: 4px; font-weight: 600;">Game Invitation</p>
        </div>

        <p style="color: #e2e8f0; font-size: 15px; line-height: 1.5; margin-bottom: 20px;">
            <strong style="color: #ffffff;">{{ $sender->name }}</strong> ({{ $sender->email }}) has shared a game with you:
        </p>

        <!-- Game Card -->
        <div style="background-color: #0f172a; border: 1px solid #334155; border-radius: 10px; padding: 18px; margin-bottom: 20px;">
            <div style="font-size: 11px; text-transform: uppercase; font-weight: 800; letter-spacing: 0.05em; color: #38bdf8; margin-bottom: 6px;">
                {{ ucfirst($game->sport) }} &bull; Access: {{ $permission === 'edit' ? 'Can Edit / Operate' : 'View Only' }}
            </div>
            <div style="font-size: 18px; font-weight: 800; color: #ffffff; margin-bottom: 8px;">
                {{ $game->home_display_name }} <span style="color: #64748b; font-weight: 400;">vs</span> {{ $game->away_display_name }}
            </div>
            <div style="font-size: 12px; color: #94a3b8;">
                Venue: {{ $game->venue ?: 'Main Court' }} &bull; Code: <span style="font-family: monospace; font-weight: 700; color: #e2e8f0;">{{ $game->access_code }}</span>
            </div>
        </div>

        @if (!empty($note))
            <!-- Personal Message Box -->
            <div style="background-color: #1e1b4b; border-left: 4px solid #6366f1; border-radius: 4px; padding: 14px 16px; margin-bottom: 24px;">
                <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: #a5b4fc; margin-bottom: 4px;">Message from {{ $sender->name }}:</div>
                <div style="font-size: 14px; color: #e0e7ff; line-height: 1.5; font-style: italic;">
                    "{{ $note }}"
                </div>
            </div>
        @endif

        <div style="text-align: center; margin-bottom: 24px;">
            @php
                $targetUrl = ($permission === 'edit')
                    ? route('games.operator', $game->uuid)
                    : route('public.live', $game->access_code);
            @endphp
            <a href="{{ $targetUrl }}" style="display: inline-block; background-color: #2563eb; color: #ffffff; text-decoration: none; font-size: 14px; font-weight: 700; padding: 12px 28px; border-radius: 10px; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4);">
                {{ $permission === 'edit' ? 'Open in Game Operator' : 'Open Live Game' }}
            </a>
        </div>

        <p style="color: #64748b; font-size: 12px; line-height: 1.5; margin: 0; text-align: center; border-top: 1px solid #334155; padding-top: 18px;">
            Sign in with your email on ProKeeper to access all your shared games.
        </p>
    </div>
</body>
</html>
