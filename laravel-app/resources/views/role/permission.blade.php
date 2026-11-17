@extends('layout.main')
@section('content')
@if(session()->has('not_permitted'))
  <div class="alert alert-danger alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ session()->get('not_permitted') }}</div>
@endif
<style>
    .perm-page { max-width: 1100px; margin: 0 auto; padding: 8px 4px 80px; }
    .perm-kicker { margin: 0; color: #8a6230; font-size: 12px; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; }
    .perm-page h1 { margin: 4px 0 0; color: #0c1830; font-size: 32px; font-weight: 700; letter-spacing: -.03em; }
    .perm-lead { margin: 8px 0 0; color: #5c6b82; }
    .perm-toolbar { display: flex; justify-content: space-between; align-items: center; gap: 12px; margin-top: 16px; padding: 12px 16px; border-radius: 16px; background: #0c1830; color: #e1b368; }
    .perm-toolbar label { margin: 0; font-weight: 700; cursor: pointer; }
    .perm-toolbar input { margin-right: 8px; }
    .perm-card { margin-top: 12px; border-radius: 18px; background: #fff; box-shadow: 0 12px 32px rgba(12,24,48,.06); overflow: hidden; }
    .perm-head { display: flex; align-items: center; gap: 10px; padding: 13px 16px; background: #0c1830; color: #e1b368; box-shadow: inset 0 -3px 0 #e1b368; }
    .perm-head label { margin: 0; font-size: 13px; font-weight: 800; letter-spacing: .08em; text-transform: uppercase; cursor: pointer; }
    .perm-head input { margin-right: 8px; }
    .perm-items { display: flex; flex-wrap: wrap; gap: 8px; padding: 14px; }
    .perm-chip { display: inline-flex; align-items: center; gap: 8px; margin: 0; padding: 8px 12px; border-radius: 999px; background: #f3f6fb; color: #0c1830; font-weight: 700; cursor: pointer; }
    .perm-chip.is-on { background: #e1b368; }
    .perm-save { position: sticky; bottom: 12px; margin-top: 16px; }
    .perm-save input { width: 100%; min-height: 48px; border: 0; border-radius: 999px; background: #e1b368; color: #0c1830; font-weight: 800; }
</style>
<section class="perm-page">
    <p class="perm-kicker">Settings</p>
    <h1>{{ $lims_role_data->name }}</h1>
    <p class="perm-lead">These are the side menu items. Tick a gold header to give that whole menu, or tick the items under it.</p>
    {!! Form::open(['route' => 'role.setPermission', 'method' => 'post']) !!}
    <input type="hidden" name="role_id" value="{{ $lims_role_data->id }}">
    <div class="perm-toolbar">
        <label><input type="checkbox" id="perm-all"> All menus</label>
        <span>{{ count(\App\Support\RolePermissionMenu::sections()) }} menus</span>
    </div>
    @foreach(\App\Support\RolePermissionMenu::sections() as $index => $section)
        <article class="perm-card" id="perm-{{ $index }}">
            <div class="perm-head">
                <label>
                    <input type="checkbox" class="section-toggle" data-section="perm-{{ $index }}">
                    {{ $section['title'] }}
                </label>
            </div>
            <div class="perm-items">
                @foreach($section['items'] as $item)
                    @php $on = in_array($item['name'], $all_permission, true); @endphp
                    <label class="perm-chip {{ $on ? 'is-on' : '' }}">
                        <input class="perm" type="checkbox" name="{{ $item['name'] }}" value="1" {{ $on ? 'checked' : '' }}>
                        {{ $item['label'] }}
                    </label>
                @endforeach
            </div>
        </article>
    @endforeach
    <div class="perm-save">
        <input type="submit" value="{{ trans('file.submit') }}">
    </div>
    {!! Form::close() !!}
</section>
<script>
    $("ul#setting").siblings('a').attr('aria-expanded','true');
    $("ul#setting").addClass("show");
    $("ul#setting #role-menu").addClass("active");

    function paint(card) {
        card.find('.perm').each(function () {
            $(this).closest('.perm-chip').toggleClass('is-on', this.checked);
        });
        var boxes = card.find('.perm');
        card.find('.section-toggle').prop('checked', boxes.length > 0 && boxes.filter(':checked').length === boxes.length);
    }
    $('.perm-card').each(function () { paint($(this)); });
    $('.section-toggle').on('change', function () {
        var card = $('#' + $(this).data('section'));
        var on = this.checked;
        card.find('.perm').each(function () {
            var name = this.name;
            $('.perm[name="' + name + '"]').prop('checked', on);
        });
        $('.perm-card').each(function () { paint($(this)); });
    });
    $('.perm').on('change', function () {
        var on = this.checked;
        $('.perm[name="' + this.name + '"]').prop('checked', on);
        $('.perm-card').each(function () { paint($(this)); });
    });
    $('#perm-all').on('change', function () {
        var on = this.checked;
        $('.perm, .section-toggle').prop('checked', on);
        $('.perm-card').each(function () { paint($(this)); });
        $('#perm-all').prop('checked', on);
    });
</script>
@endsection
