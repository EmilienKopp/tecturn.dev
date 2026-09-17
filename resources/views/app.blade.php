<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        @php
            $metaTitle = ($metaTitle ?? null) ?: config('app.name', 'Tecturn').' - The stage for developer talks';
            $metaDescription = ($metaDescription ?? null) ?: 'Build slides out of blocks, wire their reveal order in a flow graph, and present with live translation and floating audience reactions. A built-in pacing coach keeps your talk on time.';
            $metaImage = ($metaImage ?? null) ?: url('/apple-touch-icon.png');
        @endphp

        <meta name="description" content="{{ $metaDescription }}">

        <meta property="og:type" content="website">
        <meta property="og:site_name" content="{{ config('app.name', 'Tecturn') }}">
        <meta property="og:title" content="{{ $metaTitle }}">
        <meta property="og:description" content="{{ $metaDescription }}">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:image" content="{{ $metaImage }}">
        <meta property="og:locale" content="{{ str_replace('-', '_', app()->getLocale()) }}">

        <meta name="twitter:card" content="summary">
        <meta name="twitter:title" content="{{ $metaTitle }}">
        <meta name="twitter:description" content="{{ $metaDescription }}">
        <meta name="twitter:image" content="{{ $metaImage }}">

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.ts'])
        <x-inertia::head>
            <title>{{ config('app.name', 'Tecturn') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
