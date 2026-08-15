<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #1f2937;
            line-height: 1.6;
        }

        .title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 24px;
        }

        .content {
            margin-bottom: 40px;
        }

        .signature-block {
            border-top: 1px solid #d1d5db;
            padding-top: 16px;
        }

        .steps-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        .signature-step {
            width: 50%;
            vertical-align: top;
            padding: 0 12px 16px 0;
        }

        .step-icon {
            display: inline-block;
            width: 9px;
            height: 9px;
            border-radius: 50%;
            margin-right: 6px;
        }

        .step-icon-approved {
            background-color: #16a34a;
        }

        .step-icon-rejected {
            background-color: #dc2626;
        }

        .signature-image {
            height: 80px;
        }

        .meta {
            font-size: 10px;
            color: #6b7280;
            margin-top: 8px;
        }
    </style>
</head>

<body>
    @include('pdf.partials.header')

    <div class="title">{{ $generatedDocument->title }}</div>

    <div class="content">{!! $generatedDocument->content !!}</div>

    @if ($generatedDocument->approvals->isNotEmpty())
        <div class="signature-block">
            <table class="steps-table">
                @foreach ($generatedDocument->approvals->chunk(2) as $pair)
                    <tr>
                        @foreach ($pair as $step)
                            <td class="signature-step">
                                <p>
                                    <span
                                        class="step-icon {{ $step->status === 'rejected' ? 'step-icon-rejected' : 'step-icon-approved' }}"></span>
                                    <strong>{{ $step->label() }} :</strong> {{ $step->decidedBy?->name }}
                                </p>
                                @if ($step->signature_data)
                                    <img class="signature-image" src="{{ $step->signature_data }}">
                                @endif
                                <p class="meta">
                                    {{ $step->status === 'rejected' ? 'Refusé' : 'Approuvé' }} le
                                    {{ $step->decided_at?->format('d/m/Y à H:i') }}
                                </p>
                            </td>
                        @endforeach
                        @if ($pair->count() === 1)
                            <td class="signature-step"></td>
                        @endif
                    </tr>
                @endforeach
            </table>
        </div>
    @else
        <div class="signature-block">
            <p><strong>Signé électroniquement par :</strong> {{ $employee->full_name }}</p>
            <img class="signature-image" src="{{ $signatureData }}">
            <p class="meta">
                Signé le {{ $signedAt->format('d/m/Y à H:i') }} depuis l'adresse IP {{ $signedIp }}.
            </p>
        </div>
    @endif

    @include('pdf.partials.footer')
</body>

</html>
