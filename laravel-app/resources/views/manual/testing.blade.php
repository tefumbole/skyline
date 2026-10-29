@extends('layout.main')
@section('content')
@php
    $passed = $saved->where('result', 'pass')->count();
    $failed = $saved->where('result', 'fail')->count();
    $shots = [
        'dates' => 'dates.jpg',
        'services' => 'services.jpg',
        'experience' => 'experience.jpg',
        'tables' => 'tables.jpg',
        'reserve' => 'reserve.jpg',
        'admin-tables' => 'admin-tables.jpg',
        'locations' => 'locations.jpg',
    ];
@endphp
<style>
    .man { color: #1f2a44; }
    .man-hero {
        background: #fff; border: 1px solid #e6ebf3; border-radius: 16px;
        box-shadow: 0 8px 24px rgba(12, 24, 48, 0.06); padding: 24px; margin-bottom: 16px;
    }
    .man-kicker { letter-spacing: .16em; text-transform: uppercase; color: #8a7424; font-size: 12px; font-weight: 700; margin: 0 0 6px; }
    .man h1 { font-weight: 650; font-size: 30px; margin: 0; color: #0c1830; }
    .man-lead { color: #6f7b91; margin: 8px 0 0; max-width: 720px; }
    .man-progress { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 16px; align-items: center; }
    .man-pill { border-radius: 999px; padding: 6px 12px; font-size: 13px; font-weight: 700; background: #f3f6fb; color: #1f2a44; }
    .man-pill.pass { background: #e8f7ee; color: #146c43; }
    .man-pill.fail { background: #fdecec; color: #b42318; }
    .man-bar { flex: 1; min-width: 160px; height: 8px; background: #e6ebf3; border-radius: 999px; overflow: hidden; }
    .man-bar > span { display: block; height: 100%; background: #0c1830; width: 0; }
    .man-nav { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 18px; }
    .man-nav a {
        border: 2px solid #C5A059; color: #3f2a12; background: #fffaf3;
        border-radius: 999px; padding: 6px 12px; font-size: 13px; font-weight: 600; text-decoration: none;
    }
    .man-nav a:hover { background: #f6ead0; color: #1a1208; text-decoration: none; }
    .man-sec { margin-bottom: 22px; }
    .man-sec h2 { font-size: 22px; color: #0c1830; margin: 0 0 4px; }
    .man-sec > p { color: #6f7b91; margin: 0 0 12px; }
    .man-item {
        background: #fff; border: 1px solid #e6ebf3; border-radius: 16px;
        padding: 16px; margin-bottom: 12px;
    }
    .man-item.is-pass { border-color: #8fd4ae; }
    .man-item.is-fail { border-color: #f0b4b4; }
    .man-item h3 { font-size: 17px; margin: 0 0 8px; color: #0c1830; }
    .man-steps { margin: 0 0 8px; padding-left: 18px; color: #1f2a44; }
    .man-expect { margin: 0 0 12px; color: #3f2a12; background: #fffaf3; border-radius: 10px; padding: 10px 12px; }
    .man-shot { display: block; width: 100%; max-width: 720px; border-radius: 12px; border: 1px solid #e6ebf3; margin: 0 0 12px; }
    .man-opts { display: flex; flex-wrap: wrap; gap: 14px; align-items: center; }
    .man-opt {
        display: inline-flex; align-items: center; gap: 8px; margin: 0;
        font-weight: 650; color: #1f2a44; cursor: pointer;
    }
    .man-opt input { width: 18px; height: 18px; }
    .man-state { font-size: 12px; color: #6f7b91; min-height: 16px; }
</style>
<section class="container-fluid man">
    <div class="man-hero">
        <p class="man-kicker">Skyline Hotel</p>
        <h1>Testing guide</h1>
        <p class="man-lead">These checks match the menus on your account. Mark each one successful or not successful. Your marks stay with this login.</p>
        @if(!empty($shareUrl))
            <p class="man-lead">The full test for a secretary is <a href="{{ $shareUrl }}">{{ $shareUrl }}</a>. She does not need a login.</p>
            @foreach($reports as $report)
                <p class="man-lead"><a href="{{ route('admin.help.report', $report->id) }}">{{ $report->tester_name }}</a> · {{ $report->created_at }}</p>
            @endforeach
        @endif
        <div class="man-progress">
            <span class="man-pill pass" id="man-pass">{{ $passed }} successful</span>
            <span class="man-pill fail" id="man-fail">{{ $failed }} not successful</span>
            <span class="man-pill" id="man-left">{{ $total - $passed - $failed }} still open</span>
            <div class="man-bar" aria-hidden="true"><span id="man-bar" style="width: {{ $total ? round((($passed + $failed) / $total) * 100) : 0 }}%"></span></div>
        </div>
    </div>

    <nav class="man-nav" aria-label="Guide sections">
        @foreach ($sections as $section)
            <a href="#man-{{ $section['id'] }}">{{ $section['title'] }}</a>
        @endforeach
    </nav>

    @foreach ($sections as $section)
        <section class="man-sec" id="man-{{ $section['id'] }}">
            <h2>{{ $section['title'] }}</h2>
            <p>{{ $section['intro'] }}</p>
            @foreach ($section['items'] as $item)
                @php $result = optional($saved->get($item['key']))->result; @endphp
                <article class="man-item {{ $result === 'pass' ? 'is-pass' : '' }} {{ $result === 'fail' ? 'is-fail' : '' }}" data-key="{{ $item['key'] }}" data-result="{{ $result }}">
                    <h3>{{ $item['title'] }}</h3>
                    <ol class="man-steps">
                        @foreach ($item['steps'] as $step)
                            <li>{{ $step }}</li>
                        @endforeach
                    </ol>
                    <p class="man-expect"><strong>Pass when:</strong> {{ $item['expect'] }}</p>
                    @if (! empty($item['shot']) && isset($shots[$item['shot']]))
                        <img class="man-shot" src="{{ asset('public/images/manual/'.$shots[$item['shot']]) }}" alt="{{ $item['title'] }}">
                    @endif
                    <div class="man-opts">
                        <label class="man-opt">
                            <input class="man-check" type="checkbox" value="pass" @if($result === 'pass') checked @endif>
                            Successful
                        </label>
                        <label class="man-opt">
                            <input class="man-check" type="checkbox" value="fail" @if($result === 'fail') checked @endif>
                            Not successful
                        </label>
                        <span class="man-state"></span>
                    </div>
                </article>
            @endforeach
        </section>
    @endforeach
</section>
<script>
(function () {
    var total = {{ (int) $total }};
    var token = document.querySelector('meta[name="csrf-token"]');
    token = token ? token.getAttribute('content') : '{{ csrf_token() }}';
    function count() {
        var pass = 0, fail = 0;
        document.querySelectorAll('.man-item').forEach(function (card) {
            if (card.getAttribute('data-result') === 'pass') pass++;
            if (card.getAttribute('data-result') === 'fail') fail++;
        });
        document.getElementById('man-pass').textContent = pass + ' successful';
        document.getElementById('man-fail').textContent = fail + ' not successful';
        document.getElementById('man-left').textContent = (total - pass - fail) + ' still open';
        document.getElementById('man-bar').style.width = (total ? Math.round(((pass + fail) / total) * 100) : 0) + '%';
    }
    document.querySelectorAll('.man-check').forEach(function (box) {
        box.addEventListener('change', function () {
            var card = box.closest('.man-item');
            var state = card.querySelector('.man-state');
            var result = '';
            if (box.checked) {
                card.querySelectorAll('.man-check').forEach(function (other) {
                    if (other !== box) other.checked = false;
                });
                result = box.value;
            }
            card.setAttribute('data-result', result);
            card.classList.toggle('is-pass', result === 'pass');
            card.classList.toggle('is-fail', result === 'fail');
            count();
            state.textContent = 'Saving…';
            fetch('{{ route('admin.help.result') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ item_key: card.getAttribute('data-key'), result: result })
            }).then(function (res) {
                state.textContent = res.ok ? 'Saved' : 'Could not save';
            }).catch(function () {
                state.textContent = 'Could not save';
            });
        });
    });
})();
</script>
@endsection
