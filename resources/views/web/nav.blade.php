<section>
    <style>
        .carousel-slide {
            cursor: pointer;
        }

        .opacity-0 {
            opacity: 0;
            pointer-events: none;
            /* important */
        }

        .opacity-100 {
            opacity: 1;
            pointer-events: auto;
        }
    </style>
    <nav class="relative pl-6 py-6 flex justify-between items-center bg-white custom-menu">
        @php
            $nav_business = \App\Business::find(session()->get('business.id'));
            $nav_logo_url = '';
            if (!empty($nav_business) && !empty($nav_business?->site_logo_path)) {
                $nav_logo_url = asset($nav_business?->site_logo_path);
            } elseif (!empty($settings)) {
                $nav_logo_url = asset($settings?->site_logo);
            }
        @endphp
        @if (!empty($nav_logo_url))
            <a class="text-dark text-3xl font-bold leading-none" href="{{ url('/') }}"><img class="h-18"
                    src="{{ $nav_logo_url }}" alt="{{ $settings->site_name ?? 'Logo' }}" width="auto"></a>
        @endif
        <div class="lg:hidden">
            <button class="navbar-burger flex items-center text-dark p-3">

                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" />
                </svg>
            </button>
        </div>
        <ul
            class="hidden absolute top-1/2 left-1/2 pr-12  transform -translate-y-1/2 -translate-x-1/2 lg:flex lg:mx-auto lg:flex lg:items-center lg:w-auto lg:space-x-4">

            @if (!empty($data['about']) && $data['about'] == 1)
                <li>
                    <a class="text-sm font-bold hover:text-dark text-dark"
                        href="{{ url('about-us') }}">{{ __('About') }}</a>
                </li>
                <li class="text-gray-800">
                    <svg class="w-2 h-4 current-fill" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewbox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z">
                        </path>
                    </svg>
                </li>
            @endif

            @if (!empty($data['how_it_works']) && $data['how_it_works'] == 1)
                <li>
                    @if (request()->is('/') != false)
                        <a class="text-sm font-bold hover:text-dark text-dark"
                            href="#how-it-works">{{ __('How it works?') }}</a>
                    @else
                        <a class="text-sm font-bold hover:text-dark text-dark"
                            href="{{ url('index') }}#how-it-works">{{ __('How it works?') }}</a>
                    @endif
                </li>
                <li class="text-gray-800">
                    <svg class="w-4 h-4 current-fill" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewbox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z">
                        </path>
                    </svg>
                </li>
            @endif

            @if (isset($data['features']) && !empty($data['features']) && $data['features'] == 1)
                <li>
                    @if (request()->is('/') != false)
                        <a class="text-sm font-bold hover:text-dark text-dark" href="#features">{{ __('Features') }}</a>
                    @else
                        <a class="text-sm font-bold hover:text-dark text-dark"
                            href="{{ url('index') }}#features">{{ __('Features') }}</a>
                    @endif
                </li>
                <li class="text-gray-800">
                    <svg class="w-4 h-4 current-fill" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewbox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z">
                        </path>
                    </svg>
                </li>
            @endif
            @if (isset($data['pricing']) && !empty($data['pricing']) && $data['pricing'] == 1)
                <li>
                    @if (request()->is('/') != false)
                        <a class="text-sm font-bold hover:text-dark text-dark" href="#pricing">{{ __('Pricing') }}</a>
                    @else
                        <a class="text-sm font-bold hover:text-dark text-dark"
                            href="{{ url('index') }}#pricing">{{ __('Pricing') }}</a>
                    @endif
                </li>
                <li class="text-gray-800">
                    <svg class="w-4 h-4 current-fill" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewbox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z">
                        </path>
                    </svg>
                </li>
            @endif
            @if (isset($data['contact']) && !empty($data['contact']) && $data['contact'] == 1)
                <li>
                    <a class="text-sm font-bold hover:text-dark text-dark" href="{{ url('contact-us') }}">Contact</a>
                </li>
            @endif
        </ul>

        <div class="hidden lg:inline-block">

            @if (!empty($data['login']) && $data['login'] == 1)
                @guest
                    <a class="hidden lg:inline-block py-2 px-6 bg-{{ $config[11]?->config_value ?? '' }}-500 hover:bg-{{ $config[11]?->config_value ?? '' }}-600 text-sm text-white font-bold rounded-l-xl rounded-t-xl transition duration-200"
                        href="{{ url('login') }}">{{ __('Sign In') }}</a>
                @else
                    <a class="hidden lg:inline-block py-2 px-6 bg-{{ $config[11]?->config_value ?? '' }}-500 hover:bg-{{ $config[11]?->config_value ?? '' }}-600 text-sm text-white font-bold rounded-l-xl rounded-t-xl transition duration-200"
                        href="{{ url('home') }}">{{ __('Dashboard') }}</a>
                @endguest
            @endif


            @if (!empty($data['language']) && $data['language'] == 1)
                @if (count(config('app.languages')) > 1)

                    <div @click.away="open = false"
                        class="hidden cursor-pointer lg:inline-block px-2 transition duration-200 w-40"
                        x-data="{ open: false }">
                        <a @click="open = !open"
                            class="px-6 py-2 font-semibold text-dark text-center bg-gray-200 rounded-l-xl rounded-t-xl dark-mode:bg-transparent dark-mode:focus:text-white dark-mode:hover:text-white dark-mode:focus:bg-gray-600 dark-mode:hover:bg-gray-600 hover:text-dark focus:text-gray-900 hover:bg-gray-300 focus:bg-gray-300 focus:outline-none focus:shadow-outline">
                            <span>{{ config('app.languages')[app()->getLocale()] }}</span>
                            <svg fill="currentColor" viewBox="0 0 20 20"
                                :class="{ 'rotate-180': open, 'rotate-0': !open }"
                                class="inline w-4 h-4 mt-1 ml-1 transition-transform duration-200 transform md:-mt-1">
                                <path fill-rule="evenodd"
                                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                    clip-rule="evenodd"></path>
                            </svg>
                        </a>
                        <div x-show="open" id="journal-scroll" x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="transform opacity-0 scale-95"
                            x-transition:enter-end="transform opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="transform opacity-100 scale-100"
                            x-transition:leave-end="transform opacity-0 scale-95"
                            class="fixed w-36 h-80 overflow-y-scroll mt-2 rounded-lg shadow-lg md:w-32">
                            <div class="px-2 py-2 bg-white capitalize rounded-sm shadow dark-mode:bg-gray-800">
                                @foreach (config('app.languages') as $langLocale => $langName)
                                    <a class="block px-4 py-2 mt-2 text-sm capitalize font-semi-bold bg-transparent rounded-sm dark-mode:bg-transparent dark-mode:hover:bg-gray-600 dark-mode:focus:bg-gray-600 dark-mode:focus:text-white dark-mode:hover:text-white dark-mode:text-gray-200 md:mt-0 hover:text-gray-900 focus:text-gray-900 hover:bg-gray-200 focus:bg-gray-200 focus:outline-none focus:shadow-outline"
                                        href="{{ url()->current() }}?change_language={{ $langLocale }}">{{ strtoupper($langName) }}</a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </nav>
    @include('web.ad')
    @if (isset($banner) && $banner)
        <div class="bg-white pt-6 pb-12 md:pb-24">
            <div class="container mx-auto px-4">
                <div class="flex flex-wrap -mx-4">
                    <div class="w-full lg:w-1/2 px-4 mb-12 md:mb-20 lg:mb-0 flex items-center">
                        <div class="w-full text-center lg:text-left">
                            <div class="max-w-md mx-auto lg:mx-0">
                                <h2 class="mb-3 text-4xl lg:text-5xl text-white font-bold">
                                    <span class="text-{{ $config[11]?->config_value ?? '' }}-500">
                                        {{ is_array($homePage[0]?->section_content ?? null) ? implode(' ', $homePage[0]->section_content) : $homePage[0]?->section_content ?? '' }}
                                    </span>
                                </h2>
                            </div>
                            <div class="max-w-sm mx-auto lg:mx-0">
                                <p class="mb-6 text-gray-800 leading-loose">
                                    {{ is_array($homePage[1]?->section_content ?? null) ? implode(' ', $homePage[1]->section_content) : $homePage[1]?->section_content ?? '' }}
                                </p>
                                <div>
                                    <a class="inline-block mb-3 lg:mb-0 lg:mr-3 w-full lg:w-auto py-2 px-6 leading-loose bg-{{ $config[11]?->config_value ?? '' }}-600 hover:bg-{{ $config[11]?->config_value ?? '' }}-700 text-white font-semibold rounded-l-xl rounded-t-xl transition duration-200"
                                        href="{{ is_array($homePage[3]?->section_content ?? null) ? implode('', $homePage[3]->section_content) : $homePage[3]?->section_content ?? '' }}">
                                        {{ is_array($homePage[2]?->section_content ?? null) ? implode(' ', $homePage[2]->section_content) : $homePage[2]?->section_content ?? '' }}
                                    </a>
                                    <a class="inline-block w-full lg:w-auto py-2 px-6 leading-loose text-white font-semibold bg-gray-900 border-2 border-gray-700 hover:border-gray-600 rounded-l-xl rounded-t-xl transition duration-200"
                                        href="{{ is_array($homePage[5]?->section_content ?? null) ? implode('', $homePage[5]->section_content) : $homePage[5]?->section_content ?? '#' }}">
                                        {{ is_array($homePage[4]?->section_content ?? null) ? implode(' ', $homePage[4]->section_content) : $homePage[4]?->section_content ?? '' }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if (isset($banners) && $banners)
                        @include('web.banner');
                    @else
                        <div class="hidden lg:block w-full lg:w-1/2 px-4 flex items-center justify-center">
                            <div class="relative web-index">
                                <img class="h-128 w-full max-w-lg object-cover rounded-3xl md:rounded-br-none"
                                    src="{{ asset($config[12]?->config_value ?? '') }}" alt="">
                                <img class="hidden md:block absolute web-nav-top">
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
    <div class="hidden navbar-menu relative z-50">
        <div class="navbar-backdrop fixed inset-0 bg-gray-800 opacity-25"></div>
        <nav
            class="fixed top-0 left-0 bottom-0 flex flex-col w-5/6 max-w-sm py-6 px-6 bg-white border-r overflow-y-auto">
            <div class="flex items-center mb-8">
                @php
                    $nav_drawer_logo = '';
                    if (!empty($nav_business) && !empty($nav_business->site_logo_path)) {
                        $nav_drawer_logo = $nav_business->site_logo_path;
                    } elseif (!empty($settings) && !empty($settings->site_logo)) {
                        $nav_drawer_logo = $settings->site_logo;
                    }
                @endphp
                @if (!empty($nav_drawer_logo))
                    <a class="mr-auto text-3xl font-bold leading-none" href="{{ url('/') }}">
                        <img class="h-10" src="{{ asset($nav_drawer_logo) }}"
                            alt="{{ $settings->site_name ?? 'Logo' }}" width="auto">
                    </a>
                @endif
                <button class="navbar-close">
                    <svg class="h-6 w-6 text-gray-400 cursor-pointer hover:text-gray-500"
                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div>
                <ul>
                    @if (!empty($config[11]?->config_value) && is_string($config[11]?->config_value))
                        @php $color = $config[11]?->config_value; @endphp
                    @else
                        @php $color = 'gray'; @endphp
                    @endif
                    @if (!empty($homePage) && isset($homePage[0]))
                        <li class="mb-1">
                            <a class="block p-4 text-sm font-semibold text-gray-400 hover:bg-{{ $color }}-50 hover:text-{{ $color }}-600 rounded"
                                href="{{ url('about-us') }}">{{ __('About') }}</a>
                        </li>
                    @endif
                    @if (request()->is('/'))
                        @if (!empty($homePage) && isset($homePage[1]))
                            <li class="mb-1">
                                <a class="block p-4 text-sm font-semibold text-gray-400 hover:bg-{{ $color }}-50 hover:text-{{ $color }}-600 rounded"
                                    href="#how-it-works">{{ __('How it works?') }}</a>
                            </li>
                            <li class="mb-1">
                                <a class="block p-4 text-sm font-semibold text-gray-400 hover:bg-{{ $color }}-50 hover:text-{{ $color }}-600 rounded"
                                    href="#features">{{ __('Features') }}</a>
                            </li>
                            <li class="mb-1">
                                <a class="block p-4 text-sm font-semibold text-gray-400 hover:bg-{{ $color }}-50 hover:text-{{ $color }}-600 rounded"
                                    href="#pricing">{{ __('Pricing') }}</a>
                            </li>
                        @endif
                    @else
                        @if (!empty($homePage) && isset($homePage[1]))
                            <li class="mb-1">
                                <a class="block p-4 text-sm font-semibold text-gray-400 hover:bg-{{ $color }}-50 hover:text-{{ $color }}-600 rounded"
                                    href="{{ url('index') }}#how-it-works">{{ __('How it works?') }}</a>
                            </li>
                            <li class="mb-1">
                                <a class="block p-4 text-sm font-semibold text-gray-400 hover:bg-{{ $color }}-50 hover:text-{{ $color }}-600 rounded"
                                    href="{{ url('index') }}#features">{{ __('Features') }}</a>
                            </li>
                            <li class="mb-1">
                                <a class="block p-4 text-sm font-semibold text-gray-400 hover:bg-{{ $color }}-50 hover:text-{{ $color }}-600 rounded"
                                    href="{{ url('index') }}#pricing">{{ __('Pricing') }}</a>
                            </li>
                        @endif
                    @endif
                    @if (!empty($homePage) && isset($homePage[2]))
                        <li class="mb-1">
                            <a class="block p-4 text-sm font-semibold text-gray-400 hover:bg-{{ $color }}-50 hover:text-{{ $color }}-600 rounded"
                                href="{{ url('contact-us') }}">Contact</a>
                        </li>
                    @endif
                    {{-- Language Switch --}}
                    @if (!empty(config('app.languages')) && count(config('app.languages')) > 1)
                        <div @click.away="open = false" @click="open = !open"
                            class="block p-4 text-sm font-semibold text-gray-400 hover:bg-{{ $color }}-50 hover:text-{{ $color }}-600 rounded transition duration-200"
                            x-data="{ open: false }">
                            <a>
                                <span>{{ config('app.languages')[app()->getLocale()] ?? '' }}</span>
                            </a>
                            <div x-show="open"
                                class="absolute right-0 w-full h-80 overflow-y-scroll mt-2 origin-top-right rounded-lg shadow-lg md:w-40">
                                <div class="px-2 py-2 bg-white capitalize rounded-sm shadow">
                                    @foreach (config('app.languages') ?? [] as $langLocale => $langName)
                                        <a class="block px-4 py-2 mt-2 text-sm capitalize font-semi-bold bg-transparent rounded-sm hover:text-gray-900 focus:text-gray-900 hover:bg-gray-200 focus:bg-gray-200 focus:outline-none focus:shadow-outline"
                                            href="{{ url()->current() }}?change_language={{ $langLocale }}">{{ strtoupper($langName) }}</a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                </ul>
            </div>
            <div class="mt-auto">
                <div class="pt-6">
                    @php
                        // Safe color from $config[11]
                        $color = 'gray';
                        if (
                            !empty($config) &&
                            is_array($config) &&
                            array_key_exists(11, $config) &&
                            is_object($config[11]) &&
                            is_string($config[11]?->config_value)
                        ) {
                            $color = $config[11]->config_value;
                        }

                        // Safe site name
                        $siteName = '';
                        if (
                            !empty($settings) &&
                            is_object($settings) &&
                            property_exists($settings, 'site_name') &&
                            is_string($settings->site_name)
                        ) {
                            $siteName = $settings->site_name;
                        }
                    @endphp
                    @guest
                        <a class="block px-4 py-3 mb-3 text-xs text-center font-semibold leading-none text-white bg-{{ $color }}-600 hover:bg-{{ $color }}-700 rounded-l-xl rounded-t-xl"
                            href="{{ url('login') }}">{{ __('Sign In / Sign Up') }}</a>
                    @else
                        <a class="block px-4 py-3 mb-3 text-xs text-center font-semibold leading-none text-white bg-{{ $color }}-600 hover:bg-{{ $color }}-700 rounded-l-xl rounded-t-xl"
                            href="{{ url('home') }}">{{ __('Dashboard') }}</a>
                    @endguest
                </div>
                @if (!empty($siteName))
                    <p class="my-4 text-xs text-center text-{{ $color }}-400">
                        <span><span id="year"></span> {{ $siteName }}.
                            {{ __('All rights reserved.') }}</span>
                    </p>
                @endif

                <div class="text-center">
                    @php
                        $socialIcons = ['facebook', 'twitter', 'instagram'];
                    @endphp

                    @foreach ($socialIcons as $index => $icon)
                        @php
                            $url = '#';
                            if (
                                !empty($supportPage) &&
                                isset($supportPage[$index + 1]->section_content) &&
                                is_string($supportPage[$index + 1]->section_content)
                            ) {
                                $url = $supportPage[$index + 1]->section_content;
                            }
                        @endphp
                        <a class="inline-block px-1" href="{{ $url }}" target="_blank">
                            <img src="{{ asset('frontend/assets/social/' . $icon . '.svg') }}"
                                alt="{{ $icon }}">
                        </a>
                    @endforeach
                </div>
            </div>
        </nav>
    </div>
</section>
@if (!empty($banners) && $banners->count() > 0)
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const slides = document.querySelectorAll('.carousel-slide');
            if (slides.length <= 1) return;

            let currentIndex = 0;
            let timeout;

            function startTimer() {
                clearTimeout(timeout);

                const currentSlide = slides[currentIndex];
                const duration = (parseFloat(currentSlide.dataset.duration) || 3) * 1000; // convert sec → ms


                timeout = setTimeout(goNext, duration);
            }

            function goNext() {
                slides[currentIndex].classList.remove('opacity-100');
                slides[currentIndex].classList.add('opacity-0');

                currentIndex = (currentIndex + 1) % slides.length;

                slides[currentIndex].classList.remove('opacity-0');
                slides[currentIndex].classList.add('opacity-100');

                startTimer();
            }


            slides.forEach(slide => {
                slide.addEventListener('click', function(e) {
                    // e.currentTarget always refers to the slide
                    const link = e.currentTarget.dataset.link;
                    if (link) window.open(link, '_blank');
                });
            });

            // Initial state
            slides.forEach((slide, index) => {
                slide.classList.add(
                    'absolute',
                    'inset-0',
                    'w-full',
                    'h-full',
                    'object-cover',
                    'transition-opacity',
                    'duration-700'
                );

                slide.classList.toggle('opacity-100', index === 0);
                slide.classList.toggle('opacity-0', index !== 0);
            });

            startTimer();
        });
    </script>
@endif
