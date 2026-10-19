@php
    $countryNames = \App\Support\CountryDialCodes::names();
    $countrySelected = isset($selected) ? (string) $selected : 'Cameroon';
@endphp
<select name="country" class="selectpicker form-control" data-live-search="true" data-live-search-placeholder="Search country" title="Select Country">
    @foreach($countryNames as $countryName)
        <option value="{{ $countryName }}" {{ $countrySelected === $countryName ? 'selected' : '' }}>{{ $countryName }}</option>
    @endforeach
    @if($countrySelected !== '' && ! in_array($countrySelected, $countryNames, true))
        <option value="{{ $countrySelected }}" selected>{{ $countrySelected }}</option>
    @endif
</select>
