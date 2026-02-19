<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rules\Enum;
use App\Services\AuditService;
use Illuminate\Support\Facades\Auth;


class AdminUserController extends Controller
{
    // 📌 Listar usuarios
    public function index(): JsonResponse
    {
        $users = User::select('id', 'name', 'email', 'role', 'created_at')
            ->orderBy('id', 'desc')
            ->get();

        return response()->json($users);
    }

    // 📌 Crear usuario
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role' => ['required', new Enum(UserRole::class)],
        ]);

        $user = User::create($validated);

        AuditService::log(
            'crear_usuario',
            'Creó usuario ID ' . $user->id . ' con rol ' . $user->role
        );

        return response()->json([
            'message' => 'Usuario creado correctamente',
            'data' => $user
        ], 201);
    }

    // 📌 Actualizar usuario
    public function update(Request $request, $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => "sometimes|email|unique:users,email,$id",
            'password' => 'nullable|min:6',
            'role' => ['sometimes', new Enum(UserRole::class)],
        ]);

        // Si password viene vacío, lo eliminamos para no sobreescribir
        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $rolAnterior = $user->role;

        $user->update($validated);

        AuditService::log(
            'actualizar_usuario',
            'Actualizó usuario ID ' . $user->id .
                ' Rol: ' . $rolAnterior . ' → ' . $user->role
        );

        return response()->json([
            'message' => 'Usuario actualizado correctamente',
            'data' => $user
        ]);
    }


    // 📌 Eliminar usuario
    public function destroy($id): JsonResponse
    {
        $user = User::findOrFail($id);

        // 🚫 Evitar que un usuario se elimine a sí mismo
        if (Auth::id() === $user->id) {
            return response()->json([
                'message' => 'No puede eliminar su propio usuario'
            ], 403);
        }

        // 🚫 Evitar eliminar último admin

        // if ($user->role->value === 'admin' &&
        //     User::where('role', 'admin')->count() <= 1
        // ) 
        
        if (
            $user->role === UserRole::ADMIN &&
            User::where('role', UserRole::ADMIN)->count() <= 1
        ) {
            return response()->json([
                'message' => 'No se puede eliminar el último administrador'
            ], 403);
        }

        $nombre = $user->name;

        $user->delete();

        AuditService::log(
            'eliminar_usuario',
            'Eliminó usuario ID ' . $id . ' (' . $nombre . ')'
        );

        return response()->json([
            'message' => 'Usuario eliminado correctamente'
        ]);
    }
}
