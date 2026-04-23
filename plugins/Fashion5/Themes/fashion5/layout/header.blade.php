<header>
  @hook('header.before')
  <div class="top-wrap">
    <div class="container d-flex justify-content-between align-items-center">
      <div class="left d-flex align-items-center">
        @hookwrapper('header.top.currency')
        @if (currencies()->count() > 1)
          <div class="dropdown">
            <a class="btn dropdown-toggle ps-0" href="javascript:void(0)" role="button" id="currency-dropdown"
               data-toggle="dropdown"
               aria-expanded="false">
              @foreach (currencies() as $currency)
                @if ($currency->code == current_currency_code())
                  @if ($currency->symbol_left)
                    {{ $currency->symbol_left }}
                  @endif
                  {{ $currency->name }}
                  @if ($currency->symbol_right)
                    {{ $currency->symbol_right }}
                  @endif
                @endif
              @endforeach
            </a>

            <div class="dropdown-menu" aria-labelledby="currency-dropdown">
              @foreach (currencies() as $currency)
                <a class="dropdown-item"
                   href="{{ shop_route('currency.switch', [$currency->code]) }}">
                  @if ($currency->symbol_left)
                    {{ $currency->symbol_left }}
                  @endif
                  {{ $currency->name }}
                  @if ($currency->symbol_right)
                    {{ $currency->symbol_right }}
                  @endif
                </a>
              @endforeach
            </div>
          </div>
        @endif
        @endhookwrapper

        @hookwrapper('header.top.language')
        @if (count($languages) > 1)
          <div class="dropdown">
            <a class="btn dropdown-toggle" href="javascript:void(0)" role="button" id="language-dropdown"
               data-toggle="dropdown"
               aria-expanded="false">
              {{ current_language()->name }}
            </a>

            <div class="dropdown-menu" aria-labelledby="language-dropdown">
              @foreach ($languages as $language)
                <a class="dropdown-item" href="{{ shop_route('lang.switch', [$language->code]) }}">
                  {{ $language->name }}
                </a>
              @endforeach
            </div>
          </div>
        @endif
        @endhookwrapper

        @hook('header.top.left')
      </div>

      @hook('header.top.language.after')

      <div class="right nav">
        @if (system_setting('base.meta_title'))
        <div class="my-auto me-3 d-none d-md-block"><i class="bi bi-buildings-fill"></i> {{ system_setting('base.meta_title') }}</div>
        @endif

        <a href="{{ shop_route('account.wishlist.index') }}"><i class="bi bi-heart-fill me-2"></i>{{ __('shop/account/wishlist.index') }}</a>

        @hook('header.top.right')
      </div>
    </div>
  </div>

  <div class="header-content d-none d-lg-block">
    <div class="container navbar-expand-lg">
      @hookwrapper('header.menu.logo')
      <div class="logo"><a href="{{ shop_route('home.index') }}">
          <img src="{{ image_origin(system_setting('base.logo')) }}" class="img-fluid"></a>
      </div>
      @endhookwrapper
      <div class="search-wrap">
        <form action="{{ shop_route('products.search') }}" method="get">
          <div class="input-group input-group-lg">
            <input type="text" value="{{ request('keyword') }}" class="form-control" name="keyword" placeholder="{{ __('admin/builder.modules_keywords_search') }}">
            <button class="btn btn-primary" type="submit">{{ __('admin/builder.text_search') }}</button>
          </div>
        </form>
      </div>
      <div class="right-wrap">
        <div class="telephone-line">
          <div class="icon">
            {{-- <i class="bi bi-telephone-forward-fill me-2"></i> --}}
            <svg t="1741656278274" class="icon" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" p-id="16285" width="46" height="46"><path d="M371.510163 833.327734l0 1.209474c-6.551319 0-11.355619 0.503948-14.177726 0.503948l-3.326054 0c-69.813539 0-126.05409-55.837393-128.002688-125.550143-1.075088-34.23484 11.288426-67.360994 34.738787-92.256005 23.450361-24.895011 55.165463-39.475895 89.400302-40.450193 0.335965 0 3.762809-0.201579 4.065177-0.201579 26.810012 0 52.511339 8.399126 74.61784 24.491853 31.009575 10.952461 62.355115-4.367546 92.961532-45.422476 30.472031-40.752562 36.888964-73.0724 19.049219-96.018814-6.34974-4.569125-12.464304-10.011759-18.511675-16.462288-1.075088-1.041492-2.049387-2.150176-2.990089-3.326054-20.023518-22.610449-31.513523-51.234672-32.420628-80.732404-1.948597-70.754242 53.821603-129.884092 124.273476-131.866286 0.335965 0 3.628423-0.067193 3.930791-0.067193 59.264237 0 111.674786 41.995632 124.575844 99.916009 10.414917 32.151856 37.02335 159.51621-90.945742 330.589619C530.354443 816.227112 415.454393 833.327734 371.510163 833.327734L371.510163 833.327734zM367.377793 768.721653c19.116412 0.436755 119.569965-2.250966 229.531329-149.672434 112.313119-150.176382 86.746178-255.501428 80.463632-275.121787-7.156056-31.412733-33.058962-52.20897-62.388712-52.20897-37.02335 1.041492-64.639677 30.404838-63.698975 65.546783 0.571141 18.646061 9.407022 32.252646 16.697463 40.315807 0.235176 0.268772 0.470351 0.537544 0.67193 0.806316 4.065177 4.401142 7.861582 7.6936 11.657988 10.146145 2.385352 1.579036 4.602721 3.46044 6.484126 5.610616 19.788342 22.30808 58.088359 84.528809-13.405006 180.110868-46.161599 61.783974-93.499076 74.718629-125.046195 74.752226-16.227112 0-32.353435-3.326054-47.908618-9.81018-2.68772-1.108685-5.241055-2.620527-7.525617-4.434739-8.029565-6.316143-21.266588-13.875357-38.736771-13.875357-19.150008 0.537544-34.839577 7.59281-46.430371 19.889132-11.624391 12.363514-17.738955 28.523434-17.235008 45.489669 0.974299 34.671594 28.892995 61.750378 63.497396 61.750378 0.167983 0 0.201579 0.033597 0.067193 0.067193 1.209474-0.201579 2.519738-0.369562 3.292458-0.436755C360.759281 767.680161 364.152528 767.948933 367.377793 768.721653L367.377793 768.721653zM515.706367 967.478582c-60.708886 0-119.603561-11.859567-175.071393-35.175542-16.428691-6.954477-24.122291-25.835713-17.201411-42.264405 6.92088-16.462288 25.835713-24.088695 42.264405-17.235008 47.471863 19.956325 97.933815 30.136066 150.008399 30.136066 213.539392 0 387.233328-173.693936 387.233328-387.233328 0-213.505795-173.693936-387.233328-387.233328-387.233328S128.473039 302.200571 128.473039 515.739963c0 73.005207 20.426676 144.095414 59.062657 205.610616 9.440618 15.118428 4.90509 35.007559-10.179741 44.481774-15.084831 9.474215-35.007559 4.938686-44.481774-10.179741-45.153704-71.862926-68.973627-154.812699-68.973627-239.912649 0-249.084495 202.654124-451.772216 451.772216-451.772216s451.772216 202.654124 451.772216 451.772216C967.444986 764.824458 764.790862 967.478582 515.706367 967.478582L515.706367 967.478582z" p-id="16286"></path></svg>
          </div>
          <div class="text-wrap">
            <div>{{ __('Fashion5::common.telephone_call') }}</div>
            <div class="text-telephone">{{ system_setting('base.telephone') }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="d-none d-lg-block menu-box">
    <div class="container navbar-expand-lg">
      <div class="menu-wrap">
        @include('shared.menu-pc')
      </div>
      <div class="right-btn">
        <ul class="navbar-nav flex-row">
          @hookwrapper('header.menu.icon')
          <li class="nav-item dropdown">
            <a href="{{ shop_route('account.index') }}" class="nav-link d-flex align-items-center account-link">
              <div class="icon"><svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-user"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg></div>
              <div class="text-wrap">
                <span class="d-none d-md-block fw-bold">{{ __('shop/account.index') }}</span>
                <span class="d-none d-md-block a-name">
                  @auth('web_shop')
                    {{ current_customer()->name }}
                  @else
                   {{ __('Fashion5::common.sign_in') }}
                  @endauth
                </span>
              </div>
            </a>
            @auth('web_shop')
            <ul class="dropdown-menu">
                <li><a href="{{ shop_route('account.index') }}" class="dropdown-item"><i class="bi bi-person me-1"></i>
                    {{ __('shop/account.index') }}</a></li>
                <li><a href="{{ shop_route('account.order.index') }}" class="dropdown-item"><i
                      class="bi bi-clipboard-check me-1"></i> {{ __('shop/account/order.index') }}</a></li>
                <li><a href="{{ shop_route('account.wishlist.index') }}" class="dropdown-item"><i
                      class="bi bi-heart me-1"></i> {{ __('shop/account/wishlist.index') }}</a></li>
                <li>
                  <hr class="dropdown-divider opacity-100">
                </li>
                <li><a href="{{ shop_route('logout') }}" class="dropdown-item"><i class="bi bi-box-arrow-left me-1"></i>
                    {{ __('common.sign_out') }}</a></li>
              </ul>
            @endauth
          </li>
          @endhookwrapper
          <li class="nav-item">
            <a
              class="nav-link position-relative btn-right-cart {{ equal_route('shop.carts.index') ? 'page-cart' : '' }}"
              href="javascript:void(0);" role="button">
              <div class="icon"><svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-shopping-bag"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg></div>
              <div class="text-wrap">
                <span class="cart-badge-text fw-bold">{{ __('shop/carts.index') }}</span>
                <div><span>{{ __('shop/account/rma.quantity') }} <span class="cart-badge-quantity">0</span></span></div>
              </div>
            </a>
          </li>
        </ul>
      </div>
    </div>
  </div>

  <div class="header-mobile d-lg-none">
    <div class="mobile-content">
      <div class="left">
        <div class="mobile-open-menu"><svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg"><path d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg></div>
        <div class="mobile-open-search" href="#offcanvas-search-top" data-bs-toggle="offcanvas"
             aria-controls="offcanvasExample">
             <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg"><path d="M19 11.5a7.5 7.5 0 1 1-15 0 7.5 7.5 0 0 1 15 0m-2.107 5.42 3.08 3.08"/></svg>
        </div>
      </div>
      <div class="center"><a href="{{ shop_route('home.index') }}">
          <img src="{{ image_origin(system_setting('base.logo')) }}" class="img-fluid">
        </a>
      </div>
      <div class="right">
        <a href="{{ shop_route('account.index') }}" class="nav-link mb-account-icon">
          <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg"><path d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0"/><path d="M14.5 9.25a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0M17 19.5c-.317-6.187-9.683-6.187-10 0"/></svg>
          @if (strstr(current_route(), 'shop.account'))
            <span></span>
          @endif
        </a>
        <a href="{{ shop_route('carts.index') }}" class="nav-link ms-3 m-cart position-relative">
          <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" xmlns="http://www.w3.org/2000/svg"><path d="M16.5 21a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3m-8 0a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3M3.71 5.4h15.214c1.378 0 2.373 1.27 1.995 2.548l-1.654 5.6C19.01 14.408 18.196 15 17.27 15H8.112c-.927 0-1.742-.593-1.996-1.452zm0 0L3 3"/></svg>
          <span class="cart-badge-quantity"></span></a>
      </div>
    </div>
  </div>
  <div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvas-mobile-menu">
    <div class="offcanvas-header">
      <h5 class="offcanvas-title" id="offcanvasWithBothOptionsLabel">{{ __('common.menu') }}</h5>
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body mobile-menu-wrap">
      @include('shared.menu-mobile')
    </div>
  </div>

  <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvas-right-cart"
       aria-labelledby="offcanvasRightLabel"></div>

  <div class="offcanvas offcanvas-top" tabindex="-1" id="offcanvas-search-top" aria-labelledby="offcanvasTopLabel">
    <div class="offcanvas-header">
      <input type="text" class="form-control input-group-lg border-0 fs-4" focus placeholder="{{ __('common.input') }}"
             value="{{ request('keyword') }}" data-lang="{{ locale() === system_setting('base.locale') ? '' : session()->get('locale') }}">
      <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
  </div>
  @hook('header.after')
</header>
