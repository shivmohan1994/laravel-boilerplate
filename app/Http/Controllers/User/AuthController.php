<?php namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Services\UserServiceInterface;
use App\Http\Requests\User\SignInRequest;

class AuthController extends Controller
{

    /** @var \App\Services\UserServiceInterface UserService */
    protected $userService;

    public function __construct(UserServiceInterface $userService)
    {
        $this->userService = $userService;
    }

    public function getSignIn()
    {
        return view('pages.user.auth.signin', [
        ]);
    }

    public function postSignIn(SignInRequest $request)
    {
        $user = $this->userService->signIn($request->all());
        if (empty($user)) {
            return redirect()->action('User\AuthController@getSignIn');
        }

        return redirect()->intended(\URL::action('User\IndexController@index'));
   }
}
