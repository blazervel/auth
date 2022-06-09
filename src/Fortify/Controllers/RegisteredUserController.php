<?php

namespace Blazervel\Auth\Fortify\Controllers;

use App\Http\Controllers\Controller;
use App\Models\{ User, Workspace };
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{ App, Auth, Hash };
use Illuminate\Validation\Rules;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Illuminate\Http\RedirectResponse;

class RegisteredUserController extends Controller
{
  public function create(): InertiaResponse
  {
    return Inertia::render('Auth/Register');
  }

  public function store(Request $request): RedirectResponse
  {
    if (App::environment('production')) :
      return abort(404);
    endif;

    $request->validate([
      'first_name' => 'required|string|max:255',
      'last_name'  => 'required|string|max:255',
      'email'      => 'required|string|email:dns,rfc,spoof,filter|max:255|unique:users',
      'phone'      => 'required|string|max:255|unique:users',
      'password'   => ['required', 'confirmed', Rules\Password::defaults()],
    ]);

    $workspace = Workspace::create([
      'name' => __('workspaces.users_workspace', ['name_possessive' => $user->firstNamePossessive]),
    ]);

    $user = User::create([
      'first_name' => $request->first_name,
      'last_name'  => $request->last_name,
      'email'      => $request->email,
      'phone'      => $request->phone,
      'password'   => Hash::make($request->password),
    ]);
    
    $workspace->users()->attach(
      $user->id
    );

    $budget = $workspace->budgets()->create([
      'name' => 'My Budget',
      'default' => true,
    ]);

    $budget->categories()->createMany([

    ]);

    event(
      new Registered($user)
    );

    Auth::login(
      $user
    );

    return redirect(
      RouteServiceProvider::HOME
    );
  }
}
