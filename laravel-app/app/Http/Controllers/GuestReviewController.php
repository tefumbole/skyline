<?php

namespace App\Http\Controllers;

use App\Services\BeyondAuthService;
use App\Services\BeyondWasenderService;
use App\Support\CountryDialCodes;
use App\Support\WhatsAppPhone;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class GuestReviewController extends Controller
{
    protected $auth;
    protected $whatsapp;

    public function __construct(BeyondAuthService $auth, BeyondWasenderService $whatsapp)
    {
        $this->auth = $auth;
        $this->whatsapp = $whatsapp;
    }

    public function show(Request $request)
    {
        $countries = CountryDialCodes::names();
        $priority = array_values(array_intersect(['Cameroon', 'Nigeria', 'Rwanda'], $countries));

        return view('beyond.reviews', [
            'reference' => $this->reference($request->query('ref')),
            'saved' => (bool) session('review_saved'),
            'verifyStep' => (bool) $request->session()->get('review_verify_phone'),
            'maskedPhone' => $request->session()->get('review_verify_masked'),
            'priorityCountries' => $priority,
            'otherCountries' => array_values(array_diff($countries, $priority)),
            'countryCodes' => CountryDialCodes::all(),
            'summary' => \App\Support\GuestReviews::summary(),
        ]);
    }

    public function store(Request $request)
    {
        if (! Schema::hasTable('guest_reviews')) {
            return back()->withInput()->withErrors(['review' => 'Reviews are not available yet.']);
        }

        $data = $this->validatedReview($request);
        $phone = $this->resolvePhone($data);

        if ($this->auth->shouldSkipOtp()) {
            $this->persistReview($data, $phone, now());

            return redirect()->route('beyond.reviews')->with('review_saved', true);
        }

        $request->session()->put('review_draft', array_merge($data, ['phone_full' => $phone]));
        $otp = $this->auth->createOtp($phone, 'guest_review');
        $send = $this->whatsapp->sendOtp($phone, $otp['code'], 'review');
        if (! ($send['success'] ?? false)) {
            return back()->withInput()->withErrors(['phone' => $send['error'] ?? 'The code could not be sent.']);
        }

        $request->session()->put('review_verify_phone', $otp['phone']);
        $request->session()->put('review_verify_masked', $this->whatsapp->maskPhone($otp['phone']));

        return redirect()->route('beyond.reviews')->with('success', 'We sent a code to your WhatsApp.');
    }

    public function verify(Request $request)
    {
        $request->validate(['otp' => 'required|string|size:6']);
        $phone = $request->session()->get('review_verify_phone');
        $draft = $request->session()->get('review_draft');
        if (! $phone || ! is_array($draft)) {
            return redirect()->route('beyond.reviews')->withErrors(['otp' => 'That code expired. Please submit the review again.']);
        }

        $result = $this->auth->verifyOtp($phone, $request->otp, 'guest_review');
        if (! ($result['success'] ?? false)) {
            return redirect()->route('beyond.reviews')->withErrors(['otp' => $result['error'] ?? 'That code is not correct.']);
        }

        $this->persistReview($draft, isset($draft['phone_full']) ? $draft['phone_full'] : $phone, now());
        $request->session()->forget(['review_draft', 'review_verify_phone', 'review_verify_masked']);

        return redirect()->route('beyond.reviews')->with('review_saved', true);
    }

    public function resendOtp(Request $request)
    {
        $phone = $request->session()->get('review_verify_phone');
        if (! $phone) {
            return redirect()->route('beyond.reviews')->withErrors(['otp' => 'That code expired. Please submit the review again.']);
        }

        $otp = $this->auth->createOtp($phone, 'guest_review');
        $send = $this->whatsapp->sendOtp($phone, $otp['code'], 'review');
        if (! ($send['success'] ?? false)) {
            return back()->withErrors(['otp' => $send['error'] ?? 'The code could not be sent.']);
        }

        return back()->with('success', 'We sent a new code.');
    }

    protected function validatedReview(Request $request)
    {
        $data = $request->validate([
            'cleanliness' => 'required|integer|min:1|max:5',
            'on_time' => 'required|integer|min:1|max:5',
            'service' => 'required|integer|min:1|max:5',
            'food_quality' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
            'guest_name' => 'nullable|string|max:80',
            'reference' => 'nullable|string|max:80',
            'country' => 'required|string|max:80',
            'country_code' => 'required|string|max:8',
            'phone' => 'required|string|max:20',
            'signature' => 'required|string|max:500000',
        ]);

        if (! preg_match('/^data:image\/(png|jpeg);base64,/', (string) $data['signature'])) {
            throw ValidationException::withMessages(['signature' => ['Please sign the review.']]);
        }

        return $data;
    }

    protected function resolvePhone(array $data)
    {
        try {
            $phone = WhatsAppPhone::combine($data['country_code'] ?? '+237', $data['phone'] ?? '');
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages(['phone' => [$e->getMessage()]]);
        }
        if ($phone === '') {
            throw ValidationException::withMessages(['phone' => ['Enter a WhatsApp number.']]);
        }

        return $phone;
    }

    protected function persistReview(array $data, $phone, $verifiedAt)
    {
        DB::table('guest_reviews')->insert([
            'reference' => $this->reference(isset($data['reference']) ? $data['reference'] : ''),
            'guest_name' => trim((string) (isset($data['guest_name']) ? $data['guest_name'] : '')) ?: null,
            'country' => trim((string) (isset($data['country']) ? $data['country'] : '')) ?: null,
            'phone' => $phone ?: null,
            'phone_verified_at' => $verifiedAt,
            'cleanliness' => (int) $data['cleanliness'],
            'on_time' => (int) $data['on_time'],
            'service' => (int) $data['service'],
            'food_quality' => (int) $data['food_quality'],
            'comment' => trim((string) (isset($data['comment']) ? $data['comment'] : '')) ?: null,
            'signature' => isset($data['signature']) ? $data['signature'] : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function reference($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        return substr(preg_replace('/[^\w\-\.\/]/', '', $value), 0, 80) ?: null;
    }
}
