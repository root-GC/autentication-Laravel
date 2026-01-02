<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class EmailValidationController extends Controller
{
    public function sendToken(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = \App\Models\User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'esse email não existe'], 404);
        }

        $token = Str::random(6); // ou UUID longo
        $expires = Carbon::now()->addMinutes(30);

        // guarda no DB
        DB::table('email_verifications')->updateOrInsert(
            ['email' => $user->email],
            ['token' => $token, 'expires_at' => $expires, 'updated_at' => now()]
        );

        // envia email (Mailtrap ou log)
        Mail::raw("Seu token de validação: $token", function ($message) use ($user) {
            $message->to($user->email)
                ->subject('Validação de Email');
        });

        return response()->json(['message' => 'Token enviado']);
    }

    public function verifyToken(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required'
        ]);

        $record = DB::table('email_verifications')
            ->where('email', $request->email)
            ->where('token', $request->token)
            ->first();

        if (!$record || $record->expires_at < now()) {
            return response()->json(['message' => 'Token inválido ou expirado'], 400);
        }

        return response()->json(['message' => 'Email validado com sucesso!']);
    }
}
