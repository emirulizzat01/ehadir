@props(['title' => null])
@include('layouts.app', ['slot' => $slot, 'title' => $title, 'header' => $header ?? null])
