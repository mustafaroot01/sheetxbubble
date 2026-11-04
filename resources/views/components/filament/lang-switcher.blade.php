@php
    $currentLocale = app()->getLocale();
    $targetLocale = $currentLocale === 'ar' ? 'en' : 'ar';
    $label = $currentLocale === 'ar' ? 'EN' : 'عربي';
@endphp

<a href="{{ route('lang.switch', $targetLocale) }}"
   class="flex items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-semibold transition
          text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/5"
   title="{{ $currentLocale === 'ar' ? 'Switch to English' : 'التبديل للعربية' }}">
    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="10"/>
        <path d="M2 12h20"/>
        <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
    </svg>
    <span>{{ $label }}</span>
</a>
