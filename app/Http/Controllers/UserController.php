<?php

namespace App\Http\Controllers;

use App\Post;
use App\User;
use App\Profile;
use Illuminate\Http\Request;
use App\Events\UserWasRegistered;
use App\Events\UserEmailHasChanged;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Input;
use Tymon\JWTAuth\Contracts\JWTSubject;

class UserController extends ApiController
{
    /**
     * Create a new UserController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['store', 'verify', 'resend']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->authorize(auth()->user());

        $users = User::all();

        return $this->successResponse($users);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validate = $this->validate($request, [
            'nick' => 'required|string|max:255|unique:users',
            'name' => 'required|string|max:255|regex:#^[A-Za-zÁÉÍÓÚñáéíóúÑ\s]+$#',           
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|string|min:4|confirmed',
            'description' => 'string',
        ]);

        $defaultProfile = Profile::where('profile', 'user')->first();

        $userData = $request->all();
        $userData['password'] = bcrypt($request->password);
        $userData['profile_id'] = $defaultProfile->id;
        $userData['verified'] = 0;
        $userData['enabled'] = 1;
        $userData['verification_email_token'] = User::generateEmailToken();
        
        $user = User::create($userData);       

        event(new UserWasRegistered($user));

        return $this->showOne($user, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(User $user)
    {
        $user['post'] = $user->post;
        $user['following'] = $user->following;
        $user['follower'] = $user->follower;
        return $this->showOne($user);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, User $user)
    {
        $this->authorize($user);
        
        $validate = $this->validate($request, [
            'nick' => 'string|max:255|unique:users,nick,'.$user->id,
            'profile' => 'integer',
            'name' => 'string|max:255|regex:#^[A-Za-zÁÉÍÓÚñáéíóúÑ\s]+$#',           
            'email' => 'string|email|max:255|unique:users,email,'.$user->id,
            'password' => 'string|min:4|confirmed',
            'description' => 'string|nullable',
            'image' => 'nullable',
            'enabled' => 'boolean'
        ]);
        
        $user->fill(Input::all());
        
        if ($request->has('password')) {
            $user->password = bcrypt($request->password);
        }

        // if ($request->has('email')) {
        //     $user->verified = 0;
        //     $user->verification_email_token = User::generateEmailToken();
        //     event(new UserEmailHasChanged($user));
        // }

        if (!$user->isDirty()) {
            return $this->errorResponse('Se debe especificar al menos un valor diferente para actulizar', 422);
        }

        $user->save();

        return $this->showOne($user);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user)
    {
        $user->delete();

        return $this->showOne($user);
    }

    public function password(Request $request, User $user)
    {
        //$this->authorize($user);
        
        $validate = $this->validate($request, [
            'password_old' => 'required|string|min:4',
            'password_new' => 'required|string|min:4'
        ]);

        if (Hash::check($request->password_old, $user->password)) {
            $user->password = bcrypt($request->password_new);
            $user->save();
            
            return $this->showMessage('OK');
        }
        else {
            return $this->errorResponse('NO', 400);
        }
    }

    public function post(User $user) {
        $post = Post::where('user_id', $user->id)
                        ->orderBy('created_at', 'DESC')
                        ->get();

        return $this->successResponse($post);
    }

    public function pagePost(User $user, $init, $num)
    {
        $post = Post::where('user_id', $user->id)
                        ->orderBy('created_at', 'DESC')
                        ->offset($init)
                        ->limit($num)
                        ->get();

        return $this->successResponse($post);
    }

    public function follower(User $user) {
        $follower = $user->follower;

        return $this->successResponse($follower);
    }

    public function following(User $user) {
        $following = $user->following;

        return $this->successResponse($following);
    }

    /**
     * Verifies the specified resource.
     *
     * @param  string  $token
     * @return \Illuminate\Http\Response
     */
    public function verify($token)
    {
        $user = User::where('verification_email_token', $token)->firstOrFail();

        $user->verified = 1;
        $user->verification_email_token = null;
        $user->save();

        return redirect('http://localhost:8080/login');
    }

    /**
     * Resend the verification email for a specified resource.
     *
     * @param  string  $token
     * @return \Illuminate\Http\Response
     */
    public function resend(User $user)
    {
        if ($user->verified == 1) {
            return $this->errorResponse('El usuario ya está verificado', 409);
        }

        event(new UserWasRegistered($user));
        
        return $this->showMessage('El email de verificación ha sido reenviado');
    }
}
