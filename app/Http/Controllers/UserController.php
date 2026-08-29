<?php

namespace App\Http\Controllers;

use App\Http\Requests\Users\StoreUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use App\Services\Mail\MailService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(
        private readonly MailService $mailService,
    ) {}

    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();
        $status = $request->string('status')->toString();
        $role = $request->string('role')->toString();

        $users = User::query()
            ->with('roles')
            ->whereHas('roles', fn ($query) => $query->whereIn('name', User::BACKEND_ROLES))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($role !== '', function ($query) use ($role) {
                $query->whereHas('roles', fn ($roleQuery) => $roleQuery->where('name', $role));
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('pages.users.index', [
            'title' => 'ผู้ใช้งานระบบ',
            'users' => $users,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'role' => $role,
            ],
            'roles' => $this->backendRoles(),
        ]);
    }

    public function create(): View
    {
        return view('pages.users.create', [
            'title' => 'เพิ่มผู้ใช้งานระบบ',
            'roles' => $this->backendRoles(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => $data['password'],
            'status' => $data['status'],
            'email_verified_at' => now(),
        ]);

        $user->roles()->sync($data['roles']);

        $this->mailService->sendStaffPassword($user, $data['password']);

        return redirect()
            ->route('users.index')
            ->with('success', 'เพิ่มผู้ใช้งานระบบเรียบร้อยแล้ว และส่งอีเมลแจ้งรหัสผ่านแล้ว');
    }

    public function edit(User $user): View|RedirectResponse
    {
        if (! $user->canAccessBackend()) {
            return redirect()
                ->route('users.index')
                ->with('error', 'ไม่พบผู้ใช้งานระบบที่ต้องการแก้ไข');
        }

        $user->load('roles');

        return view('pages.users.edit', [
            'title' => 'แก้ไขผู้ใช้งานระบบ',
            'user' => $user,
            'roles' => $this->backendRoles(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        if (! $user->canAccessBackend()) {
            return redirect()
                ->route('users.index')
                ->with('error', 'ไม่พบผู้ใช้งานระบบที่ต้องการแก้ไข');
        }

        $data = $request->validated();

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'status' => $data['status'],
        ];

        if (! empty($data['password'])) {
            $payload['password'] = $data['password'];
        }

        $user->update($payload);
        $user->roles()->sync($data['roles']);

        if (! empty($data['password'])) {
            $this->mailService->sendStaffPassword($user, $data['password']);
        }

        return redirect()
            ->route('users.index')
            ->with('success', 'บันทึกข้อมูลผู้ใช้งานเรียบร้อยแล้ว');
    }

    public function destroy(User $user): RedirectResponse
    {
        if (! $user->canAccessBackend()) {
            return redirect()
                ->route('users.index')
                ->with('error', 'ไม่พบผู้ใช้งานระบบที่ต้องการลบ');
        }

        if ($user->id === auth()->id()) {
            return redirect()
                ->route('users.index')
                ->with('error', 'ไม่สามารถลบบัญชีที่กำลังใช้งานอยู่ได้');
        }

        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('success', 'ลบผู้ใช้งานระบบเรียบร้อยแล้ว');
    }

    /**
     * @return Collection<int, Role>
     */
    private function backendRoles()
    {
        return Role::query()
            ->whereIn('name', User::BACKEND_ROLES)
            ->orderBy('display_name')
            ->get();
    }
}
