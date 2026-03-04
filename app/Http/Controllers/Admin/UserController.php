<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::latest()->paginate(15);
        return view('admin.users.index', compact('users'));
    }

    public function toggleRole(User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users.index')->with('message', 'No puedes cambiar tu propio rol.');
        }
        $user->update(['role' => $user->role === 'admin' ? 'user' : 'admin']);
        return redirect()->route('admin.users.index')->with('message', 'Rol actualizado.');
    }
}
