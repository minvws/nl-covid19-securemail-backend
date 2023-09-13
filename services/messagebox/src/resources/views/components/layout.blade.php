<!DOCTYPE html>
<html translate="no">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>GGD - {{ $title }}</title>
    <link rel="stylesheet" href="{{ mix('css/app.css') }}">
    <script nonce="{{ csp_nonce() }}">
        window.config = {!! json_encode($frontendConfiguration) !!};
        window.isLoggedIn = {!! json_encode($isLoggedIn) !!};
        window.sessionMessageUuid = {!! json_encode($sessionMessageUuid ?? null) !!};

        window.pairingCodeResponse = {!! json_encode($pairingCodeResponse) !!};
        window.digidResponse = {!! json_encode($digidResponse) !!};
    </script>
</head>

<body>
    {{ $slot }}
    <script src="{{ mix('js/app.js') }}"></script>
</body>

</html>
