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

class BlazervelServiceProvider extends ServiceProvider 
{
  private string $pathTo = __DIR__ . '/../..';

	public function register()
	{
    //
	}

  public function boot()
  {
    $this->loadViews();
    $this->loadComponents();
    $this->loadRoutes();
    $this->loadTranslations();
    $this->loadDirectives();

    $this->loadFortify();
  }

  private function loadDirectives(): void
  {
    Blade::directive('blazervel', fn ($group) => trim("
      <script type=\"text/javascript\"> 
        const Blazervel = <?php echo Js::from(['translations' => " . self::class . "::translations()]) ?>
      </script>

      <?php echo app('" . BladeRouteGenerator::class . "')->generate({$group}); ?>
    "));
  }

  static function translations(): array
  {
    $translationFiles = File::files(
      lang_path(
        App::currentLocale()
      )
    );

    $langKey = fn ($file) => (
      Str::remove(".{$file->getExtension()}", $file->getFileName())
    );

    return (
      collect($translationFiles)
        ->map(fn ($file) => [$langKey($file) => Lang::get($langKey($file))])
        ->collapse()
        ->all()
    );
  }

  private function loadViews()
  {
    $this->loadViewsFrom(
      "{$this->pathTo}/resources/views", 'blazervel'
    );
  }

  private function loadComponents()
  {
    Blade::componentNamespace(
      'Blazervel\\Blazervel\\Components\\Components', 
      'blazervel'
    );

    if (method_exists($this->app['blade.compiler'], 'precompiler')) {
      $this->app['blade.compiler']->precompiler(function ($string) {
        return app(TagCompiler::class)->compile($string);
      });
    }
  }
  
  private function loadRoutes() 
  {
    $this->loadRoutesFrom(
      "{$this->pathTo}/routes/web.php"
    );

    $this->loadRoutesFrom(
      "{$this->pathTo}/routes/fortify.php"
    );
  }

  private function loadTranslations() 
  {
    $this->loadTranslationsFrom(
      "{$this->pathTo}/lang", 
      'blazervel'
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