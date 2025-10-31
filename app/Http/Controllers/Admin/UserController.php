<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        return view('admin.masterdata.users.index');
    }

    public function data()
    {
        $users = User::with('roles:id,name')->orderBy('name')->get()->map(function ($u) {
            return [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'roles' => $u->roles->pluck('name')->implode(', '),
            ];
        });
        return response()->json(['data' => $users]);
    }

    public function create()
    {
        $roles = Role::orderBy('name')->get();
        return view('admin.masterdata.users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required','string','max:255'],
            'email' => ['required','email','max:255','unique:users,email'],
            'password' => ['required','string','min:6','max:100'],
            'roles' => ['nullable','array'],
            'roles.*' => ['integer','exists:roles,id'],
        ]);
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'email_verified_at' => now(),
        ]);
        if (!empty($validated['roles'])) {
            $user->roles()->sync($validated['roles']);
        }
        return redirect()->route('admin.masterdata.users.index')->with('success', 'User berhasil dibuat');
    }

    public function edit(User $user)
    {
        $roles = Role::orderBy('name')->get();
        $user->load('roles');
        return view('admin.masterdata.users.edit', compact('user','roles'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required','string','max:255'],
            'email' => ['required','email','max:255', Rule::unique('users','email')->ignore($user->id)],
            'password' => ['nullable','string','min:6','max:100'],
            'roles' => ['nullable','array'],
            'roles.*' => ['integer','exists:roles,id'],
        ]);
        $update = [
            'name' => $validated['name'],
            'email' => $validated['email'],
        ];
        if (!empty($validated['password'])) {
            $update['password'] = Hash::make($validated['password']);
        }
        $user->update($update);
        $user->roles()->sync($validated['roles'] ?? []);
        return redirect()->route('admin.masterdata.users.index')->with('success', 'User berhasil diperbarui');
    }

    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->route('admin.masterdata.users.index')->with('success', 'User berhasil dihapus');
    }
}
