<div class="sk-scroll"><table>
    <thead><tr><th>Waktu ({{ config('app.timezone') }})</th><th>Pelaku</th><th>Tindakan</th><th>Perubahan</th></tr></thead>
    <tbody>@forelse($audits as $audit)
        <tr><td>{{ $audit->created_at->format('d/m/Y H:i:s') }}</td><td>{{ $audit->pelaku_nama }}<br><span class="sk-muted">{{ $audit->pelaku_role }} · ID {{ $audit->pelaku_id }}</span></td><td>{{ $audit->tindakan }}</td>
            <td class="sk-title">@foreach($audit->perubahan as $key => $value)<p><strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong> <span style="white-space:pre-wrap">{{ is_array($value) ? json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : (is_bool($value) ? ($value ? 'Aktif' : 'Tidak aktif') : ($value ?? '-')) }}</span></p>@endforeach</td></tr>
    @empty<tr><td colspan="4">Belum ada catatan riwayat.</td></tr>@endforelse</tbody>
</table></div>
@include('skripsi.partials.pagination', ['paginator' => $audits])
