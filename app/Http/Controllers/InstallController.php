<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InstallController extends Controller
{
    /**
     * Show the PWA Installation and App Launch page.
     */
    public function index(): View
    {
        $recentGames = Game::query()
            ->latest()
            ->take(4)
            ->get(['id', 'access_code', 'sport', 'status', 'home_team_name', 'away_team_name', 'home_score', 'away_score']);

        return view('install', compact('recentGames'));
    }

    /**
     * Serve dynamic PWA Manifest based on selected theme/icon.
     */
    public function manifest(Request $request): JsonResponse
    {
        $theme = strtolower($request->query('theme', $request->query('icon', 'blue')));

        $themes = [
            'blue' => [
                'name' => 'ProKeeper - Athletic Engine',
                'bg' => '#020617',
                'theme' => '#2563eb',
                'pwa_192' => '/icons/appicon_blue_pwa_192.png',
                'pwa_512' => '/icons/appicon_blue_pwa_512.png',
                'svg' => '/icons/appicon_blue_svg.svg',
            ],
            'black' => [
                'name' => 'ProKeeper - Athletic Engine (Midnight Obsidian)',
                'bg' => '#000000',
                'theme' => '#0f172a',
                'pwa_192' => '/icons/appicon_black_pwa_192.png',
                'pwa_512' => '/icons/appicon_black_pwa_512.png',
                'svg' => '/icons/appicon_black_svg.svg',
            ],
            'white' => [
                'name' => 'ProKeeper - Athletic Engine (Pure Quartz)',
                'bg' => '#ffffff',
                'theme' => '#ffffff',
                'pwa_192' => '/icons/appicon_white_pwa_192.png',
                'pwa_512' => '/icons/appicon_white_pwa_512.png',
                'svg' => '/icons/appicon_white_svg.svg',
            ],
        ];

        $selected = $themes[$theme] ?? $themes['blue'];

        $manifest = [
            'name' => $selected['name'],
            'short_name' => 'ProKeeper',
            'description' => 'High-speed sports statistics, live stadium scoreboards, and official NCAA digital scorebooks.',
            'start_url' => '/install',
            'scope' => '/',
            'display' => 'standalone',
            'orientation' => 'any',
            'background_color' => $selected['bg'],
            'theme_color' => $selected['theme'],
            'categories' => ['sports', 'utilities', 'productivity'],
            'icons' => [
                [
                    'src' => $selected['pwa_192'],
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any maskable',
                ],
                [
                    'src' => $selected['pwa_512'],
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any maskable',
                ],
                [
                    'src' => $selected['svg'],
                    'sizes' => 'any',
                    'type' => 'image/svg+xml',
                    'purpose' => 'any',
                ],
            ],
            'shortcuts' => [
                [
                    'name' => 'Live Stadium Scoreboard',
                    'short_name' => 'Scoreboard',
                    'description' => 'View live game scores, clock, and fouls',
                    'url' => '/scoreboard',
                    'icons' => [['src' => $selected['pwa_192'], 'sizes' => '192x192']],
                ],
                [
                    'name' => 'Watch Game Live',
                    'short_name' => 'Watch',
                    'description' => 'Watch live game stream, box score, and play-by-play',
                    'url' => '/watch',
                    'icons' => [['src' => $selected['pwa_192'], 'sizes' => '192x192']],
                ],
                [
                    'name' => 'Digital Scorebook',
                    'short_name' => 'Scorebook',
                    'description' => 'NCAA format digital scorebook & PDF export',
                    'url' => '/scorebook',
                    'icons' => [['src' => $selected['pwa_192'], 'sizes' => '192x192']],
                ],
                [
                    'name' => 'Operator Command Center',
                    'short_name' => 'Operator',
                    'description' => 'Scorekeeper dashboard & game operation',
                    'url' => '/dashboard',
                    'icons' => [['src' => $selected['pwa_192'], 'sizes' => '192x192']],
                ],
            ],
        ];

        return response()->json($manifest)
            ->header('Content-Type', 'application/manifest+json');
    }
}
