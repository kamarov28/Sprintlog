<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $manifest->manifest_number }}</title>
    <style>
        body {
            color: #111;
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            line-height: 1.45;
        }

        h1 {
            margin: 0 0 4px;
            font-size: 22px;
            letter-spacing: 2px;
        }

        .meta {
            display: table;
            width: 100%;
            margin: 18px 0;
        }

        .meta > div {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }

        .label {
            color: #555;
            font-size: 9px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
        }

        th,
        td {
            border-bottom: 1px solid #ddd;
            padding: 7px 5px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #f3f3f3;
            font-size: 9px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }

        .signatures {
            display: table;
            width: 100%;
            margin-top: 42px;
        }

        .signatures > div {
            display: table-cell;
            width: 50%;
            text-align: center;
        }

        .line {
            margin: 42px auto 0;
            width: 180px;
            border-top: 1px solid #111;
            padding-top: 6px;
        }
    </style>
</head>
<body>
    <h1>SPRINTLOG MANIFEST</h1>
    <div>{{ $manifest->manifest_number }}</div>

    <div class="meta">
        <div>
            <div class="label">Origin Hub</div>
            <strong>{{ optional($manifest->originBranch)->name }}</strong><br>
            {{ optional($manifest->originBranch)->city }}
        </div>
        <div>
            <div class="label">Destination Hub</div>
            <strong>{{ optional($manifest->destinationBranch)->name }}</strong><br>
            {{ optional($manifest->destinationBranch)->city }}
        </div>
    </div>

    <div class="meta">
        <div>
            <div class="label">Departed At</div>
            {{ optional($manifest->departed_at)->format('d M Y H:i') ?? '-' }}
        </div>
        <div>
            <div class="label">Created By</div>
            {{ optional($manifest->createdBy)->name ?? 'System' }}
        </div>
    </div>

    @if($manifest->notes)
        <div>
            <div class="label">Notes</div>
            {{ $manifest->notes }}
        </div>
    @endif

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tracking</th>
                <th>Sender</th>
                <th>Receiver</th>
                <th>Leg</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($manifest->items as $item)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ optional($item->shipment)->tracking_number }}</td>
                    <td>{{ optional(optional($item->shipment)->sender)->name }}</td>
                    <td>{{ optional(optional($item->shipment)->receiver)->name }}</td>
                    <td>
                        {{ optional(optional($item->leg)->originBranch)->name }}
                        ->
                        {{ optional(optional($item->leg)->destinationBranch)->name }}
                    </td>
                    <td>{{ strtoupper($item->status) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="signatures">
        <div>
            <div class="line">Origin Staff</div>
        </div>
        <div>
            <div class="line">Destination Staff</div>
        </div>
    </div>
</body>
</html>
