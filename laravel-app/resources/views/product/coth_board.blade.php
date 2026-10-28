@php
    $board = $cothBoard;
    $back = url('products?category_id='.$filterCategory->id);
@endphp
<style>
    .coth-board-lead { color: #6b4f2a; margin: 0 0 14px; }
    .coth-sec-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; }
    .coth-sec-card {
        display: flex; flex-direction: column; border: 1px solid #e4d3b0; border-radius: 16px;
        background: #fffdf8; color: #1b2a4a; text-decoration: none; overflow: hidden; min-height: 100%;
    }
    .coth-sec-card:hover { border-color: #C5A059; text-decoration: none; color: #1b2a4a; }
    .coth-sec-photo { height: 170px; background: #24160F; display: flex; align-items: center; justify-content: center; }
    .coth-sec-photo img { width: 100%; height: 100%; object-fit: contain; }
    .coth-sec-body { padding: 14px; text-align: center; }
    .coth-sec-body h4 { margin: 0 0 6px; font-family: Georgia, serif; font-size: 20px; }
    .coth-sec-body span { color: #6b4f2a; font-size: 13px; }
    .coth-table-card { border: 1px solid #e4e7ec; border-radius: 16px; padding: 14px; margin-bottom: 16px; background: #fff; }
    .coth-table-top { display: flex; gap: 16px; align-items: flex-start; flex-wrap: wrap; }
    .coth-table-preview { width: 180px; height: 140px; background: #24160F; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex: 0 0 auto; }
    .coth-table-preview img { max-width: 100%; max-height: 100%; object-fit: contain; }
    .coth-templates { display: flex; gap: 10px; overflow-x: auto; padding: 4px 0 8px; }
    .coth-template {
        flex: 0 0 140px; border: 1px solid #e4d3b0; border-radius: 12px; padding: 6px; text-align: center;
        background: #fffdf8; cursor: pointer; margin: 0;
    }
    .coth-template input { display: none; }
    .coth-template img { width: 100%; height: 78px; object-fit: contain; background: #24160F; border-radius: 8px; }
    .coth-template span { display: block; margin-top: 6px; font-size: 12px; line-height: 1.3; color: #3f2a12; }
    .coth-template.is-on { border-color: #C5A059; box-shadow: 0 0 0 2px #C5A059; }
    .coth-seat { display: flex; gap: 12px; flex-wrap: wrap; align-items: center; margin: 10px 0; }
    @media (max-width: 980px) { .coth-sec-grid { grid-template-columns: 1fr 1fr; } }
    @media (max-width: 640px) { .coth-sec-grid { grid-template-columns: 1fr; } }
</style>

@if(empty($board['section']))
    <p class="coth-board-lead">Choose a section. Each one opens the tables already set up there.</p>
    <div class="coth-sec-grid">
        @foreach($board['cards'] as $card)
            <a class="coth-sec-card" href="{{ $back.'&section='.$card['section']->code }}">
                <span class="coth-sec-photo">
                    @if(!empty($card['image']))<img src="{{ $card['image'] }}" alt="">@endif
                </span>
                <span class="coth-sec-body">
                    <h4>{{ $card['section']->name }}</h4>
                    <span>{{ $card['count'] }} {{ $card['count'] === 1 ? 'table' : 'tables' }}</span>
                </span>
            </a>
        @endforeach
    </div>
@else
    <p class="coth-board-lead">
        <a href="{{ $back }}">&larr; All sections</a>
        · <strong>{{ $board['section']->name }}</strong>
        · Change the name, pick a template, and set how the table is seated.
    </p>
    @foreach($board['tables'] as $table)
        @php
            $visuals = $board['visuals'];
            $return = $back.'&section='.$board['section']->code;
        @endphp
        <form class="coth-table-card" method="POST" action="{{ route('coth.admin.table', $table->id) }}">
            @csrf
            <input type="hidden" name="return_url" value="{{ $return }}">
            <input type="hidden" name="capacity" value="{{ (int) $table->capacity }}">
            <input type="hidden" name="price" value="{{ (int) $table->price }}">
            <input type="hidden" name="max_extra_chairs" value="{{ (int) $table->max_extra_chairs }}">
            <input type="hidden" name="extra_chair_rate" value="{{ $table->extra_chair_rate !== null ? (int) $table->extra_chair_rate : '' }}">
            <input type="hidden" name="extra_chair_furniture_type" value="{{ $table->extra_chair_furniture_type }}">
            <input type="hidden" name="pos_x" value="{{ $table->pos_x }}">
            <input type="hidden" name="pos_y" value="{{ $table->pos_y }}">
            @if($table->allow_extra_chairs)<input type="hidden" name="allow_extra_chairs" value="1">@endif
            @if($table->is_active)<input type="hidden" name="is_active" value="1">@endif
            <div class="coth-table-top">
                <div class="coth-table-preview">
                    <img src="{{ $visuals->assetFor($table) }}" alt="{{ $table->code }}">
                </div>
                <div style="flex:1;min-width:220px;">
                    <div style="font-weight:700;margin-bottom:8px;">{{ $table->code }} · {{ (int) $table->capacity }} seats</div>
                    <label>Table name
                        <input class="form-control" name="name" value="{{ $table->name }}" required maxlength="64">
                    </label>
                    <div class="coth-seat">
                        <label>Seating
                            <select class="form-control coth-style" name="seating_style">
                                <option value="standard" @if($table->seating_style==='standard') selected @endif>Standard chairs</option>
                                <option value="couch" @if($table->seating_style==='couch') selected @endif>Curved couch</option>
                                <option value="high_table" @if($table->seating_style==='high_table') selected @endif>High table</option>
                            </select>
                        </label>
                        <label><input type="checkbox" name="is_city_view" value="1" @if($table->is_city_view) checked @endif> City view</label>
                        <label><input type="checkbox" name="is_high_table" value="1" @if($table->is_high_table) checked @endif> High table</label>
                    </div>
                </div>
            </div>
            <div style="margin-top:12px;font-weight:700;">Templates</div>
            <div class="coth-templates">
                <label class="coth-template {{ $table->visual_preset ? '' : 'is-on' }}" data-style="any">
                    <input type="radio" name="visual_preset" value="" @if(!$table->visual_preset) checked @endif>
                    <img src="{{ $visuals->assetFor($table) }}" alt="">
                    <span>Current default</span>
                </label>
                @foreach($board['presets'] as $preset)
                    @php
                        $fits = empty($preset['applies_to_capacities']) || in_array((int) $table->capacity, $preset['applies_to_capacities'], true);
                        $thumb = $visuals->templateImage($preset['slug'], $table->capacity);
                    @endphp
                    @if($fits && $thumb)
                        <label class="coth-template {{ $table->visual_preset === $preset['slug'] ? 'is-on' : '' }}" data-style="{{ $preset['style'] }}">
                            <input type="radio" name="visual_preset" value="{{ $preset['slug'] }}" @if($table->visual_preset === $preset['slug']) checked @endif>
                            <img src="{{ $thumb }}" alt="">
                            <span>{{ $preset['name'] }}</span>
                        </label>
                    @endif
                @endforeach
            </div>
            <button class="btn btn-primary" type="submit" style="margin-top:8px;">Save {{ $table->code }}</button>
        </form>
    @endforeach
    <script>
    document.querySelectorAll('.coth-table-card').forEach(function (form) {
        function applyStyle() {
            var style = form.querySelector('.coth-style').value;
            form.querySelectorAll('.coth-template').forEach(function (card) {
                var cardStyle = card.getAttribute('data-style');
                var show = cardStyle === 'any' || cardStyle === style;
                card.style.display = show ? '' : 'none';
                var input = card.querySelector('input');
                if (!show && input.checked) {
                    input.checked = false;
                    card.classList.remove('is-on');
                }
            });
        }
        form.querySelector('.coth-style').addEventListener('change', applyStyle);
        form.querySelectorAll('.coth-template input').forEach(function (input) {
            input.addEventListener('change', function () {
                form.querySelectorAll('.coth-template').forEach(function (card) { card.classList.remove('is-on'); });
                input.closest('.coth-template').classList.add('is-on');
            });
        });
        applyStyle();
    });
    </script>
@endif
