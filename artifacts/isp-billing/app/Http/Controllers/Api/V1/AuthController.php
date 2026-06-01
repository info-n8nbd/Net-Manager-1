<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\MobileApiService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    protected MobileApiService $apiService;

    public function __construct(MobileApiService $apiService)
    {
        $this->apiService = $apiService;
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $result = $this->apiService->authenticate(
            $request->email,
            $request->password,
            $request->device_name,
            $request->device_id
        );

        if (!$result['success']) {
            return response()->json(['error' => $result['message']], 401);
        }

        return response()->json($result);
    }

    public function clientLogin(Request $request)
    {
        $request->validate([
            'username'       => 'required|string',
            'pppoe_password' => 'required|string',
        ]);

        $result = $this->apiService->authenticateByPppoe(
            $request->username,
            $request->pppoe_password,
            $request->device_name,
            $request->device_id
        );

        if (!$result['success']) {
            return response()->json(['error' => $result['message']], 401);
        }

        return response()->json($result);
    }

    public function refresh(Request $request)
    {
        $request->validate(['refresh_token' => 'required|string']);

        $result = $this->apiService->refreshAccessToken($request->refresh_token);

        if (!$result['success']) {
            return response()->json(['error' => $result['message']], 401);
        }

        return response()->json($result);
    }

    public function logout(Request $request)
    {
        $request->validate(['refresh_token' => 'required|string']);

        $this->apiService->revokeRefreshToken($request->refresh_token);

        return response()->json(['message' => 'Logged out successfully']);
    }
}
