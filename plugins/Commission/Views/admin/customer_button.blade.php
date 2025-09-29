@push('footer')
  <script>
    function addCommission(obj) {
      let customer_id = $(obj).parent().parent().data().item.id;
      let customer_name = $(obj).parent().parent().data().item.name;
      let tip = "";
      let status = $(obj).attr('data-status');
      if (status == 3) {
        tip = '{{ __('Commission::customer_button.confirm_freeze', ['name' => '']) }}' + customer_name;
      } else if (status == 2) {
        tip = '{{ __('Commission::customer_button.confirm_add', ['name' => '']) }}' + customer_name;
      }
      layer.confirm(tip, {
        title: "{{ __('common.text_hint') }}",
        btn: ['{{ __('common.cancel') }}', '{{ __('common.confirm') }}'],
        area: ['400px'],
        btn2: () => {
          $http.post(`/commission/users`, {'customer_id': customer_id, status: status}).then((res) => {
            if (res.code == 0) {
              layer.msg("{{ __('Commission::customer_button.operation_success') }}")
              window.location.reload();
            } else {
              layer.msg(res.msg)
            }
          })
        }
      })
    }

    $(function () {
      let customer_ids = [];
      $('[commission_add]').each(function () {
        let customer_id = $(this).parent().parent().data().item.id;
        customer_ids.push(customer_id);
      });
      if (customer_ids.length == 0) {
        return;
      }
      $http.post(`/commission/checkAdd`, {'customer_ids': customer_ids}).then((res) => {
        if (res.code == 0) {
          console.log(res);
          let normalCommissionIDs = res.normalCommissionIDs;
          let allCommissionIDs = res.allCommissionIDs;
          let applyCommissionIDs = res.applyCommissionIDs;
          let freezeCommissionIDs = res.freezeCommissionIDs;
          $('[commission_add]').each(function () {
            let customer_id = $(this).parent().parent().data().item.id;
            $(this).css("display", "");
            //显示状态
            let statusTd = $(this).parent().parent().find('[commission_status]');
            if ($.inArray(customer_id, normalCommissionIDs) >= 0) {
              statusTd.html('<span class="text-success">{{ __('Commission::customer_button.status_joined') }}</span>');
              $(this).html("{{ __('Commission::customer_button.btn_freeze') }}");
              $(this).attr("data-status", '3');
              $(this).addClass("btn-outline-danger");
              $(this).removeClass("btn-outline-success");
            } else if ($.inArray(customer_id, freezeCommissionIDs) >= 0) {
              statusTd.html('<span class="text-danger">{{ __('Commission::customer_button.status_frozen') }}</span>');
              $(this).html("{{ __('Commission::customer_button.btn_unfreeze') }}");
              $(this).attr("data-status", '2');
              $(this).addClass("btn-outline-success");
              $(this).removeClass("btn-outline-danger");
            } else if ($.inArray(customer_id, applyCommissionIDs) >= 0) {
              statusTd.html('<span class="text-warning">{{ __('Commission::customer_button.status_pending') }}</span>');
              $(this).html("{{ __('Commission::customer_button.btn_approve') }}");
              $(this).attr("data-status", '2');
              $(this).addClass("btn-outline-success");
              $(this).removeClass("btn-outline-danger");
            } else {
              statusTd.html('<span class="text-info">{{ __('Commission::customer_button.status_not_joined') }}</span>');
              $(this).html("{{ __('Commission::customer_button.btn_add') }}");
              $(this).attr("data-status", '2');
              $(this).addClass("btn-outline-success");
              $(this).removeClass("btn-outline-danger");
            }
          })
        } else {
          layer.msg(res.msg)
        }
      })
    });
  </script>
@endpush
