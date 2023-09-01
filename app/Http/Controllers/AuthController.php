<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginUserRequest;
use App\Http\Requests\RegusterUserRequest;
use App\Models\User;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class AuthController extends Controller
{
    use HttpResponses;

    public function login(LoginUserRequest $request){

        $request->validated($request->all());

        if(!Auth::attempt($request->only(['email', 'password']))) {
            return $this->error('', 'Credentials do not match', 200);
        }

        $user = User::where([
            'email' => $request->email
        ])->first();


        return $this->success([
            'user' => $user,
            'token' => $user->createToken('API Token for '.$user->name)->plainTextToken
        ]);
    }


    public function register(RegusterUserRequest $request){

        $request->validated($request->all());

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password)
        ]);


        return $this->success([
           'user' => $user,
           'token' => $user->createToken('API Token for '.$user->name)->plainTextToken
        ]);
    }

    public function profile(){
        return $this->success([
            'user' => Auth::user(),
            'token' => ''
        ]);
    }

    public function profileUpdate(Request $request){

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $requestData['name'] = $request->name;

        if(isset($request->password)){

            if(trim($request->password) !==""){
                $request->validate([
                    'password' => ['required', 'confirmed', Rules\Password  ::defaults()]
                ]);

                $requestData['password'] = Hash::make($request->password);
            }
        }

        $user = User::where([
            'email' => Auth::user()->email
        ])->first();

        $user->update($requestData);

        return $this->success([
            'user' => $user,
            'token' => ''
        ]);
    }


    public function logout(){

        Auth::user()->currentAccessToken()->delete();

        return $this->success([
            'message' => 'You have successfully been logged out and your token has been removed'
        ]);
    }
}
