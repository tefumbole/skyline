@extends('beyond.auth.layout')

@section('title', 'Login QR')
@section('auth_subtitle', 'Enter the code from the QR')

@section('auth_body')
<p style="margin:0 0 14px;text-align:center;color:#444;font-size:14px;line-height:1.45;">
    Scan this QR, then type the code before you can enter the system.
</p>
<img src="{{ $qr }}" alt="Login QR" width="168" height="168" style="display:block;width:168px;height:168px;margin:0 auto 10px;background:#fff;border-radius:12px;">
<p style="margin:0 0 14px;text-align:center;font-weight:800;letter-spacing:.18em;font-size:18px;">{{ $code }}</p>
<form method="POST" action="{{ url('/login-qr') }}" autocomplete="off">
    @csrf
    <div class="auth-field">
        <input type="text" name="login_qr" class="auth-input" required autofocus
               inputmode="text" autocapitalize="characters" spellcheck="false"
               placeholder="QR code" autocomplete="one-time-code">
    </div>
    <button type="submit" class="auth-btn">Continue</button>
</form>
@endsection
