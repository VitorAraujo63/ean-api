<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;


/**
 * @OA\Tag(
 *     name="Autenticação",
 *     description="Operações relacionadas ao registro e login de usuários."
 * )
 */
class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/register",
     *     tags={"Autenticação"},
     *     summary="Registrar um novo usuário",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 required={"name", "email", "password", "role"},
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="johndoe@example.com"),
     *                 @OA\Property(property="password", type="string", example="secret123"),
     *                 @OA\Property(property="role", type="string", enum={"admin", "operador"}, example="admin")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=201, description="Usuário registrado com sucesso."),
     *     @OA\Response(response=400, description="Erro de validação dos dados.")
     * )
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'role' => 'in:admin,operador',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role ?? 'operador',
        ]);

        return response()->json(['message' => 'Usuário registrado com sucesso.'], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/login",
     *     tags={"Autenticação"},
     *     summary="Realizar login de um usuário",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 required={"email", "password"},
     *                 @OA\Property(property="email", type="string", example="johndoe@example.com"),
     *                 @OA\Property(property="password", type="string", example="secret123")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="Login bem-sucedido, retorna o token."),
     *     @OA\Response(response=401, description="Credenciais inválidas.")
     * )
     */
    public function login(Request $request)
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['As credenciais fornecidas estão incorretas.'],
            ]);
        }

        $token = $user->createToken('token-api')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }
}
