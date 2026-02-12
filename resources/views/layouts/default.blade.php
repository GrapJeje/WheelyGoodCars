<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <!-- Title -->
    @php
        function page_title()
        {
            $title = View::yieldContent('title');
            $base = config('app.name');
            return $title ? $base . ' | ' . $title : $base . ' | Full-Stack Developer';
        }
    @endphp
    <title>{{ page_title() }}</title>

    <!-- Styles -->
    @vite('resources/sass/app.scss')

    <!-- Livewire -->
    @livewireStyles
    @livewireScripts
</head>
<body>

@include('components.header')

<main class="container">
    @if (isset($slot))
        {{ $slot }}
    @endif
</main>

</body>
</html>
