<nav x-data="{ open: false }" class="bg-surface/90 backdrop-blur-md border-b border-border sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">

            {{-- ── Logo + Nav Links ── --}}
            <div class="flex items-center">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5 shrink-0 group">
                    <div class="w-9 h-9 rounded-lg bg-accent flex items-center justify-center shadow-sm group-hover:shadow-gbtn transition-shadow duration-200">
                        <i class="fas fa-microchip text-white text-sm"></i>
                    </div>
                    <span class="font-display font-bold text-lg text-ink tracking-tight">
                        S<span class="text-accent">IGIT</span>
                    </span>
                </a>

                {{-- Desktop nav links --}}
                <div class="hidden sm:flex items-center gap-1 ml-8">
                    <a href="{{ route('admin.dashboard') }}"
                       class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm font-medium transition-all duration-150
                              {{ request()->routeIs('admin.dashboard')
                                  ? 'bg-accent-light text-accent'
                                  : 'text-ink-soft hover:text-ink hover:bg-s2' }}">
                        <i class="fas fa-gauge-high text-xs"></i>
                        Dashboard
                    </a>
                </div>
            </div>

            {{-- ── User Dropdown (desktop) ── --}}
            <div class="hidden sm:flex sm:items-center sm:gap-3">

                {{-- Back to public site --}}
                <a href="{{ route('public.landing') }}"
                   class="inline-flex items-center gap-1.5 text-xs text-ink-muted hover:text-ink-soft px-3 py-1.5 rounded-lg hover:bg-s2 transition-all duration-150">
                    <i class="fas fa-arrow-up-right-from-square text-[10px]"></i>
                    Situs Publik
                </a>

                <div class="w-px h-5 bg-border"></div>

                {{-- Dropdown --}}
                <x-dropdown align="right" width="52">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-2.5 pl-1 pr-3 py-1.5 rounded-xl border border-border hover:border-accent/40 hover:bg-s2 transition-all duration-150 text-sm text-ink-soft hover:text-ink focus:outline-none">
                            {{-- Avatar initials --}}
                            <div class="w-7 h-7 rounded-lg bg-accent-light text-accent font-display font-bold text-xs flex items-center justify-center shrink-0">
                                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                            </div>
                            <span class="font-medium text-ink">{{ Auth::user()->name }}</span>
                            <i class="fas fa-chevron-down text-[10px] text-ink-muted"></i>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        {{-- User info header --}}
                        <div class="px-4 py-3 border-b border-border">
                            <div class="text-sm font-semibold text-ink">{{ Auth::user()->name }}</div>
                            <div class="text-xs text-ink-muted mt-0.5">{{ Auth::user()->email }}</div>
                        </div>

                        <x-dropdown-link :href="route('profile.edit')" class="flex items-center gap-2">
                            <i class="fas fa-user text-xs text-ink-muted w-4 text-center"></i>
                            {{ __('Profil Saya') }}
                        </x-dropdown-link>

                        <div class="border-t border-border my-1"></div>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault(); this.closest('form').submit();"
                                    class="flex items-center gap-2 text-danger hover:bg-danger-light">
                                <i class="fas fa-arrow-right-from-bracket text-xs w-4 text-center"></i>
                                {{ __('Keluar') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            {{-- ── Hamburger (mobile) ── --}}
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = !open"
                        class="inline-flex items-center justify-center p-2 rounded-lg text-ink-muted hover:text-ink hover:bg-s2 focus:outline-none transition duration-150">
                    <i class="fas text-base" :class="open ? 'fa-xmark' : 'fa-bars'"></i>
                </button>
            </div>

        </div>
    </div>

    {{-- ── Mobile Menu ── --}}
    <div :class="{'block': open, 'hidden': !open}" class="hidden sm:hidden border-t border-border">

        {{-- Nav links --}}
        <div class="px-4 py-3 space-y-1">
            <a href="{{ route('admin.dashboard') }}"
               class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150
                      {{ request()->routeIs('admin.dashboard')
                          ? 'bg-accent-light text-accent'
                          : 'text-ink-soft hover:text-ink hover:bg-s2' }}">
                <i class="fas fa-gauge-high text-xs w-4 text-center"></i>
                Dashboard
            </a>
            <a href="{{ route('public.landing') }}"
               class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-sm font-medium text-ink-soft hover:text-ink hover:bg-s2 transition-all duration-150">
                <i class="fas fa-arrow-up-right-from-square text-xs w-4 text-center"></i>
                Situs Publik
            </a>
        </div>

        {{-- User info + actions --}}
        <div class="px-4 py-3 border-t border-border">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-9 h-9 rounded-xl bg-accent-light text-accent font-display font-bold text-sm flex items-center justify-center shrink-0">
                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                </div>
                <div>
                    <div class="text-sm font-semibold text-ink">{{ Auth::user()->name }}</div>
                    <div class="text-xs text-ink-muted">{{ Auth::user()->email }}</div>
                </div>
            </div>

            <div class="space-y-1">
                <a href="{{ route('profile.edit') }}"
                   class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-sm text-ink-soft hover:text-ink hover:bg-s2 transition-all duration-150">
                    <i class="fas fa-user text-xs w-4 text-center"></i>
                    Profil Saya
                </a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit"
                            class="w-full flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-sm text-danger hover:bg-danger-light transition-all duration-150">
                        <i class="fas fa-arrow-right-from-bracket text-xs w-4 text-center"></i>
                        Keluar
                    </button>
                </form>
            </div>
        </div>
    </div>
</nav>
