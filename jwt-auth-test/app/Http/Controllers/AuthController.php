<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\JWTGuard;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Illuminate\Support\Str;

class AuthController extends Controller
{

    public function profile()
    {
        $user = auth('api')->user(); // pega o usuário logado

        return response()->json([
            'user' => $user,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name')
        ]);
    }

    // LOGIN
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (! $token = Auth::guard('api')->attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // 2FA: gera token temporário e envia email
        $user = auth('api')->user(); // ou User::where('email', ...)->first()
        $twoFactorCode = rand(100000, 999999);
        $expires = now()->addMinutes(10);

        DB::table('two_factor_tokens')->updateOrInsert(
            ['user_id' => $user->id],
            ['token' => $twoFactorCode, 'expires_at' => $expires, 'updated_at' => now()]
        );

        Mail::raw("Seu código 2FA: $twoFactorCode", function ($message) use ($user) {
            $message->to($user->email)->subject('Código 2FA');
        });

        return response()->json([
            'message' => 'Código 2FA enviado por email',
            'access_token' => $token // opcional: só referência
        ]);
    }

    public function verify2FA(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required|digits:6'
        ]);

        $user = \App\Models\User::where('email', $request->email)->first();
        if (!$user) return response()->json(['message' => 'Usuário não encontrado'], 404);

        $record = DB::table('two_factor_tokens')
            ->where('user_id', $user->id)
            ->where('token', $request->token)
            ->where('expires_at', '>', now())
            ->first();

        if (!$record) return response()->json(['message' => 'Token inválido ou expirado'], 400);

        return response()->json([
            'message' => '2FA verificado com sucesso!',
            'user' => $user,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name')
        ]);
    }

    // REFRESH token
    public function refresh()
    {
        try {
            $token = JWTAuth::parseToken()->refresh();

            return $this->respondWithToken($token);
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json(['error' => 'Token expired, login again'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['error' => 'Token invalid'], 401);
        }
    }

    // resposta padrão
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => auth('api')->factory()->getTTL() * 60,
        ]);
    }

    
    public function logout()
    {
        auth('api')->logout();
        return response()->json(['message' => 'Successfully logged out']);
    }
}
