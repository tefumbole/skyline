<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Table list</title>
    @php $invoiceLetterheadOnce = true; @endphp
    @include('pdf.partials._invoice_styles')
    <style>
        h1 { margin: 0 0 2px; font-size: 16px; color: #0c1830; }
        .meta { margin: 0 0 10px; color: #5c6b82; font-size: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 6px 8px; background: #0c1830; color: #fff; font-size: 9px; letter-spacing: .04em; text-transform: uppercase; }
        td { padding: 6px 8px; border-bottom: 1px solid #e6ebf2; font-size: 10px; vertical-align: top; }
        tr:nth-child(even) td { background: #f7f9fc; }
        .num { text-align: right; white-space: nowrap; }
    </style>
</head>
<body>
@include('pdf.partials._invoice_open')
<h1>Table list</h1>
<p class="meta">{{ $sectionName }} · {{ $printedAt }}</p>
<table>
    <thead>
        <tr>
            <th>Table</th>
            <th>Section</th>
            <th class="num">Seats</th>
            <th class="num">Price (FCFA)</th>
            <th>Notes</th>
            <th>Add</th>
        </tr>
    </thead>
    <tbody>
    @forelse($tables as $table)
        <tr>
            <td>{{ $table->code }}</td>
            <td>{{ optional($table->section)->name }}</td>
            <td class="num">{{ (int) $table->capacity }}</td>
            <td class="num">{{ number_format((int) $table->price) }}</td>
            <td>{{ $table->extra_chair_furniture_type }}</td>
            <td>{{ $table->list_note }}</td>
        </tr>
    @empty
        <tr><td colspan="6">No tables in this section.</td></tr>
    @endforelse
    </tbody>
</table>
@include('pdf.partials._invoice_close')
</body>
</html>
