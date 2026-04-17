<table width="100%">
    <thead>
        <tr>
            <td colspan="7" style="font-size:18px;"><center>{{ $judul }}</center></td>
        </tr>
        <tr>
            <td colspan="7" style="font-size:16px;"><center>BULAN {{ strtoupper($namaBulan) }} TAHUN {{ $tahun }}</center></td>
        </tr>
        <tr>
            <td colspan="7"></td>
        </tr>
    </thead>
</table>

<table width="100%" border="1">
    <thead>
        <tr>
            <th width="4%"><center>No</center></th>
            <th width="12%"><center>Tanggal</center></th>
            <th width="12%"><center>Jam</center></th>
            <th><center>Nama</center></th>
            <th><center>Jabatan</center></th>
            <th><center>Instansi</center></th>
            <th><center>Keperluan</center></th>
        </tr>
    </thead>
    <tbody>
        @forelse ($entries as $row)
            <tr>
                <td><center>{{ $loop->iteration }}</center></td>
                <td><center>{{ \Illuminate\Support\Carbon::parse($row->checkin)->format('d/m/Y') }}</center></td>
                <td><center>{{ \Illuminate\Support\Carbon::parse($row->checkin)->format('H:i:s') }}</center></td>
                <td><center>{{ $row->name }}</center></td>
                <td><center>{{ $row->position }}</center></td>
                <td><center>{{ $row->institution }}</center></td>
                <td><center>{{ $row->purpose ?: '-' }}</center></td>
            </tr>
        @empty
            <tr><td colspan="7"><center>Tidak ada data pada periode ini.</center></td></tr>
        @endforelse
    </tbody>
</table>
