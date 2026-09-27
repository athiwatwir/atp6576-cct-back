<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();
        $action = $request->string('action')->trim()->toString();
        $userId = $request->integer('user_id') ?: null;

        $logs = AuditLog::query()
            ->with('user:id,name,email')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('action', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('ip_address', 'like', "%{$search}%")
                        ->orWhere('entity_type', 'like', "%{$search}%")
                        ->orWhere('request_url', 'like', "%{$search}%");
                });
            })
            ->when($action !== '', fn ($query) => $query->where('action', 'like', $action.'%'))
            ->when($userId, fn ($query) => $query->where('user_id', $userId))
            ->latest('id')
            ->paginate(30)
            ->withQueryString();

        return view('pages.audit-logs.index', [
            'title' => 'Activity Log',
            'logs' => $logs,
            'filters' => [
                'search' => $search,
                'action' => $action,
                'user_id' => $userId,
            ],
        ]);
    }

    public function show(AuditLog $auditLog): View
    {
        $auditLog->load('user:id,name,email');

        return view('pages.audit-logs.show', [
            'title' => 'รายละเอียด Log #'.$auditLog->id,
            'log' => $auditLog,
        ]);
    }
}
