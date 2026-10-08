<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class HandleAppearance
{
    /**
     * Keep in sync with DARK_THEMES in resources/js/lib/theme.svelte.ts.
     *
     * @var list<string>
     */
    private const DARK_THEMES = [
        'onair-dark', 'dark', 'synthwave', 'halloween', 'forest', 'aqua',
        'black', 'luxury', 'dracula', 'business', 'night', 'coffee',
        'dim', 'sunset', 'abyss',
    ];

    /**
     * Keep in sync with THEME_OPTIONS in resources/js/lib/theme.svelte.ts.
     *
     * @var list<string>
     */
    private const LIGHT_THEMES = [
        'onair', 'light', 'cupcake', 'bumblebee', 'emerald', 'corporate',
        'retro', 'cyberpunk', 'valentine', 'garden', 'lofi', 'pastel',
        'fantasy', 'wireframe', 'cmyk', 'autumn', 'acid', 'lemonade',
        'winter', 'nord', 'caramellatte', 'silk',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $appearance = $request->cookie('appearance') ?? 'system';
        $theme = $this->resolveTheme($request->cookie('theme'), $appearance);

        View::share('appearance', $appearance);
        View::share('theme', $theme);
        View::share('themeIsDark', in_array($theme, self::DARK_THEMES, true));

        return $next($request);
    }

    /**
     * Resolve the daisyUI theme for first paint. 'auto' follows the
     * appearance cookie between the On Air pair ('system' resolves
     * client-side and defaults to light here, matching previous behavior).
     */
    private function resolveTheme(?string $cookie, string $appearance): string
    {
        if ($cookie !== null && $cookie !== 'auto' && in_array($cookie, [...self::DARK_THEMES, ...self::LIGHT_THEMES], true)) {
            return $cookie;
        }

        return $appearance === 'dark' ? 'onair-dark' : 'onair';
    }
}
