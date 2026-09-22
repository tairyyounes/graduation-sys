<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
<<<<<<< Updated upstream
=======
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
>>>>>>> Stashed changes

class SetLocale
{
    /**
<<<<<<< Updated upstream
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
=======
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Session::has('locale')) {
            App::setLocale(Session::get('locale'));
        } else {
            App::setLocale(config('app.locale'));
>>>>>>> Stashed changes
        }

        return $next($request);
    }
}
