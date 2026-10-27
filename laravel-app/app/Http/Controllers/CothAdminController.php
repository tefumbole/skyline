<?php

namespace App\Http\Controllers;

use App\CothAudit;
use App\CothReservation;
use App\CothSection;
use App\CothSetting;
use App\CothSlot;
use App\CothTable;
use App\Event;
use App\Services\Coth\CothCatalog;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CothAdminController extends Controller
{
    public function index(Request $request, CothCatalog $catalog)
    {
        $this->authorizeStaff();
        $query = $this->filtered($request);
        $reservations = $query->with('table.section')->orderBy('event_date')->orderBy('id', 'desc')->paginate(40);
        $date = $request->query('availability', $catalog->eventDates()[0]);

        return view('coth.admin', [
            'reservations' => $reservations,
            'sections' => CothSection::orderBy('sort')->get(),
            'tables' => CothTable::with('section')->orderBy('code')->get(),
            'settings' => CothSetting::query()->first(),
            'audits' => CothAudit::orderBy('id', 'desc')->limit(40)->get(),
            'dates' => $catalog->eventDates(),
            'availabilityDate' => $date,
            'slots' => CothSlot::with('table.section')->whereDate('event_date', $date)->get()->sortBy(function ($slot) {
                return optional($slot->table)->code;
            }),
            'filters' => $request->only(['date', 'section', 'table', 'customer', 'payment']),
            'event' => optional(CothSetting::query()->first())->event_id ? Event::find(CothSetting::query()->first()->event_id) : null,
        ]);
    }

    public function export(Request $request)
    {
        $this->authorizeStaff();
        $rows = $this->filtered($request)->with('table.section')->orderBy('event_date')->get();
        $response = new StreamedResponse(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Reference', 'Date', 'Section', 'Table', 'Seats', 'Customer', 'Phone', 'List', 'Discount', 'Final', 'Paid', 'Balance', 'Reservation', 'Payment']);
            foreach ($rows as $row) {
                fputcsv($out, [
                    $row->reference,
                    optional($row->event_date)->format('Y-m-d'),
                    optional(optional($row->table)->section)->name,
                    optional($row->table)->code,
                    optional($row->table)->capacity,
                    $row->customer_name,
                    $row->phone,
                    $row->list_price,
                    $row->discount_amount,
                    $row->final_price,
                    $row->amount_paid,
                    $row->balance,
                    $row->status,
                    $row->payment_status,
                ]);
            }
            fclose($out);
        });
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="coth-reservations.csv"');

        return $response;
    }

    public function updateSettings(Request $request)
    {
        $this->authorizeStaff();
        $settings = CothSetting::query()->firstOrFail();
        $data = $request->validate([
            'deposit_percent' => 'required|integer|min:1|max:100',
            'discount_percent' => 'required|integer|min:0|max:100',
            'hold_minutes' => 'required|integer|min:5|max:120',
            'discount_dates' => 'nullable|string',
            'event_end_at' => 'nullable|date',
        ]);
        $dates = array_values(array_filter(array_map('trim', preg_split('/[\s,]+/', (string) $data['discount_dates']))));
        $settings->deposit_percent = $data['deposit_percent'];
        $settings->discount_percent = $data['discount_percent'];
        $settings->hold_minutes = $data['hold_minutes'];
        $settings->discount_dates = json_encode($dates);
        $settings->save();
        if (! empty($data['event_end_at']) && $settings->event_id) {
            $event = Event::find($settings->event_id);
            if ($event) {
                $event->event_end_at = $data['event_end_at'];
                $event->save();
            }
        }
        CothAudit::write('settings', 'coth_settings', $settings->id, 'Deposit, discount, hold, or event end updated.');

        return back()->with('message', 'Settings saved. Existing reservations keep their original prices.');
    }

    public function updateTable(Request $request, $id)
    {
        $this->authorizeStaff();
        $table = CothTable::findOrFail($id);
        $data = $request->validate([
            'name' => 'required|string|max:64',
            'capacity' => 'required|integer|min:1|max:30',
            'price' => 'required|integer|min:0',
            'seating_style' => 'required|in:standard,couch',
            'is_active' => 'nullable|boolean',
            'pos_x' => 'nullable|numeric',
            'pos_y' => 'nullable|numeric',
        ]);
        $table->fill([
            'name' => $data['name'],
            'capacity' => $data['capacity'],
            'price' => $data['price'],
            'seating_style' => $data['seating_style'],
            'is_active' => $request->has('is_active') ? 1 : 0,
            'pos_x' => $data['pos_x'] === null || $data['pos_x'] === '' ? null : $data['pos_x'],
            'pos_y' => $data['pos_y'] === null || $data['pos_y'] === '' ? null : $data['pos_y'],
        ]);
        $table->save();
        CothAudit::write('table', 'coth_tables', $table->id, $table->code.' updated.');

        return back()->with('message', $table->code.' saved.');
    }

    public function updateSlot(Request $request, $id)
    {
        $this->authorizeStaff();
        $slot = CothSlot::findOrFail($id);
        if ($slot->status === 'booked') {
            return back()->with('not_permitted', 'A booked date cannot be reopened here.');
        }
        $slot->is_enabled = $request->has('is_enabled') ? 1 : 0;
        if (! $slot->is_enabled && $slot->status === 'open') {
            $slot->status = 'open';
        }
        $slot->save();
        CothAudit::write('slot', 'coth_slots', $slot->id, 'Date '.$slot->event_date->format('Y-m-d').' '.($slot->is_enabled ? 'enabled' : 'disabled').'.');

        return back()->with('message', 'Date updated.');
    }

    protected function filtered(Request $request)
    {
        $query = CothReservation::query();
        if ($request->filled('date')) {
            $query->whereDate('event_date', $request->query('date'));
        }
        if ($request->filled('section')) {
            $ids = CothTable::where('coth_section_id', $request->query('section'))->pluck('id');
            $query->whereIn('coth_table_id', $ids);
        }
        if ($request->filled('table')) {
            $query->where('coth_table_id', $request->query('table'));
        }
        if ($request->filled('customer')) {
            $like = '%'.$request->query('customer').'%';
            $query->where(function ($q) use ($like) {
                $q->where('customer_name', 'like', $like)->orWhere('phone', 'like', $like);
            });
        }
        if ($request->filled('payment')) {
            $query->where('payment_status', $request->query('payment'));
        }

        return $query;
    }

    protected function authorizeStaff()
    {
        $user = auth()->user();
        if (! $user) {
            abort(403);
        }
        if (in_array((int) $user->role_id, [1, 4], true)) {
            return;
        }
        try {
            if ($user->hasPermissionTo('coth-reservations')) {
                return;
            }
        } catch (\Throwable $e) {
        }
        abort(403);
    }
}
