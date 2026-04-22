<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <title>{{ config('app.name') }} — Dashboard</title>
    <link href="https://fonts.cdnfonts.com/css/lato" rel="stylesheet">
    @vite(['resources/js/apps/dashboard/main.js'])
</head>
<body>
    <div id="app"></div>
</body>
</html>
