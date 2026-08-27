<?php

namespace App\Http\Controllers;

use App\Services\DeliveryAssignmentService;
use App\Services\DeliveryService;
use App\Services\DeliveryStatusService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminDeliveryController extends Controller
{
    protected DeliveryService $deliveryService;
    protected DeliveryAssignmentService $deliveryAssignmentService;
    protected DeliveryStatusService $deliveryStatusService;

    public function __construct(
        DeliveryService $deliveryService,
        DeliveryAssignmentService $deliveryAssignmentService,
        DeliveryStatusService $deliveryStatusService
    ) {
        $this->deliveryService = $deliveryService;
        $this->deliveryAssignmentService = $deliveryAssignmentService;
        $this->deliveryStatusService = $deliveryStatusService;
    }

    public function dashboard()
    {
        $stats = $this->deliveryService->getDeliveryStats();

        return view('admin.deliveries.dashboard', compact('stats'));
    }

    public function personsIndex(Request $request)
    {
        $search = trim((string) $request->query('search'));
        $deliveryPersons = $this->deliveryService->getAllDeliveryPersons($search);

        return view('admin.deliveries.persons_index', compact('deliveryPersons', 'search'));
    }

    public function personsCreate()
    {
        return view('admin.deliveries.persons_create');
    }

    public function personsStore(Request $request)
    {
        $this->cleanDeliveryPersonInput($request);

        $validatedData = $request->validate($this->deliveryPersonRules());

        $adminId = session('admin_id');
        if (!$adminId) {
            return redirect()->route('admin.login')
                ->with('error', 'Please login to continue.');
        }

        $this->deliveryService->createDeliveryPerson($validatedData, $adminId);

        return redirect()->route('admin.deliveries.persons.index')
            ->with('success', 'Delivery person created successfully');
    }

    public function personsShow(int $id)
    {
        $deliveryPerson = $this->deliveryService->findDeliveryPersonForShow($id);

        return view('admin.deliveries.persons_show', compact('deliveryPerson'));
    }

    public function personsEdit(int $id)
    {
        $deliveryPerson = $this->deliveryService->findDeliveryPersonForEdit($id);

        return view('admin.deliveries.persons_edit', compact('deliveryPerson'));
    }

    public function personsUpdate(Request $request, int $id)
    {
        $this->cleanDeliveryPersonInput($request);

        $validatedData = $request->validate($this->deliveryPersonRules($id));

        $adminId = session('admin_id');
        if (!$adminId) {
            return redirect()->route('admin.login')
                ->with('error', 'Please login to continue.');
        }

        $this->deliveryService->updateDeliveryPerson($id, $validatedData, $adminId);

        return redirect()->route('admin.deliveries.persons.edit', $id)
            ->with('success', 'Delivery person updated successfully');
    }

    public function unassigned()
    {
        $orders = $this->deliveryService->getUnassignedDeliveries();

        return view('admin.deliveries.unassigned', compact('orders'));
    }

    public function assign(int $orderId)
    {
        $order = \App\Models\Order::query()
            ->with(['customer', 'address', 'statuses', 'payments', 'items.product'])
            ->where('id', $orderId)
            ->first();

        if (!$order) {
            return redirect()->route('admin.deliveries.unassigned')
                ->with('error', 'Order not found.');
        }

        $availablePersons = $this->deliveryService->getAvailableDeliveryPersons();
        $existingDelivery = $this->deliveryService->getDeliveryByOrder($orderId);

        return view('admin.deliveries.assign', compact('order', 'availablePersons', 'existingDelivery'));
    }

    public function assignStore(Request $request, int $orderId)
    {
        $validated = $request->validate([
            'delivery_person_id' => ['required', 'integer', 'exists:delivery_persons,id'],
        ]);

        $adminId = session('admin_id');
        if (!$adminId) {
            return redirect()->route('admin.login')
                ->with('error', 'Please login to continue.');
        }

        try {
            $this->deliveryAssignmentService->assignDeliveryPerson(
                $orderId,
                $validated['delivery_person_id'],
                $adminId
            );
        } catch (\Exception $exception) {
            return redirect()->route('admin.deliveries.assign', $orderId)
                ->with('error', $exception->getMessage());
        }

        return redirect()->route('admin.deliveries.unassigned')
            ->with('success', 'Delivery person assigned successfully');
    }

    public function active()
    {
        $deliveries = $this->deliveryService->getActiveDeliveries();

        return view('admin.deliveries.active', compact('deliveries'));
    }

    public function show(int $id)
    {
        $delivery = $this->deliveryService->getDeliveryById($id);

        if (!$delivery) {
            return redirect()->route('admin.deliveries.dashboard')
                ->with('error', 'Delivery not found.');
        }

        $timeline = $this->buildDeliveryTimeline($delivery);
        $allowedStatuses = $this->deliveryStatusService->getAllowedNextStatuses($delivery->status);

        return view('admin.deliveries.show', compact('delivery', 'timeline', 'allowedStatuses'));
    }

    public function updateStatus(Request $request, int $deliveryId)
    {
        $validated = $request->validate([
            'delivery_status' => ['required', 'string'],
        ]);

        $adminId = session('admin_id');
        if (!$adminId) {
            return redirect()->route('admin.login')
                ->with('error', 'Please login to continue.');
        }

        try {
            $this->deliveryStatusService->changeStatus(
                $deliveryId,
                $validated['delivery_status'],
                $adminId
            );
        } catch (\Exception $exception) {
            return redirect()->route('admin.deliveries.show', $deliveryId)
                ->with('error', $exception->getMessage());
        }

        return redirect()->route('admin.deliveries.show', $deliveryId)
            ->with('success', 'Delivery status updated successfully');
    }

    public function history(Request $request)
    {
        $search = trim((string) $request->query('search'));
        $status = trim((string) $request->query('status'));
        $deliveryPersonId = $request->query('delivery_person_id') ? (int) $request->query('delivery_person_id') : null;
        $dateFrom = trim((string) $request->query('date_from'));
        $dateTo = trim((string) $request->query('date_to'));

        $deliveries = $this->deliveryService->getDeliveryHistoryFiltered(
            $search ?: null,
            $status ?: null,
            $deliveryPersonId,
            $dateFrom ?: null,
            $dateTo ?: null
        );

        $deliveryPersons = \App\Models\DeliveryPerson::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return view('admin.deliveries.history', compact('deliveries', 'search', 'status', 'deliveryPersonId', 'dateFrom', 'dateTo', 'deliveryPersons'));
    }

    private function cleanDeliveryPersonInput(Request $request): void
    {
        $email = strtolower(trim((string) ($request->email ?? '')));

        $request->merge([
            'name' => Str::title(trim((string) $request->name)),
            'mobile' => preg_replace('/\D+/', '', (string) $request->mobile),
            'email' => $email === '' ? null : $email,
            'vehicle_number' => strtoupper(trim((string) ($request->vehicle_number ?? ''))),
        ]);
    }

    private function deliveryPersonRules($deliveryPersonId = null): array
    {
        return [
            'name' => 'required|string|max:120',
            'mobile' => [
                'required',
                'digits_between:10,15',
                Rule::unique('delivery_persons', 'mobile')->ignore($deliveryPersonId),
            ],
            'email' => 'nullable|email|max:255',
            'password' => $deliveryPersonId ? 'nullable|min:6' : 'required|min:6',
            'vehicle_type' => 'nullable|string|max:50',
            'vehicle_number' => 'nullable|string|max:20',
            'status' => 'required|string|in:active,inactive',
            'availability_status' => 'required|string|in:available,busy,offline',
        ];
    }

    private function buildDeliveryTimeline($delivery): array
    {
        $statuses = [
            'not_assigned' => 'Not Assigned',
            'assigned' => 'Assigned',
            'accepted' => 'Accepted',
            'picked_up' => 'Picked Up',
            'out_for_delivery' => 'Out for Delivery',
            'delivered' => 'Delivered',
        ];

        $timestampMap = [
            'not_assigned' => null,
            'assigned' => 'assigned_at',
            'accepted' => 'accepted_at',
            'picked_up' => 'picked_up_at',
            'out_for_delivery' => 'out_for_delivery_at',
            'delivered' => 'delivered_at',
        ];

        $currentIndex = null;
        $statusKeys = array_keys($statuses);
        foreach ($statusKeys as $index => $status) {
            if ($status === $delivery->status) {
                $currentIndex = $index;
                break;
            }
        }

        $timeline = [];
        foreach ($statuses as $key => $label) {
            $keyIndex = array_search($key, $statusKeys, true);
            $isCompleted = $currentIndex !== null && $keyIndex <= $currentIndex;
            $isCurrent = $key === $delivery->status;

            $timestampField = $timestampMap[$key];
            $timestamp = null;
            if ($timestampField && $delivery->{$timestampField}) {
                $timestamp = $delivery->{$timestampField};
            }

            $timeline[] = [
                'key' => $key,
                'label' => $label,
                'is_completed' => $isCompleted,
                'is_current' => $isCurrent,
                'timestamp' => $timestamp,
            ];
        }

        return $timeline;
    }
}
