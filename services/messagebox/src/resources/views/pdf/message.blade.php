<?php /** @var \App\Models\Message $message */ ?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0, user-scalable=0">

    <title>{{ $message->subject }}</title>
    <style type="text/css">
        @page {
            margin: 0 0;
        }

        body {
            font-family: 'Dejavu Sans', sans-serif;
            position: relative;
            padding: 2cm;
            color: #333333;
        }

        p {
            padding: 0;
            margin-top: 0;
        }

        a {
            color: #2962ff;
        }

        h1 {
            margin: 0 0 10px 0;
            padding: 0;
            font-size: 28px;
        }

        hr {
            margin-top: 1rem;
            margin-bottom: 1rem;
            border: 1px solid rgba(0, 0, 0, 0.1);
        }

        table {
            width: 100%;
            margin: 0 0 10px 0;
            padding: 0;
        }

        tr,
        td,
        th {
            margin: 0;
            padding: 0;
        }

        table td,
        table th {
            vertical-align: top;
            text-align: left;
            padding: 4px;
        }
    </style>
</head>

<body>
    <h1>{{ $message->subject }}</h1>
    <strong>{{ $message->fromName }}</strong>
    <p>
        Ontvangen op {{ $message->createdAt->translatedFormat('d F Y \o\m g:i') }}
        @if ($message->expiresAt)
        <br />
        Beschikbaar tot
        {{ $message->expiresAt->translatedFormat('d F Y \o\m g:i') }}
        @endif
    </p>
    <hr />
    <div class="message-body">
        {!! $message->text !!}
    </div>
</body>

</html>
