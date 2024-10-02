<nav x-data="{ open: false }" class="bg-[#314795] border-b border-blue-700">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" class="text-white text-lg font-bold hover:text-gray-500">
                        ShoTrack
                    </a>
                </div>
                

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" class="text-white hover:text-blue-300">
                        {{ __('Home') }}
                    </x-nav-link>

                    @can('view permissions')
                    <x-nav-link :href="route('permissions.index')" :active="request()->routeIs('permissions.index')" class="text-white hover:text-blue-300">
                        {{ __('Permissions') }}
                    </x-nav-link>
                    @endcan

                    @can('view roles')
                    <x-nav-link :href="route('roles.index')" :active="request()->routeIs('roles.index')" class="text-white hover:text-blue-300">
                        {{ __('Roles') }}
                    </x-nav-link>
                    @endcan

                    @can('view users')
                    <x-nav-link :href="route('users.index')" :active="request()->routeIs('users.index')" class="text-white hover:text-blue-300">
                        {{ __('Users') }}
                    </x-nav-link>
                    @endcan

                    <x-nav-link :href="route('tournaments.index')" :active="request()->routeIs('tournaments.index')" class="text-white hover:text-blue-300">
                        {{ __('Tournaments') }}
                    </x-nav-link>

                    <x-nav-link :href="route('teams.index')" :active="request()->routeIs('teams.index')" class="text-white hover:text-blue-300">
                        {{ __('Teams') }}
                    </x-nav-link>

                    <x-nav-link :href="route('players.index')" :active="request()->routeIs('players.index')" class="text-white hover:text-blue-300">
                        {{ __('Players') }}
                    </x-nav-link>
                    
                    <x-nav-link :href="route('schedules.index')" :active="request()->routeIs('schedules.index')" class="text-white hover:text-blue-300">
                        {{ __('Game Schedules') }}
                    </x-nav-link>

                    <x-nav-link :href="route('leaderboards.index')" :active="request()->routeIs('leaderboards.index')" class="text-white hover:text-blue-300">
                        {{ __('Leaderboards') }}
                    </x-nav-link>                    
                </div>
            </div>

            <!-- User Dropdown or Login/Register Links -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                @if (Auth::check())
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white hover:text-blue-300 focus:outline-none focus:text-white-200 transition ease-in-out duration-150">
                                <div>{{ Auth::user()->name }} ({{ Auth::user()->roles->pluck('name')->implode(', ') }})</div>
                                <div class="ms-1">
                                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </button>
                        </x-slot>
            
                        <x-slot name="content">
                            <x-dropdown-link :href="route('profile.edit')" class="text-gray-800 hover:bg-blue-100 hover:text-blue-600">
                                {{ __('Profile') }}
                            </x-dropdown-link>
            
                            <!-- Authentication -->
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();" class="text-gray-800 hover:bg-blue-100 hover:text-blue-600">
                                    {{ __('Log Out') }}
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                @else
                    <div class="space-x-4">
                        <a href="{{ route('login') }}" class="text-sm text-white hover:text-blue-500">{{ __('Log In') }}</a>
                        <a href="{{ route('register') }}" class="text-sm text-white hover:text-blue-300">{{ __('Register') }}</a>
                    </div>
                @endif
            </div>
            
            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 focus:outline-none focus:text-gray-200 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" class="text-blue-600 hover:bg-blue-100">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('tournaments.index')" :active="request()->routeIs('tournaments.index')" class="text-blue-600 hover:bg-blue-100">
                {{ __('Tournaments') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('teams.index')" :active="request()->routeIs('teams.index')" class="text-blue-600 hover:bg-blue-100">
                {{ __('Teams') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('players.index')" :active="request()->routeIs('players.index')" class="text-blue-600 hover:bg-blue-100">
                {{ __('Players') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('schedules.index')" :active="request()->routeIs('schedules.index')" class="text-blue-600 hover:bg-blue-100">
                {{ __('Game Schedules') }}
            </x-responsive-nav-link>
        </div>

        <!-- Responsive Login/Register Links -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                @if (Auth::check())
                    <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                    <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
                @else
                    <div class="space-x-4">
                        <a href="{{ route('login') }}" class="text-sm text-blue-600 hover:bg-blue-100">{{ __('Log In') }}</a>
                        <a href="{{ route('register') }}" class="text-sm text-blue-600 hover:bg-blue-100">{{ __('Register') }}</a>
                    </div>
                @endif
            </div>

            <div class="mt-3 space-y-1">
                @if (Auth::check())
                    <x-responsive-nav-link :href="route('profile.edit')" class="text-blue-600 hover:bg-blue-100">
                        {{ __('Profile') }}
                    </x-responsive-nav-link>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();" class="text-blue-600 hover:bg-blue-100">
                            {{ __('Log Out') }}
                        </x-responsive-nav-link>
                    </form>
                @endif
            </div>
        </div>
    </div>
</nav>
