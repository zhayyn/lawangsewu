<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Cetak Kartu Tamu</title>
    <style>
        body { margin: 0; padding: 0; font-family: sans-serif; }
        .card { width: 300px; padding: 20px; border: 2px solid #ccc; background-color: #f9f9f9; text-align: center; }
        .hdr { background-color: #B28751; color: white; padding: 10px; font-weight: bold; margin-bottom: 20px; }
        .img { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; margin-bottom: 15px; }
        .info { margin-bottom: 5px; }
        .title { font-size: 1.2rem; font-weight: bold; }
        .dt { font-size: 0.8rem; color: #555; margin-top: 15px; }
    </style>
</head>
<body onload="window.print()">
    <div class="card">
        <div class="hdr">KARTU VISITOR #{{ str_pad((string) $row, 3, '0', STR_PAD_LEFT) }}</div>
        @php
            $fotoUrl = asset('guestbook/tanpafoto.jpg');
            foreach (['jpg', 'jpeg', 'png'] as $ext) {
                $storageRelative = 'guestbook/photos/' . $entry->id . '.' . $ext;
                if (\Illuminate\Support\Facades\Storage::disk('public')->exists($storageRelative)) {
                    $fotoUrl = asset('storage/' . $storageRelative);
                    break;
                }

                $candidate = public_path('guestbook/photos/' . $entry->id . '.' . $ext);
                if (is_file($candidate)) {
                    $fotoUrl = asset('guestbook/photos/' . $entry->id . '.' . $ext);
                    break;
                }
            }
        @endphp
        <img src="{{ $fotoUrl }}" class="img">

        <div class="info title">{{ $entry->name }}</div>
        <div class="info">{{ $entry->position }}</div>
        <div class="info"><strong>{{ $entry->institution }}</strong></div>

        <div class="dt">Check-in: {{ \Illuminate\Support\Carbon::parse($entry->checkin)->format('d M Y, H:i') }}</div>
    </div>
</body>
</html>
