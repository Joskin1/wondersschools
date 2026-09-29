@php
    $theme = \App\Services\FrontendLibrary::getTheme();
@endphp

<div>
    @if(view()->exists("themes.{$theme}.home"))
        @include("themes.{$theme}.home")
    @else
        @include("themes.editorial.home")
    @endif
</div>
