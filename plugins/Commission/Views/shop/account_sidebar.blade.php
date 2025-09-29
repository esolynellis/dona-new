<a class="list-group-item d-flex justify-content-between align-items-center {{ equal_route('shop.shop_order_index') ? 'active' : '' }}"
   href="{{ shop_route('shop_order_index') }}">
  <span>{{ __('Commission::orders.commission') }}</span></a>
<a class="list-group-item d-flex justify-content-between align-items-center {{ equal_route('shop.shop_users_index') ? 'active' : '' }}"
   href="{{ shop_route('shop_users_index') }}">
  <span>{{ __('Commission::orders.membership') }}</span></a>
