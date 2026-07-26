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

    <div class="signature-block">
        <p><strong>Signé électroniquement par :</strong> {{ $employee->full_name }}</p>
        <img class="signature-image" src="{{ $signatureData }}">
        <p class="meta">
            Signé le {{ $signedAt->format('d/m/Y à H:i') }} depuis l'adresse IP {{ $signedIp }}.<br>
            Empreinte d'intégrité du document (SHA-256) : {{ $hash }}
        </p>
    </div>
</body>

</html>
