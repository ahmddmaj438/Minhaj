@props(['compact' => false])

@php
    $locale = app()->getLocale();
    $buttonBase = $compact
        ? 'px-2.5 py-1.5 text-xs'
        : 'px-3 py-2 text-sm';
@endphp

<form method="POST" action="{{ route('language.switch') }}" class="language-switcher inline-flex items-center rounded-2xl border border-slate-200/80 bg-white/80 p-1 shadow-sm backdrop-blur" aria-label="{{ __('Language') }}" dir="ltr">
    @csrf
    <input type="hidden" name="redirect_to" value="{{ request()->fullUrl() }}">

    <button type="submit" name="locale" value="en"
        class="{{ $buttonBase }} rounded-xl font-semibold transition {{ $locale === 'en' ? 'bg-brand-navy text-white shadow-sm' : 'text-slate-600 hover:bg-orange-50 hover:text-brand-ink' }}">
        EN
    </button>
    <button type="submit" name="locale" value="ar"
        class="{{ $buttonBase }} rounded-xl font-semibold transition {{ $locale === 'ar' ? 'bg-brand-navy text-white shadow-sm' : 'text-slate-600 hover:bg-orange-50 hover:text-brand-ink' }}">
        AR
    </button>
</form>
