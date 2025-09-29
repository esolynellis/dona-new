@extends('admin::layouts.master')

@section('title', __('admin/common.huipi_goods'))

@section('content')
  <div id="customer-app" class="card h-min-600" v-cloak>
    <div class="card-body">
      {{--      <div class="d-flex justify-content-between mb-4">--}}
      {{--        <button type="button" class="btn btn-primary" @click="checkedCreate('add', null)">{{ __('admin/brand.brands_create') }}</button>--}}
      {{--      </div>--}}
      {{-- 批量同步 --}}
      <div class="d-flex justify-content-between mb-3">
        <el-button
          type="success"
          size="small"
          icon="el-icon-refresh"
          :loading="syncLoading"
          :disabled="checkedIds.length===0"
          @click="handleBatchSync">
          {{ __('common.batch_sync') }} (@{{ checkedIds.length }})
        </el-button>
      </div>
      <div class="table-push">
        <table class="table">
          <thead>
          <tr>
            <el-checkbox v-model="checkAll" :indeterminate="isIndeterminate" @change="handleCheckAll"></el-checkbox>
            <th>{{ __('common.id') }}</th>
            <th>{{ __('goods.goods_code') }}</th>
            <th>{{ __('goods.goods_name') }}</th>
            <th>{{ __('goods.goods_cover') }}</th>
{{--            <th>{{ __('goods.goods_mall_category') }}</th>--}}
{{--            <th>{{ __('goods.goods_brand') }}</th>--}}
            <th>{{ __('goods.gunit_max') }}</th>
            <th>{{ __('goods.gnum_midd') }}</th>
            <th>{{ __('goods.gunit_midd') }}</th>
            <th>{{ __('goods.gnum_min') }}</th>
            <th>{{ __('goods.gunit_min') }}</th>
            <th>{{ __('goods.cash_price_big') }}</th>
            <th>{{ __('goods.cash_price_small') }}</th>
            <th>{{ __('goods.quality') }}</th>
            <th>{{ __('goods.unit_min') }}</th>
            <th>{{ __('goods.buy_num_min') }}</th>
          </tr>
          </thead>
          <tbody v-if="goods.data.length">
          <tr v-for="good, index in goods.data" :key="index">
            <td><el-checkbox v-model="checkedIds" :label="good.goods_id"></el-checkbox></td>
            <td>@{{ good.goods_code }}</td>
            <td>@{{ good.goods_name }}</td>
            <td><div class="wh-50 border d-flex justify-content-center rounded-2 align-items-center"><img :src="thumbnail(good.goods_cover)" class="img-fluid rounded-2"></div></td>
{{--            <td>@{{ good.goods_mall_category }}</td>--}}
{{--            <td>@{{ good.goods_brand }}</td>--}}
            <td>@{{ good.gunit_max }}</td>
            <td>@{{ good.gnum_midd }}</td>
            <td>@{{ good.gunit_midd }}</td>
            <td>@{{ good.gnum_min }}</td>
            <td>@{{ good.gunit_min }}</td>
            <td>@{{ good.cash_price_big }}</td>
            <td>@{{ good.cash_price_small }}</td>
            <td>@{{ good.quality }}</td>
            <td>@{{ good.unit_min }}</td>
            <td>@{{ good.buy_num_min }}</td>
          </tr>
          </tbody>
          <tbody v-else><tr><td colspan="7" class="border-0"><x-admin-no-data /></td></tr></tbody>
        </table>
      </div>

      <el-pagination v-if="goods.data.length" layout="prev, pager, next" background :page-size="goods.per_page" :current-page.sync="page"
                     :total="goods.total" :current-page.sync="page"></el-pagination>
    </div>
  </div>
@endsection



@push('footer')
  @include('admin::shared.vue-image')
  <script>
    new Vue({
      el: '#customer-app',

      data: {
        goods: @json($goods ?? []),
        page: bk.getQueryString('page', 1) * 1,
        checkedIds: [],        // 已选商品ID
        checkAll: false,       // 全选框状态
        syncLoading: false,
        dialog: {
          show: false,
          index: null,
          type: 'add',
          form: {
            goods_id: null,
            goods_name: '',
            logo: '',
            sort: '',
          },
        },

        rules: {
          goods_name: [{required: true,message: '{{ __('common.error_required', ['name' => __('common.name')])}}',trigger: 'blur'}, ],
          {{--first: [{required: true,message: '{{ __('common.error_required', ['name' => __('brand.first_letter')])}}',trigger: 'blur'}, ],--}}
          goods_image: [{required: true,message: '{{ __('admin/brand.error_upload') }}',trigger: 'change'}, ],
        }
      },
      watch: {
        page: function() {
          this.loadData();
        },
      },

      mounted() {
        bk.ajaxPageReloadData(this)
      },

      computed: {
        /* 半选状态 */
        isIndeterminate() {
          return this.checkedIds.length > 0 && this.checkedIds.length < this.goods.data.length;
        },
        url() {
          const url = @json(admin_route('huipi_goods.index'));

          if (this.page) {
            return url + '?page=' + this.page;
          }

          return url;
        },
      },

      methods: {
        loadData() {
          window.history.pushState('', '', this.url);
          $http.get(`huipi_goods?page=${this.page}`).then((res) => {
            this.goods = res.data.goods;
          })
        },
        /* 全选 / 取消全选 */
        handleCheckAll(val) {
          this.checkedIds = val ? this.goods.data.map(g => g.goods_id) : [];
        },

        /* 批量同步：把 ids 一次性 POST 给服务端 */
        handleBatchSync() {
          if (!this.checkedIds.length) return;
          this.$confirm(`确定同步已选的 ${this.checkedIds.length} 件商品？`, '提示', {
            confirmButtonText: '确定',
            cancelButtonText: '取消',
            type: 'warning'
          }).then(() => {
            this.syncLoading = true;
            return $http.post('huipi_goods/batchSync', { ids: this.checkedIds });
          }).then(res => {
            this.$message.success(res.message || '同步完成');
            this.checkedIds = [];   // 清空选择
            this.checkAll = false;
            this.loadData();        // 刷新列表
          }).catch(e => {
            if (e !== 'cancel') this.$message.error(e.message || '同步失败');
          }).finally(() => {
            this.syncLoading = false;
          });
        },
      }
    })
  </script>
@endpush
