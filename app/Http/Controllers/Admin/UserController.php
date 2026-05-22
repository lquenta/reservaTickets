<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::with('createdBy')->latest();

        if ($request->filled('q')) {
            $search = $request->string('q')->trim()->toString();
            $term = '%'.$search.'%';
            $query->where(function ($q) use ($term, $search) {
                $q->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term);

                if (ctype_digit($search)) {
                    $q->orWhere('id', (int) $search);
                    if (strlen($search) >= 4) {
                        $q->orWhere('ci', 'like', $term)
                            ->orWhere('phone', 'like', $term);
                    }
                } else {
                    $q->orWhere('ci', 'like', $term)
                        ->orWhere('phone', 'like', $term);
                }
            });
        }

        $users = $query->paginate(15)->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function verifyEmail(User $user): RedirectResponse
    {
        if ($user->isAdmin() || $user->isVendedor()) {
            return redirect()->route('admin.users.index')->with('message', 'No aplica a administradores ni vendedores.');
        }

        $user->forceFill(['email_verified_at' => now()])->save();

        return redirect()->route('admin.users.index')->with('message', 'Correo marcado como verificado para '.$user->name.'.');
    }

    public function setRole(Request $request, User $user): RedirectResponse
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('admin.users.index')->with('message', 'No puedes cambiar tu propio rol.');
        }

        $request->validate([
            'role' => ['required', 'in:'.User::ROLE_USER.','.User::ROLE_ADMIN.','.User::ROLE_VENDEDOR],
        ]);

        $user->update(['role' => $request->input('role')]);

        return redirect()->route('admin.users.index')->with('message', 'Rol actualizado.');
    }
}
