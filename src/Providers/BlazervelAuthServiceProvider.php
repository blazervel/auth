<?php

namespace Blazervel\Auth\Providers;

use Blazervel\Blazervel\Fortify\Actions\{
  CreateNewUser,
  ResetUserPassword,
  UpdateUserPassword,
  UpdateUserProfileInformation
};
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use Laravel\Fortify\Fortify;

use Blazervel\Blazervel\View\TagCompiler;

use Tightenco\Ziggy\BladeRouteGenerator;
use Illuminate\Support\Facades\{ File, Blade, App, Lang };
use Illuminate\Support\{ Str, ServiceProvider };

class BlazervelAuthServiceProvider extends ServiceProvider 
{
  private string $pathTo = __DIR__ . '/../..';

	public function register()
	{
    //
	}

  public function boot()
  {
    $this->loadViews();
    $this->loadRoutes();
    $this->loadTranslations();
    $this->loadFortify();
  }

  private function loadViews()
  {
    $this->loadViewsFrom(
      "{$this->pathTo}/resources/views", 'blazervel-auth'
    );
  }
  
  private function loadRoutes() 
  {
    $this->loadRoutesFrom(
      "{$this->pathTo}/routes/routes.php"
    );
  }

  private function loadTranslations() 
  {
    $this->loadTranslationsFrom(
      "{$this->pathTo}/lang", 
      'blazervel-auth'
    );
  }

  private function loadFortify()
  {
    config(['fortify.views' => false]);

    Fortify::createUsersUsing(CreateNewUser::class);
    Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
    Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
    Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

    RateLimiter::for('login', function (Request $request) {
      $email = (string) $request->email;
      return Limit::perMinute(5)->by($email.$request->ip());
    });

    RateLimiter::for('two-factor', function (Request $request) {
      return Limit::perMinute(5)->by($request->session()->get('login.id'));
    });
  }

}