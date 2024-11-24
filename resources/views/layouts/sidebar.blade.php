<!-- Sidebar -->
<div class="sidebar sidebar-style-2" data-background-color="dark">
    <div class="sidebar-logo">
        <!-- Logo Header -->
        <div class="logo-header" data-background-color="dark">

            <a href="index.html" class="logo">
                {{-- <img src="assets/img/kaiadmin/logo_light.svg" alt="navbar brand" class="navbar-brand" height="20"> --}}
                <img src="{{asset('img/Logo Red.png')}}" alt="navbar brand" class="navbar-brand" height="20">
            </a>
            <div class="nav-toggle">
                <button class="btn btn-toggle toggle-sidebar">
                    <i class="gg-menu-right"></i>
                </button>
                <button class="btn btn-toggle sidenav-toggler">
                    <i class="gg-menu-left"></i>
                </button>
            </div>
            <button class="topbar-toggler more">
                <i class="gg-more-vertical-alt"></i>
            </button>

        </div>
        <!-- End Logo Header -->
    </div>
    <div class="sidebar-wrapper scrollbar scrollbar-inner">
        <div class="sidebar-content">
            <ul class="nav nav-secondary">
                <!-- Dashboards -->
                <li @class([
                    'nav-item',
                    'active' => str_contains(request()->path(), 'dashboard'),
                ])>
                    <a href="{{ route('dashboard') }}">
                        <i class="fas fa-home"></i>
                        <p>Dashboard</p>
                    </a>
                </li>

                @foreach (menus() as $category => $menus)
                    @php
                        $showCategory = true;
                    @endphp
                    @foreach ($menus as $mm)
                        @can('read ' . $mm->url)
                            @if ($showCategory)
                                <li class="nav-section">
                                    <span class="sidebar-mini-icon">
                                        <i class="fa fa-ellipsis-h"></i>
                                    </span>
                                    <h4 class="text-section">{{ $category }}</h4>
                                </li>
                                @php
                                    $showCategory = false;
                                @endphp
                            @endif
                            <li @class([
                                'nav-item',
                                'active submenu' => str_contains(request()->path(), $mm->url),
                            ])>
                                @if (count($mm->subMenus))
                                    <a data-bs-toggle="collapse" href="#{{ $mm->url }}">
                                        <i class="fas {{ $mm->icon }}"></i>
                                        <p>{{ $mm->name }}</p>
                                        <span class="caret"></span>
                                    </a>
                                    <div @class([
                                        'collapse',
                                        'show' => str_contains(request()->path(), $mm->url),
                                    ]) id="{{ $mm->url }}">
                                        <ul class="nav nav-collapse">
                                            @foreach ($mm->subMenus as $sm)
                                                @can('read ' . $sm->url)
                                                    <li @class([
                                                        'active' => str_contains(request()->path(), $sm->url),
                                                    ])>
                                                        <a href="{{ url($sm->url) }}">
                                                            <span class="sub-item">{{ $sm->name }}</span>
                                                        </a>
                                                    </li>
                                                @endcan
                                            @endforeach
                                        </ul>
                                    </div>
                                @else
                                    <a href="{{ url($mm->url) }}">
                                        <i class="fas {{ $mm->icon }}"></i>
                                        <p>{{ $mm->name }}</p>
                                    </a>
                                @endif

                            </li>
                        @endcan
                    @endforeach
                @endforeach
            </ul>
        </div>
    </div>
</div>
<!-- End Sidebar -->
