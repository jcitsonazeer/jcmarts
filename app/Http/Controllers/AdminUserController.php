<?php

namespace App\Http\Controllers;

use App\Models\AdminLogin;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    public function index()
    {
        $users = AdminLogin::orderBy('id', 'desc')->get();

        return view('admin.dashboard.users.index', compact('users'));
    }

    public function create()
    {
        $this->ensureMaster();

        return view('admin.dashboard.users.create');
    }

    public function store(Request $request)
    {
        $this->ensureMaster();

        $validated = $request->validate([
            'admin_username' => ['required', 'string', 'max:100', 'unique:admin_login,admin_username'],
            'admin_password' => ['required', 'string', 'min:6'],
            'user_type'      => ['required', Rule::in(['MASTER', 'ENTRYSTAFF'])],
            'cur_status'     => ['required', Rule::in(['ACTIVE', 'INACTIVE'])],
        ]);

        $normalizedUsername = strtoupper($validated['admin_username']);

        AdminLogin::create([
            'admin_username' => $normalizedUsername,
            'admin_password' => Hash::make($validated['admin_password']),
            'user_type'       => $validated['user_type'],
            'cur_status'      => $validated['cur_status'],
            'created_date'    => Carbon::now(),
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }

    public function show($id)
    {
        $user = AdminLogin::findOrFail($id);

        return view('admin.dashboard.users.show', compact('user'));
    }

    public function edit($id)
    {
        $this->ensureMaster();

        $user = AdminLogin::findOrFail($id);

        return view('admin.dashboard.users.edit', compact('user'));
    }

    public function update(Request $request, $id)
    {
        $this->ensureMaster();

        $user = AdminLogin::findOrFail($id);

        $validated = $request->validate([
            'admin_username' => ['required', 'string', 'max:100', Rule::unique('admin_login', 'admin_username')->ignore($user->id, 'id')],
            'admin_password' => ['nullable', 'string', 'min:6'],
            'user_type'      => ['required', Rule::in(['MASTER', 'ENTRYSTAFF'])],
        ]);

        $updateData = [
            'admin_username' => strtoupper($validated['admin_username']),
            'user_type'       => $validated['user_type'],
        ];

        if (!empty($validated['admin_password'])) {
            $updateData['admin_password'] = Hash::make($validated['admin_password']);
        }

        $user->update($updateData);

        return redirect()->route('admin.users.edit', $user->id)
            ->with('success', 'User updated successfully.');
    }

    public function toggleStatus($id)
    {
        $this->ensureMaster();

        $user = AdminLogin::findOrFail($id);

        $user->cur_status = $user->cur_status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';
        $user->save();

        return redirect()->back()->with('success', 'User status updated successfully.');
    }

    private function ensureMaster()
    {
        if (session('admin_user_type') !== 'MASTER') {
            abort(403, 'Only MASTER can manage users.');
        }
    }
}
