<!DOCTYPE html>
<html lang="en">
<head>
    <base href="">
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>@yield('title', 'Import Analytics')</title>
    <link rel="shortcut icon" href="{{ asset('metronic/media/logos/logo-demo3.png') }}" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Poppins:300,400,500,600,700" />
    <link href="{{ asset('metronic/plugins/custom/fullcalendar/fullcalendar.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('metronic/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('metronic/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
    <style>
        .select2-container .select2-selection--single {
            height: 42px;
            padding: 0.5rem 1rem;
            border-radius: 0.475rem;
            border: 1px solid #e4e6ef;
        }
        .form-select.form-select-solid + .select2-container .select2-selection--single {
            background-color: #f5f8fa;
            border-color: #f5f8fa;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 30px;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 40px;
            right: 12px;
        }
        .select2-container .select2-selection--multiple {
            min-height: 42px;
            border-radius: 0.475rem;
            border: 1px solid #e4e6ef;
        }
    </style>
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
    <script>
        (function(){
            const initSelect2 = (context = document) => {
                if (!window.jQuery || !jQuery.fn.select2) return;
                jQuery('select', context)
                    .not('.no-select2')
                    .each(function(){
                        const $el = jQuery(this);
                        if ($el.data('select2')) return;
                        const dropdownParent = $el.closest('.modal');
                        const placeholder = $el.attr('placeholder')
                            || $el.data('placeholder')
                            || $el.attr('data-placeholder')
                            || $el.find('option[value=""]').text()
                            || 'Silakan pilih';
                        if (!$el.find('option[value=""]').length) {
                            $el.prepend('<option value="" disabled selected hidden></option>');
                        }
                        $el.select2({
                            width: $el.data('select2-width') || '100%',
                            placeholder,
                            allowClear: !($el.prop('required')) && placeholder !== '',
                            dropdownParent: dropdownParent.length ? dropdownParent : jQuery(document.body),
                        });
                    });
            };

            const observeSelects = () => {
                if (!window.MutationObserver) return;
                const observer = new MutationObserver(mutations => {
                    for (const mutation of mutations) {
                        mutation.addedNodes.forEach(node => {
                            if (node.nodeType !== 1) return;
                            const isSelect = node.matches && node.matches('select');
                            const hasSelect = node.querySelector && node.querySelector('select');
                            if (isSelect || hasSelect) {
                                initSelect2(node);
                            }
                        });
                    }
                });
                observer.observe(document.body, { childList: true, subtree: true });
            };

            document.addEventListener('DOMContentLoaded', function(){
                initSelect2();
                observeSelects();
                document.addEventListener('select2:reinit', event => {
                    const ctx = event.detail && event.detail.context ? event.detail.context : document;
                    initSelect2(ctx);
                });
            });
        })();
    </script>
    @stack('scripts')
    @yield('scripts')
</body>
</html>
