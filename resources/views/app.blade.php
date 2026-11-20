<!doctype html>
<html lang="en">

<head>
    <title>{{ config('settings.application.company_name') }}</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" href="{{ config('settings.application.company_icon') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link
        href="https://fonts.googleapis.com/css2?family=Nunito:ital,wght@0,200;0,300;0,400;0,600;0,700;0,800;0,900;1,200;1,300;1,400;1,600;1,700;1,800;1,900&display=swap"
        rel="stylesheet">

    <style>
        :root {
            --primary: {{ config('settings.application.primary_color', '#2b8768') }};
            --primary-hover: {{ config('settings.application.primary_hover_color', '#1f604a') }};
            --dashboard-bg: {{ config('settings.application.dashboard_color', '#f5f6fa') }};
            --white: {{ config('settings.application.background_color', '#ffffff') }};
            --font-color: {{ config('settings.application.font_color', '#4F5867') }};
            --border-color: {{ config('settings.application.border_color', '#ececec') }};
        }

        [theme="dark"] {
            --primary: {{ config('settings.application.primary_dark_color') }};
            --primary-hover: {{ config('settings.application.primary_hover_dark_color') }};
            --dashboard-bg: {{ config('settings.application.dashboard_dark_color') }};
            --white: {{ config('settings.application.background_dark_color') }};
            --font-color: {{ config('settings.application.font_dark_color') }};
            --border-color: {{ config('settings.application.border_dark_color') }};
        }
    </style>
    @vite('resources/sass/app.scss')


</head>

<body>
<div id="app"></div>

@vite('resources/js/app.js')
</body>

</html>
