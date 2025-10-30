<!DOCTYPE html>
<html lang="en">
<head>
    <base href="">
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>@yield('title', 'Import Analytics')</title>
    <link rel="shortcut icon" href="{{ asset('metronic/media/logos/favicon.ico') }}" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700" />
    <link href="{{ asset('metronic/plugins/custom/fullcalendar/fullcalendar.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('metronic/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('metronic/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
    @stack('styles')
    @yield('styles')
    <meta name="description" content="Import Analytics dashboard" />
    <meta name="keywords" content="import analytics,dashboard,analytics" />
    <meta property="og:locale" content="en_US" />
    <meta property="og:type" content="website" />
</head>
<body id="kt_body" class="header-fixed header-tablet-and-mobile-fixed toolbar-enabled toolbar-fixed aside-enabled aside-fixed" style="--kt-toolbar-height:55px;--kt-toolbar-height-tablet-and-mobile:55px">
    <div class="d-flex flex-column flex-root">
        <div class="page d-flex flex-row flex-column-fluid">
            @include('layouts.partials.sidebar')
            <div class="wrapper d-flex flex-column flex-row-fluid" id="kt_wrapper">
            @include('layouts.partials.topbar')
                <!--begin::Toolbar-->
                <div class="toolbar" id="kt_toolbar">
                    <div class="container-fluid d-flex flex-stack py-3">
                        <div class="d-flex align-items-center flex-wrap me-3">
                            <h1 class="text-dark fw-bold fs-3 my-1 me-5">@yield('page_title', 'Dashboard')</h1>
                            <div class="text-muted fs-7">
                                @yield('page_breadcrumbs')
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2 my-1">
                            @yield('page_actions')
                        </div>
                    </div>
                </div>
                <!--end::Toolbar-->
                <div class="content d-flex flex-column flex-column-fluid" id="kt_content">
                    @yield('content')
                </div>
                @include('layouts.partials.footer')
            </div>
        </div>
    </div>

    <script src="{{ asset('metronic/plugins/global/plugins.bundle.js') }}"></script>
    <script src="{{ asset('metronic/js/scripts.bundle.js') }}"></script>
    <script src="{{ asset('metronic/plugins/custom/fullcalendar/fullcalendar.bundle.js') }}"></script>
    <script src="{{ asset('metronic/js/custom/widgets.js') }}"></script>
    <script src="{{ asset('metronic/js/custom/apps/chat/chat.js') }}"></script>
    <script src="{{ asset('metronic/js/custom/modals/create-app.js') }}"></script>
    <script src="{{ asset('metronic/js/custom/modals/upgrade-plan.js') }}"></script>
    @stack('scripts')
    @yield('scripts')
</body>
</html>
