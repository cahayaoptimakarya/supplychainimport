<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    public function index()
    {
        return view('admin.masterdata.roles.index');
    }

    public function data()
    {
        $roles = Role::orderBy('name')->withCount('users')->get()->map(function ($r) {
            return [
                'id' => $r->id,
                'name' => $r->name,
                'slug' => $r->slug,
                'users_count' => $r->users_count,
            ];
        });
        return response()->json(['data' => $roles]);
    }

    public function create()
    {
        return view('admin.masterdata.roles.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:roles,name'],
            'slug' => ['nullable', 'string', 'max:100', 'unique:roles,slug'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);
        $slug = $validated['slug'] ?? Str::slug($validated['name']);
        if (Role::where('slug', $slug)->exists()) {
            return back()->withErrors(['slug' => 'Slug sudah digunakan'])->withInput();
        }
        Role::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
        ]);
        return redirect()->route('admin.masterdata.roles.index')->with('success', 'Role berhasil dibuat');
    }

    public function edit(Role $role)
    {
        return view('admin.masterdata.roles.edit', compact('role'));
    }

    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('roles', 'name')->ignore($role->id)],
            'slug' => ['required', 'string', 'max:100', Rule::unique('roles', 'slug')->ignore($role->id)],
            'description' => ['nullable', 'string', 'max:255'],
        ]);
        $role->update($validated);
        return redirect()->route('admin.masterdata.roles.index')->with('success', 'Role berhasil diperbarui');
    }

    public function destroy(Role $role)
    {
        $role->delete();
        return redirect()->route('admin.masterdata.roles.index')->with('success', 'Role berhasil dihapus');
    }
}

