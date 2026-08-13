<?php

namespace App\Http\Controllers;

use App\Services\ReturnService;
use Illuminate\Http\Request;
use RuntimeException;

class AdminReturnController extends Controller
{
    protected ReturnService $returnService;

    public function __construct(ReturnService $returnService)
    {
        $this->returnService = $returnService;
    }

    public function index()
    {
        $returns = $this->returnService->getReturnsForAdmin();

        return view('admin.returns.index', compact('returns'));
    }

    public function show(int $returnId)
    {
        $return = $this->returnService->getReturnForAdmin($returnId);

        if (!$return) {
            return redirect()
                ->route('admin.returns.index')
                ->with('error', 'Return request not found.');
        }

        return view('admin.returns.show', compact('return'));
    }

    public function process(int $returnId)
    {
        $return = $this->returnService->getReturnForAdmin($returnId);

        if (!$return) {
            return redirect()
                ->route('admin.returns.index')
                ->with('error', 'Return request not found.');
        }

        return view('admin.returns.process', compact('return'));
    }

    public function update(Request $request, int $returnId)
    {
        $adminId = (int) $request->session()->get('admin_id');
        $validated = $request->validate([
            'action' => ['required', 'string'],
            'admin_note' => ['nullable', 'string', 'max:1000'],
            'sellable_stock' => ['nullable', 'boolean'],
        ]);

        try {
            $adminNote = $validated['admin_note'] ?? null;

            if ($validated['action'] === 'approve') {
                $this->returnService->approveByAdmin($returnId, $adminId, $adminNote);
            } elseif ($validated['action'] === 'reject') {
                $this->returnService->rejectByAdmin($returnId, $adminId, $adminNote);
            } elseif ($validated['action'] === 'schedule_pickup') {
                $this->returnService->schedulePickupByAdmin($returnId, $adminId);
            } elseif ($validated['action'] === 'mark_received') {
                $this->returnService->markReceivedByAdmin($returnId, $adminId);
            } elseif ($validated['action'] === 'inspection_failed') {
                $this->returnService->failInspectionByAdmin($returnId, $adminId, $adminNote);
            } elseif ($validated['action'] === 'inspection_passed') {
                $this->returnService->passInspectionAndRefundByAdmin(
                    $returnId,
                    $adminId,
                    (bool) ($validated['sellable_stock'] ?? false)
                );
            } else {
                throw new RuntimeException('Invalid return action.');
            }
        } catch (RuntimeException $exception) {
            return redirect()
                ->back()
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('admin.returns.process', $returnId)
            ->with('success', 'Return request updated successfully.');
    }
}
