<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class StaffAuthController extends Controller
{
    /**
     * Merchant işçisinin (kassir/admin) API girişi — kupon oxutmaq üçün.
     */
    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return response()->json(['message' => 'Email və ya şifrə yanlışdır'], 422);
        }

        if (! in_array($user->role, [User::ROLE_SUPER_ADMIN, User::ROLE_MERCHANT_ADMIN, User::ROLE_CASHIER], true)) {
            return response()->json(['message' => 'Bu hesabın API girişi yoxdur'], 403);
        }

        return response()->json([
            'user' => [
                'id'          => $user->id,
                'name'        => $user->name,
                'email'       => $user->email,
                'role'        => $user->role,
                'merchant_id' => $user->merchant_id,
            ],
            'token' => $user->createToken('staff-api')->plainTextToken,
        ]);
    }
}
