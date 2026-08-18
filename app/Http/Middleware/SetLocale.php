<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * اللغات المدعومة في النظام.
     *
     * @var array<int, string>
     */
    protected array $supported = ['en', 'ar'];

    /**
     * يضبط لغة التطبيق من الكوكي (app_locale) اللي يكتبها الـFrontend،
     * باش رسائل الـvalidation والإشعارات تجي بنفس لغة الواجهة.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->cookie('app_locale');

        if (is_string($locale) && in_array($locale, $this->supported, true)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
