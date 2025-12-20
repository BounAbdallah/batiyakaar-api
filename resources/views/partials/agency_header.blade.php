@php
    $logoSrc = null;
    $agence = $agence ?? null;

    if ($agence && $agence->logo) {
        try {
            $logoPath = public_path('storage/logos/' . $agence->logo);
            if (!file_exists($logoPath)) {
                $logoPath = storage_path('app/public/logos/' . $agence->logo);
            }

            if (file_exists($logoPath)) {
                $logoData = file_get_contents($logoPath);
                if ($logoData !== false) {
                    $logoMime = mime_content_type($logoPath);
                    $logoSrc = 'data:' . $logoMime . ';base64,' . base64_encode($logoData);
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Logo loading error: " . $e->getMessage());
        }
    }
@endphp

@if($agence)
    <table width="100%"
        style="border-bottom: 2px solid #eee; padding-bottom: 8px; margin-bottom: 12px; border-collapse: collapse;">
        <tr>
            <td style="text-align: center; vertical-align: middle;">
                @if($logoSrc)
                    <div style="margin-bottom: 5px;">
                        <img src="{{ $logoSrc }}" alt="Logo" style="height: 55px; width: auto; display: inline-block;">
                    </div>
                @endif
                <div
                    style="font-weight: bold; font-size: 16px; text-transform: uppercase; color: #1a1a1a; margin-top: 2px;">
                    {{ $agence->raison_sociale }}
                </div>
                <div style="font-size: 10px; color: #444; margin-top: 2px; line-height: 1.3;">
                    {{ $agence->adresse }}<br>
                    NINEA: {{ $agence->ninea }} | RCCM: {{ $agence->rccm }}
                    @if(isset($agence->user) && $agence->user->telephone)
                        | Tél: {{ $agence->user->telephone }}
                    @endif
                </div>
            </td>
        </tr>
    </table>
@endif