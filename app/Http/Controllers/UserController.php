<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\InternalUser;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\AccurateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    protected $accurateService;

    public function __construct(AccurateService $accurateService)
    {
        $this->accurateService = $accurateService;
    }

    // HALAMAN USER MANAGEMENT
    public function index(Request $request)
    {
        $query = User::with(['profile', 'internalUser.area', 'roles'])
            ->whereHas('internalUser');

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('roles', function ($roleQuery) use ($search) {
                        $roleQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $users = $query->latest()->get();
        $totalUsers = User::whereHas('internalUser')->count();
        $activeUsers = User::whereHas('internalUser', function ($q) {
            $q->where('is_active', true);
        })->count();
        $inactiveUsers = $totalUsers - $activeUsers;

        $roles = Role::all();
        $areas = Area::where('is_active', true)->orderBy('sort_order')->get();

        return view('users.index', compact('users', 'totalUsers', 'activeUsers', 'inactiveUsers', 'roles', 'areas'));
    }

    // CREATE USER/STAF INTERNAL
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'birth_date' => 'nullable|date',
            'role_id' => 'required|exists:roles,id',
            'area_id' => 'nullable|exists:areas,id',
            'is_active' => 'boolean',
        ]);
        $accurateId = null;
        try {
            DB::beginTransaction();
            $response = $this->accurateService->saveEmployee([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'position' => Role::findById($validated['role_id'])->name,
            ]);
            $accurateId = $response['r']['id'];

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);
            $profile = UserProfile::create([
                'user_id' => $user->id,
                'phone' => $validated['phone'] ?? null,
                'address' => $validated['address'] ?? null,
                'birth_date' => $validated['birth_date'] ?? null,
            ]);
            InternalUser::create([
                'accurate_id' => $accurateId,
                'user_id' => $user->id,
                'user_profile_id' => $profile->id,
                'area_id' => $validated['area_id'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
            ]);
            $role = Role::findById($validated['role_id']);
            $user->assignRole($role);
            DB::commit();

            return redirect()->route('admin.users.index')
                ->with('success', 'User berhasil ditambahkan');
        } catch (\Exception $e) {
            DB::rollBack();
            if ($accurateId !== null) {
                $this->accurateService->deleteEmployee((int) $accurateId);
            }

            return back()->withErrors(['error' => 'Gagal menambahkan user: '.$e->getMessage()]);
        }
    }

    // UPDATE USER/STAF INTERNAL
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'password' => 'nullable|string|min:8',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'birth_date' => 'nullable|date',
            'role_id' => 'required|exists:roles,id',
            'area_id' => 'nullable|exists:areas,id',
            'is_active' => 'boolean',
        ]);

        try {
            DB::beginTransaction();
            $userData = [
                'name' => $validated['name'],
                'email' => $validated['email'],
            ];
            if (! empty($validated['password'])) {
                $userData['password'] = Hash::make($validated['password']);
            }
            $accurateId = $user->internalUser?->accurate_id;

            if ($accurateId !== null) {
                $this->accurateService->saveEmployee([
                    'id' => (int) $accurateId,
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'position' => Role::findById($validated['role_id'])->name,
                ]);
            }
            $user->update($userData);
            $user->profile->update([
                'phone' => $validated['phone'] ?? null,
                'address' => $validated['address'] ?? null,
                'birth_date' => $validated['birth_date'] ?? null,
            ]);

            // Update internal user
            $user->internalUser->update([
                'area_id' => $validated['area_id'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
            ]);

            // Update role
            $user->syncRoles([]);
            $role = Role::findById($validated['role_id']);
            $user->assignRole($role);

            DB::commit();

            return redirect()->route('admin.users.index')
                ->with('success', 'User berhasil diupdate');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Gagal mengupdate user: '.$e->getMessage()]);
        }
    }

    // HAPUS USER/STAF INTERNAL
    public function destroy(User $user)
    {
        try {
            $accurateId = $user->internalUser?->accurate_id;

            if ($accurateId !== null) {
                $this->accurateService->deleteEmployee((int) $accurateId);
            }
            $user->delete();

            return redirect()->route('admin.users.index')
                ->with('success', 'User berhasil dihapus');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Gagal menghapus user: '.$e->getMessage()]);
        }
    }
}
