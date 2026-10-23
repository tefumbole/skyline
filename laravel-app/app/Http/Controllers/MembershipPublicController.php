<?php

namespace App\Http\Controllers;

use App\Customer;
use App\CustomerGroup;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MembershipPublicController extends Controller
{
    public function apply()
    {
        if (! Schema::hasTable('membership_plans')) {
            return view('beyond.membership.apply', ['ready' => false]);
        }

        return view('beyond.membership.apply', [
            'ready' => true,
            'plans' => DB::table('membership_plans')->where('is_active', 1)->orderBy('sort_order')->get(),
            'promo' => DB::table('membership_promotions')->where('is_enabled', 1)->orderByDesc('id')->first(),
            'agreement' => DB::table('membership_agreements')->where('is_current', 1)->first(),
            'countries' => \App\Support\CountryDialCodes::names(),
        ]);
    }

    public function store(Request $request)
    {
        if (! Schema::hasTable('membership_applications')) {
            return back()->withErrors(['plan_choice' => 'Membership is not available yet.']);
        }

        $data = $request->validate([
            'plan_choice' => 'required|string|max:40',
            'full_name' => 'required|string|max:191',
            'email' => 'nullable|email|max:191',
            'phone' => 'required|string|max:40',
            'id_type' => 'required|in:national_id,passport',
            'id_number' => 'required|string|max:64',
            'id_expires_on' => 'required|date|after:today',
            'nationality' => 'required|string|max:80',
            'id_photo_data' => 'required|string|max:6000000',
            'selfie_data' => 'required|string|max:6000000',
            'accept_agreement' => 'accepted',
            'signature' => 'required|string|max:500000',
        ]);

        if (! preg_match('/^data:image\/(png|jpeg);base64,/', (string) $data['signature'])) {
            return back()->withInput()->withErrors(['signature' => 'Please sign the agreement.']);
        }

        $idPhoto = $this->storeDataUrl($data['id_photo_data'], 'id');
        $selfie = $this->storeDataUrl($data['selfie_data'], 'selfie');
        if (! $idPhoto || ! $selfie) {
            return back()->withInput()->withErrors(['id_photo_data' => 'Take a clear photo of your ID and a selfie.']);
        }

        $promo = null;
        $plan = null;
        $fee = 0;
        if ($data['plan_choice'] === 'promo') {
            $promo = DB::table('membership_promotions')->where('is_enabled', 1)->orderByDesc('id')->first();
            if (! $promo) {
                return back()->withInput()->withErrors(['plan_choice' => 'The free membership is not open.']);
            }
            $plan = DB::table('membership_plans')->where('code', 'monthly')->first();
        } else {
            $plan = DB::table('membership_plans')->where('is_active', 1)->where('id', (int) $data['plan_choice'])->first();
            if (! $plan) {
                return back()->withInput()->withErrors(['plan_choice' => 'Choose a membership plan.']);
            }
            $fee = (float) $plan->fee;
        }

        $agreement = DB::table('membership_agreements')->where('is_current', 1)->first();
        $reference = 'SKY-'.strtoupper(Str::random(8));
        DB::table('membership_applications')->insert([
            'reference' => $reference,
            'plan_id' => $plan ? $plan->id : null,
            'agreement_id' => $agreement ? $agreement->id : null,
            'promotion_id' => $promo ? $promo->id : null,
            'full_name' => trim($data['full_name']),
            'email' => ! empty($data['email']) ? $data['email'] : null,
            'phone' => $data['phone'],
            'id_type' => $data['id_type'],
            'id_number' => $data['id_number'],
            'id_expires_on' => $data['id_expires_on'],
            'nationality' => $data['nationality'],
            'id_photo_path' => $idPhoto,
            'selfie_path' => $selfie,
            'status' => 'PENDING',
            'fee' => $fee,
            'signature_image' => $data['signature'],
            'signed_at' => now(),
            'submitted_ip' => $request->ip(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('membership.confirmation', $reference);
    }

    public function confirmation($reference)
    {
        $application = DB::table('membership_applications')->where('reference', $reference)->first();
        if (! $application) {
            abort(404);
        }
        $plan = $application->plan_id ? DB::table('membership_plans')->where('id', $application->plan_id)->first() : null;

        return view('beyond.membership.confirmation', compact('application', 'plan'));
    }

    public function admin()
    {
        $this->staffOnly();
        $rows = Schema::hasTable('membership_applications')
            ? DB::table('membership_applications')->orderByDesc('id')->limit(200)->get()
            : collect();

        return view('membership.admin', compact('rows'));
    }

    public function approve($id)
    {
        $this->staffOnly();
        $application = DB::table('membership_applications')->where('id', $id)->first();
        if (! $application || $application->status !== 'PENDING') {
            return back()->with('not_permitted', 'That application cannot be approved.');
        }

        $phone = \App\Support\WhatsAppPhone::normalize($application->phone);
        $customer = Customer::where('phone_number', $phone)->first();
        $groupId = optional(DB::table('membership_settings')->where('key', 'member_group_id')->first())->value;
        if (! $groupId) {
            $groupId = optional(CustomerGroup::query()->orderBy('id')->first())->id ?: 1;
        }
        if (! $customer) {
            $customer = Customer::create([
                'customer_group_id' => $groupId,
                'name' => $application->full_name,
                'email' => $application->email,
                'phone_number' => $phone,
                'country' => $application->nationality,
                'address' => 'Membership',
                'city' => 'Bamenda',
                'is_active' => 1,
            ]);
        } else {
            $customer->customer_group_id = $groupId;
            $customer->name = $application->full_name;
            $customer->save();
        }

        $plan = $application->plan_id ? DB::table('membership_plans')->where('id', $application->plan_id)->first() : null;
        $promo = $application->promotion_id ? DB::table('membership_promotions')->where('id', $application->promotion_id)->first() : null;
        $starts = Carbon::now();
        $expires = $promo
            ? $starts->copy()->addDays((int) $promo->free_days)
            : $starts->copy()->addMonths($plan ? (int) $plan->duration_months : 1);

        DB::table('memberships')->insert([
            'number' => 'M-'.strtoupper(Str::random(6)),
            'customer_id' => $customer->id,
            'application_id' => $application->id,
            'plan_id' => $application->plan_id,
            'promotion_id' => $application->promotion_id,
            'status' => 'ACTIVE',
            'is_promotional' => $promo ? 1 : 0,
            'starts_at' => $starts,
            'expires_at' => $expires,
            'qr_token' => Str::random(40),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('membership_applications')->where('id', $application->id)->update([
            'status' => 'APPROVED',
            'customer_id' => $customer->id,
            'updated_at' => now(),
        ]);

        return back()->with('message', 'Membership approved for '.$application->full_name.'.');
    }

    public function reject(Request $request, $id)
    {
        $this->staffOnly();
        DB::table('membership_applications')->where('id', $id)->where('status', 'PENDING')->update([
            'status' => 'REJECTED',
            'admin_note' => $request->input('admin_note'),
            'updated_at' => now(),
        ]);

        return back()->with('message', 'Application rejected.');
    }

    protected function staffOnly()
    {
        $user = auth()->user();
        if (! $user || ! in_array((int) $user->role_id, [1, 2], true)) {
            throw new \Illuminate\Http\Exceptions\HttpResponseException(
                redirect('/')->with('not_permitted', 'You are not allowed to open membership.')
            );
        }
    }

    protected function storeDataUrl($dataUrl, $prefix)
    {
        if (! preg_match('/^data:image\/(png|jpeg);base64,/', (string) $dataUrl, $match)) {
            return null;
        }
        $raw = base64_decode(substr($dataUrl, strpos($dataUrl, ',') + 1), true);
        if (! $raw || strlen($raw) < 20) {
            return null;
        }
        $dir = public_path('uploads/membership');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $name = $prefix.'-'.Str::random(16).'.'.($match[1] === 'jpeg' ? 'jpg' : 'png');
        file_put_contents($dir.'/'.$name, $raw);

        return 'uploads/membership/'.$name;
    }
}
