<table class="report-header">
    <tr>
        <td style="width:66px;">
            @if(file_exists(public_path('images/logo_sttmi.jpeg')))
                <img class="report-logo" src="{{ public_path('images/logo_sttmi.jpeg') }}" alt="Logo STTMI">
            @endif
        </td>
        <td>
            <div class="report-brand">SISTEM INFORMASI AKADEMIK STTMI</div>
            <div class="report-title">{{ $reportTitle }}</div>
            <div class="report-meta">Filter: {{ $deskripsiFilter }} &nbsp; | &nbsp; Dibuat {{ now()->format('d-m-Y H:i') }} WIB</div>
        </td>
    </tr>
</table>
<div class="footer">Dokumen dibuat otomatis oleh SIAKAD STTMI</div>
