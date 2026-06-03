<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    // Menampilkan daftar user
    public function index()
    {
        $users = User::orderBy('name', 'asc')->get();
        return view('users.index', compact('users'));
    }

    // Update role user (oleh Admin)
    public function updateRole(Request $request, $id)
    {
        $request->validate([
            'role' => 'required|in:administrator,user,viewer',
        ]);

        $user = User::findOrFail($id);

        // Mencegah admin mengubah role dirinya sendiri
        if ($user->id === Auth::id()) {
            return back()->with('error', 'Anda tidak dapat mengubah role akun Anda sendiri.');
        }

        $user->role = $request->role;
        $user->save();

        return back()->with('success', "Role {$user->name} berhasil diubah menjadi {$user->role}.");
    }

    // Menghapus user
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if ($user->id === Auth::id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $user->delete();
        return back()->with('success', 'User berhasil dihapus.');
    }
}