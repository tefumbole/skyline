<?php

namespace App\Http\Controllers;

use App\CothAudit;
use App\CothExtraChairInventory;
use App\CothReservation;
use App\CothSection;
use App\CothSetting;
use App\CothSlot;
use App\CothTable;
use App\Event;
use App\Services\Coth\CothCatalog;
use App\Services\Coth\CothPaymentService;
use App\Services\Coth\CothPricing;
use App\Services\Coth\CothReservationService;
use App\Services\Coth\CothVisualLibrary;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CothAdminController extends Controller
{
    public function index(Request $request, CothCatalog $catalog, CothVisualLibrary $visuals)
    {
        $this->authorizeStaff();
        $query = $this->filtered($request);
        $reservations = $query->with('table.section')->orderBy('event_date')->orderBy('id', 'desc')->paginate(40);
        $date = $request->query('availability', $catalog->eventDates()[0]);
        $chairInventory = CothExtraChairInventory::orderBy('event_date')->get()->keyBy(function ($row) {
            return $row->event_date->toDateString();
        });

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
            'visuals' => $visuals,
            'chairInventory' => $chairInventory,
            'pricing' => app(CothPricing::class),
        ]);
    }

    /**
     * Day sheet for staff: all confirmed tables on a date with section,
     * capacity, extra chairs, total guests and seating notes.
     */
    public function seating(Request $request, CothCatalog $catalog, CothVisualLibrary $visuals)
    {
        $this->authorizeStaff();
        $date = $request->query('date', $catalog->eventDates()[0]);
        if (! in_array($date, $catalog->eventDates(), true)) {
            $date = $catalog->eventDates()[0];
        }
        $rows = CothReservation::with('table.section')
            ->where('status', 'confirmed')
            ->whereDate('event_date', $date)
            ->orderBy('id')
            ->get()
            ->groupBy(function ($r) {
                return optional(optional($r->table)->section)->name ?: 'Unassigned';
            });

        return view('coth.seating', [
            'date' => $date,
            'dates' => $catalog->eventDates(),
            'grouped' => $rows,
            'visuals' => $visuals,
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
        $settings->discount_includes_addons = $request->has('discount_includes_addons') ? 1 : 0;
        $settings->save();
        if (! empty($data['event_end_at']) && $settings->event_id) {
            $event = Event::find($settings->event_id);
            if ($event) {
                $event->event_end_at = $data['event_end_at'];
                $event->save();
            }
        }
        CothAudit::write('settings', 'coth_settings', $settings->id, 'Deposit, discount, hold, or event end updated.');

        return $this->adminBack('message', 'Settings saved. Existing reservations keep their original prices.');
    }

    public function updateTable(Request $request, $id, CothPricing $pricing)
    {
        $this->authorizeStaff();
        $table = CothTable::findOrFail($id);
        $data = $request->validate([
            'name' => 'required|string|max:64',
            'capacity' => 'required|integer|min:1|max:30',
            'price' => 'required|integer|min:0',
            'seating_style' => 'required|in:standard,couch,high_table',
            'visual_preset' => 'nullable|string|max:64',
            'is_city_view' => 'nullable|boolean',
            'is_high_table' => 'nullable|boolean',
            'allow_extra_chairs' => 'nullable|boolean',
            'max_extra_chairs' => 'nullable|integer|min:0|max:50',
            'extra_chair_rate' => 'nullable|integer|min:0',
            'extra_chair_furniture_type' => 'nullable|string|max:64',
            'is_active' => 'nullable|boolean',
            'pos_x' => 'nullable|numeric',
            'pos_y' => 'nullable|numeric',
        ]);

        $cityView = $request->has('is_city_view') ? 1 : 0;
        $highTable = $data['seating_style'] === 'high_table' || $request->has('is_high_table') ? 1 : 0;
        if ($data['seating_style'] === 'couch') {
            $highTable = 0;
        }
        if ($data['seating_style'] === 'high_table') {
            $highTable = 1;
        }

        $extraRate = $data['extra_chair_rate'] !== null && $data['extra_chair_rate'] !== '' ? (int) $data['extra_chair_rate'] : null;
        $allowChairs = $request->has('allow_extra_chairs') ? 1 : 0;

        $library = app(\App\Services\Coth\CothVisualLibrary::class);
        $probe = clone $table;
        $probe->seating_style = $data['seating_style'];
        $probe->is_high_table = $highTable;
        $preset = $library->fixedPreset($probe);
        if (! $preset) {
            $preset = $library->resolvePreset($data['seating_style'], (int) $data['capacity'], $data['visual_preset'] ?: null);
        }
        if ($preset && $preset['style'] !== $data['seating_style']) {
            $preset = null;
        }

        $table->fill([
            'name' => $data['name'],
            'capacity' => $data['capacity'],
            'price' => $data['price'],
            'seating_style' => $data['seating_style'],
            'visual_preset' => $preset['slug'] ?? null,
            'tabletop_material' => null,
            'tabletop_color' => null,
            'upholstery_color' => null,
            'cushion_colors' => null,
            'trim_color' => null,
            'is_city_view' => $cityView,
            'is_high_table' => $highTable,
            'allow_extra_chairs' => $allowChairs,
            'max_extra_chairs' => (int) ($data['max_extra_chairs'] ?? 0),
            'extra_chair_rate' => $extraRate,
            'extra_chair_furniture_type' => $data['extra_chair_furniture_type'] ?: null,
            'is_active' => $request->has('is_active') ? 1 : 0,
            'pos_x' => $data['pos_x'] === null || $data['pos_x'] === '' ? null : $data['pos_x'],
            'pos_y' => $data['pos_y'] === null || $data['pos_y'] === '' ? null : $data['pos_y'],
        ]);
        if ($preset) {
            $table->tabletop_material = $preset['tabletop_material'] ?? null;
            $table->tabletop_color = $preset['tabletop_color'] ?? null;
            $table->upholstery_color = $preset['upholstery_color'] ?? null;
            $table->cushion_colors = $preset['cushion_colors'] ?? null;
            $table->trim_color = $preset['trim_color'] ?? null;
        }

        // If chair rate isn't resolvable (Hall/Pool non-6, no override), force-disable allow.
        if ($allowChairs && $pricing->extraChairRate($table) === null) {
            $table->allow_extra_chairs = 0;
            session()->flash('message', $table->code.' saved. Extra chairs stayed off: set a rate before enabling.');
        }

        $table->save();
        CothAudit::write('table', 'coth_tables', $table->id, $table->code.' updated.');

        $notice = session('message', $table->code.' saved.');
        $return = (string) $request->input('return_url');
        $products = url('products');
        if ($return !== '' && strpos($return, $products) === 0) {
            return redirect($return)->with('edit_message', $notice);
        }

        return $this->adminBack('message', $notice);
    }

    public function updateList(Request $request)
    {
        $this->authorizeStaff();
        $notes = (array) $request->input('note', []);
        $adds = (array) $request->input('add', []);
        $ids = array_unique(array_merge(array_keys($notes), array_keys($adds)));
        $saved = 0;
        foreach ($ids as $id) {
            if (! ctype_digit((string) $id)) {
                continue;
            }
            $table = CothTable::find($id);
            if (! $table) {
                continue;
            }
            $note = isset($notes[$id]) ? trim((string) $notes[$id]) : (string) $table->extra_chair_furniture_type;
            $table->extra_chair_furniture_type = $note !== '' ? mb_substr($note, 0, 64) : null;
            if (Schema::hasColumn('coth_tables', 'list_note')) {
                $add = isset($adds[$id]) ? trim((string) $adds[$id]) : '';
                $table->list_note = $add !== '' ? mb_substr($add, 0, 255) : null;
            }
            $table->save();
            $saved++;
        }

        return $this->adminBack('message', 'List saved. '.$saved.' tables updated.');
    }

    public function updateSection(Request $request, $id)
    {
        $this->authorizeStaff();
        $section = CothSection::findOrFail($id);
        $data = $request->validate([
            'name' => 'required|string|max:80',
            'prefix' => 'required|regex:/^[A-Za-z][A-Za-z0-9]{1,11}$/',
        ]);
        $name = trim($data['name']);
        $prefix = strtoupper($data['prefix']);
        $tables = CothTable::where('coth_section_id', $section->id)->orderBy('id')->get();
        $current = $this->sectionPrefix($tables);
        if ($current === null) {
            $current = strtoupper($section->code);
        }

        $renames = [];
        if ($prefix !== $current) {
            foreach ($tables as $table) {
                $old = (string) $table->code;
                if (stripos($old, $current.'-') !== 0) {
                    continue;
                }
                $renames[$table->id] = [$old, $prefix.substr($old, strlen($current)), $table];
            }
            $nextCodes = [];
            foreach ($renames as $row) {
                $nextCodes[] = $row[1];
            }
            if (count($nextCodes) !== count(array_unique($nextCodes))) {
                return $this->adminBack('not_permitted', 'Those table codes would overlap. Choose a different code.');
            }
            if ($nextCodes && CothTable::whereIn('code', $nextCodes)->whereNotIn('id', array_keys($renames))->exists()) {
                return $this->adminBack('not_permitted', 'Another table already uses that code. Choose a different one.');
            }
        }

        DB::transaction(function () use ($section, $name, $renames) {
            $section->name = $name;
            $section->save();
            foreach ($renames as $row) {
                $old = $row[0];
                $next = $row[1];
                $table = $row[2];
                if ((string) $table->name === $old) {
                    $table->name = $next;
                }
                $table->code = $next;
                $table->save();
            }
        });

        $skipped = $tables->count() - count($renames);
        if ($prefix === $current) {
            $note = $name.' saved. Its '.$tables->count().' tables already use '.$prefix.'.';
        } else {
            $note = $name.' saved. '.count($renames).' tables changed from '.$current.' to '.$prefix.'.';
            if ($prefix !== $current && $skipped > 0) {
                $note .= ' '.$skipped.' kept a custom code.';
            }
        }
        CothAudit::write('section', 'coth_sections', $section->id, $note);

        return $this->adminBack('message', $note);
    }

    public function updateChairInventory(Request $request)
    {
        $this->authorizeStaff();
        $data = $request->validate([
            'event_date' => 'required|date',
            'total_capacity' => 'nullable|integer|min:0',
        ]);
        $row = CothExtraChairInventory::firstOrNew(['event_date' => Carbon::parse($data['event_date'])->toDateString()]);
        $row->total_capacity = $data['total_capacity'] === null || $data['total_capacity'] === ''
            ? null
            : (int) $data['total_capacity'];
        if (! $row->exists) {
            $row->held = 0;
            $row->booked = 0;
        }
        $row->save();
        CothAudit::write('chair_inventory', 'coth_extra_chair_inventory', $row->id,
            'Chair cap for '.$row->event_date->format('Y-m-d').' set to '.($row->total_capacity === null ? 'unlimited' : $row->total_capacity).'.');

        return $this->adminBack('message', 'Chair inventory updated.');
    }

    public function amendChairs(Request $request, $id, CothReservationService $reservations, CothPricing $pricing, CothPaymentService $payments)
    {
        $this->authorizeStaff();
        $data = $request->validate([
            'extra_chairs_qty_new' => 'required|integer|min:0|max:50',
            'payment_mode' => 'required|in:manual,gateway,none',
            'payment_amount' => 'nullable|integer|min:0',
            'method' => 'nullable|in:cash,momo,card_terminal',
            'gateway_method' => 'nullable|in:momo,card',
            'note' => 'nullable|string|max:255',
        ]);

        $reservation = CothReservation::with('table.section')->findOrFail($id);
        if ($reservation->status !== 'confirmed') {
            return $this->adminBack('not_permitted', 'Only confirmed reservations can be amended here.');
        }
        $table = $reservation->table;
        if (! $table) {
            return $this->adminBack('not_permitted', 'Table missing — cannot amend.');
        }

        $before = (int) $reservation->extra_chairs_qty;
        $after = (int) $data['extra_chairs_qty_new'];
        if ($after > 0) {
            if (! (int) $table->allow_extra_chairs) {
                return $this->adminBack('not_permitted', 'Extra chairs are not enabled for '.$table->code.'.');
            }
            if ($after > (int) $table->max_extra_chairs) {
                return $this->adminBack('not_permitted', 'That exceeds max_extra_chairs for '.$table->code.'.');
            }
            if ($pricing->extraChairRate($table) === null) {
                return $this->adminBack('not_permitted', 'Extra chairs for '.$table->code.' are not configured yet.');
            }
        }
        $delta = $after - $before;

        $settings = $reservations->settings();
        $quote = $pricing->quote($table->price, $reservation->event_date, $settings, $table, $after);

        // Shared pool availability for a positive delta.
        if ($delta > 0) {
            $avail = $reservations->sharedChairsAvailable($reservation->event_date);
            if ($avail !== null && $delta > $avail) {
                return $this->adminBack('not_permitted', 'Only '.$avail.' extra chair(s) remain in the shared pool for '.$reservation->event_date->format('Y-m-d').'.');
            }
        }

        $gatewayUrl = null;
        DB::transaction(function () use ($reservation, $table, $data, $delta, $after, $quote, $pricing) {
            // Adjust shared pool "booked" count atomically.
            if ($delta !== 0) {
                $row = CothExtraChairInventory::whereDate('event_date', $reservation->event_date->toDateString())->lockForUpdate()->first();
                if ($row) {
                    if ($delta > 0 && $row->total_capacity !== null) {
                        $avail = max(0, (int) $row->total_capacity - (int) $row->held - (int) $row->booked);
                        if ($delta > $avail) {
                            throw new \RuntimeException('Only '.$avail.' extra chair(s) remain for this date.');
                        }
                    }
                    $row->booked = max(0, (int) $row->booked + $delta);
                    $row->save();
                }
            }

            $reservation->extra_chairs_qty = $after;
            $reservation->extra_chair_rate = $after > 0 ? $pricing->extraChairRate($table) : null;
            $reservation->extra_chairs_amount = $quote['extra_chairs_amount'];
            $reservation->booking_total = $quote['booking_total'];
            $reservation->balance = max(0, (int) $reservation->booking_total - (int) $reservation->amount_paid);
            $reservation->save();
        });

        $noteBase = 'Chair qty '.$before.' → '.$after.' by staff #'.(auth()->id() ?: '?').'.';
        if (! empty($data['note'])) {
            $noteBase .= ' Note: '.$data['note'];
        }

        if (($data['payment_mode'] ?? '') === 'manual' && ! empty($data['payment_amount'])) {
            $paidDelta = (int) $data['payment_amount'];
            $reservation->amount_paid = (int) $reservation->amount_paid + $paidDelta;
            $reservation->balance = max(0, (int) $reservation->booking_total - (int) $reservation->amount_paid);
            $reservation->payment_status = $reservation->balance <= 0 ? 'paid' : 'partial';
            $reservation->save();

            if (\Illuminate\Support\Facades\Schema::hasTable('payments') && $reservation->sale_id) {
                try {
                    $payment = new \App\Payment();
                    $payment->sale_id = $reservation->sale_id;
                    $accountId = \Illuminate\Support\Facades\Schema::hasTable('accounts') ? optional(\App\Account::query()->orderBy('id')->first())->id : null;
                    $payment->account_id = $accountId ?: 1;
                    $payment->payment_reference = 'coth-amend-'.date('YmdHis');
                    $payment->amount = $paidDelta;
                    $payment->change = 0;
                    $method = $data['method'] ?? 'cash';
                    $payment->paying_method = $method === 'card_terminal' ? 'Credit Card' : ($method === 'momo' ? 'Momo/Orange' : 'Cash');
                    $payment->payment_note = 'coth-amend by #'.(auth()->id() ?: '?');
                    $payment->save();
                } catch (\Throwable $e) {
                    \Log::warning('coth amend payment row: '.$e->getMessage());
                }
            }
            $noteBase .= ' Manual payment '.number_format($paidDelta).' FCFA ('.($data['method'] ?? 'cash').').';
        } elseif (($data['payment_mode'] ?? '') === 'gateway') {
            $deltaDue = max(0, (int) $reservation->booking_total - (int) $reservation->amount_paid);
            if ($deltaDue > 0) {
                $reservation->amount_due = $deltaDue;
                $reservation->pay_choice = 'full';
                $reservation->save();
                try {
                    $gatewayUrl = $payments->start($reservation, $data['gateway_method'] ?? 'momo');
                } catch (\Throwable $e) {
                    $noteBase .= ' Gateway start failed: '.$e->getMessage();
                }
            }
        }

        CothAudit::write('amend', 'coth_reservations', $reservation->id, $noteBase);

        if ($gatewayUrl) {
            return redirect($gatewayUrl);
        }

        return $this->adminBack('message', 'Reservation '.$reservation->reference.' amended ('.$before.' → '.$after.' chair(s)).');
    }

    public function updateSlot(Request $request, $id)
    {
        $this->authorizeStaff();
        $slot = CothSlot::findOrFail($id);
        if ($slot->status === 'booked') {
            return $this->adminBack('not_permitted', 'A booked date cannot be reopened here.');
        }
        $slot->is_enabled = $request->has('is_enabled') ? 1 : 0;
        if (! $slot->is_enabled && $slot->status === 'open') {
            $slot->status = 'open';
        }
        $slot->save();
        CothAudit::write('slot', 'coth_slots', $slot->id, 'Date '.$slot->event_date->format('Y-m-d').' '.($slot->is_enabled ? 'enabled' : 'disabled').'.');

        return $this->adminBack('message', 'Date updated.');
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

    protected function adminBack($key, $message)
    {
        $allowed = ['tables', 'list', 'locations', 'reservations', 'availability', 'chairs', 'rules', 'audit'];
        $tab = (string) request()->input('tab', 'tables');
        if (! in_array($tab, $allowed, true)) {
            $tab = 'tables';
        }
        $query = ['tab' => $tab];
        foreach (['date', 'section', 'customer', 'payment', 'availability'] as $name) {
            $value = request()->input($name);
            if ($value !== null && $value !== '') {
                $query[$name] = $value;
            }
        }

        return redirect()->route('coth.admin', $query)->with($key, $message);
    }

    protected function sectionPrefix($tables)
    {
        $counts = [];
        foreach ($tables as $table) {
            if (preg_match('/^([A-Za-z0-9]+)-/', (string) $table->code, $match)) {
                $prefix = strtoupper($match[1]);
                $counts[$prefix] = isset($counts[$prefix]) ? $counts[$prefix] + 1 : 1;
            }
        }
        if (! $counts) {
            return null;
        }
        arsort($counts);

        return array_key_first($counts);
    }

    protected function authorizeStaff()
    {
        $user = auth()->user();
        if (! $user) {
            abort(403);
        }
        if ($user->hasFullAdminAccess() || in_array((int) $user->role_id, [2, 4], true)) {
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
