@extends('layout.master')

@section('body-class', 'page-account-order-list')

@section('content')
  <div class="breadcrumb-wrap">
    <div class="container">
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
          @foreach($breadcrumbs as $breadcrumb)
            <li class="breadcrumb-item"><a href="{{$breadcrumb['url']}}">{{$breadcrumb['title']}}</a></li>
          @endforeach

        </ol>
      </nav>
    </div>
  </div>
  <div class="container">
    <div class="row">
      <x-shop-sidebar/>

      <div class="col-12 col-md-9">
        <div class="card mb-4 account-card order-wrap h-min-600">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title">{{ __('Commission::orders.commission_amount_logs') }}</h5>
          </div>
          <div class="card-body">
            <div class="table-responsive" id="app">
              <el-row>
                <el-col :span="4">
                  <div style="text-align: center">
                    <el-statistic prefix="{{current_currency_code()}}" group-separator="," :precision="2"
                                  :value="commissionUser.balance/100"
                                  title="{{__('Commission::orders.commission_user_balance')}}">
                    </el-statistic>
                  </div>
                </el-col>
                <el-col :span="4">
                  <div style="text-align: center">
                    <el-statistic prefix="{{current_currency_code()}}" group-separator="," :precision="2"
                                  :value="commissionUser.balance_progress/100"
                                  title="{{__('Commission::orders.balance_progress')}}">
                    </el-statistic>
                  </div>
                </el-col>
                <el-col :span="4">
                  <div style="text-align: center">
                    <el-statistic prefix="{{current_currency_code()}}" group-separator="," :precision="2"
                                  :value="(commissionUser.withdrawal_balance)/100"
                                  title="{{__('Commission::orders.withdrawn_amount')}}"></el-statistic>

                  </div>
                </el-col>
                <el-col :span="4">
                  <div style="text-align: center">
                    <el-statistic prefix="{{current_currency_code()}}" group-separator="," :precision="2"
                                  :value="commissionUser.total_amount/100"
                                  title="{{__('Commission::orders.commission_user_total_amount')}}"></el-statistic>

                  </div>
                </el-col>
                <el-col :span="8">
                  <el-button type="success" size="small" v-if="withdrawal_btn_display == 1"
                             @click="openCashDialog()">{{__('Commission::orders.get_cash')}}</el-button>


                </el-col>
              </el-row>
              <br/>
              <el-row>
                <el-col :span="4">
                  <el-input placeholder="{{__('Commission::orders.table_number')}}" v-model="ordersPage.q"
                            class="input-with-select" style="width: 100%">
                  </el-input>
                </el-col>
                <el-col :span="6" style="margin-left: 10px">
                  <el-date-picker
                    style="width:100%;"
                    v-model="ordersPage.start_at"
                    type="datetime"
                    value-format="yyyy-MM-dd HH:mm:ss"
                    placeholder="{{__('Commission::orders.start_at')}}">
                  </el-date-picker>
                </el-col>
                <el-col :span="6" style="margin-left: 10px">
                  <el-date-picker
                    style="width:100%;"
                    v-model="ordersPage.end_at"
                    type="datetime"
                    value-format="yyyy-MM-dd HH:mm:ss"
                    placeholder="{{__('Commission::orders.end_at')}}">
                  </el-date-picker>
                </el-col>
                <el-col :span="3">
                  <el-button @click="getOrders"
                             style="margin-left: 10px">{{__('Commission::orders.search_btn')}}</el-button>
                </el-col>
              <!--
                <el-col :span="3">
                  <el-button @click="getOrders"
                             style="margin-left: 10px">{{__('Commission::orders.export')}}</el-button>
                </el-col>
                -->
              </el-row>
              <br/>
              <el-table
                :data="ordersData"
                style="width: 100%">
                <el-table-column
                  prop="action_format"
                  label="{{__('Commission::orders.table_number')}}"
                  :show-overflow-tooltip="true"
                >
                </el-table-column>
              <!--<el-table-column
                  prop="order.currency_value"
                  label="{{__('Commission::orders.table_currency_rate')}}">
                </el-table-column>-->
                <el-table-column
                  prop="level"
                  label="{{__('Commission::orders.table_commission_level')}}">
                </el-table-column>
                <el-table-column
                  prop="rate"
                  label="{{__('Commission::orders.table_commission_rate')}}(%)">
                </el-table-column>
                <el-table-column
                  prop="c_amount"
                  label="{{__('Commission::orders.table_commission_amount')}}">
                </el-table-column>
              <!--<el-table-column
                  prop="c_amount_format"
                  label="{{__('Commission::orders.table_commission_default_amount')}}">
                </el-table-column>-->
                <el-table-column
                  prop="status_format"
                  label="{{__('Commission::orders.status_title')}}">
                </el-table-column>
                <el-table-column
                  prop="date_at"
                  label="{{__('Commission::orders.table_commission_date_at')}}">
                </el-table-column>

              </el-table>
              <div class="Pagination">
                <el-pagination
                  @current-change="handleOrdersPageChange"
                  :current-page="ordersPage.page"
                  :page-size="ordersPage.pageSize"
                  layout="total, prev, pager, next"
                  :total="ordersPage.total">
                </el-pagination>
              </div>

              <el-dialog title="{{__('Commission::orders.apply')}}" :visible.sync="cashDialog.dialogFormVisible"
                         :modal-append-to-body="false"
                         :append-to-body="true"
                         :close-on-click-modal="cashDialog.close" width="90%" style="z-index: 100000">


                <el-form :model="cashApply">
                  <el-form-item label="{{__('Commission::orders.amount')}}">
                    <el-input-number :precision="2" v-model="cashApply.amount" :min="1"
                                     :max="commissionUser.balance/100"></el-input-number>
                  </el-form-item>
                  <el-form-item label="{{__('Commission::orders.withdrawn_account')}}">
                    <el-select v-model="cashApply.withdrawal_id" placeholder="" @change="selectGroup">
                      <el-option
                        v-for="item in withdrawal_groups"
                        :key="item.id"
                        :label="item.name"
                        :value="item.id">
                      </el-option>
                    </el-select>
                  </el-form-item>

                  <el-form-item :label="item.description.content" :key="index"
                                v-for="(item,index) in cashApply.withdrawal_group_items">
                    <el-input v-model="item.value"></el-input>
                  </el-form-item>
                </el-form>
                <div slot="footer" class="dialog-footer">
                  <el-button
                    @click="cashDialog.dialogFormVisible = false">{{__('Commission::orders.cancel')}}</el-button>
                  <el-button type="primary"
                             @click="handleSaveCashApply">{{__('Commission::orders.submit')}}</el-button>
                </div>
              </el-dialog>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

@endsection

@push('header')
  <script src="{{ asset('vendor/vue/2.7/vue' . (!config('app.debug') ? '.min' : '') . '.js') }}"></script>
  <script src="{{ asset('vendor/element-ui/index.js') }}"></script>
  <link rel="stylesheet" href="{{ asset('vendor/element-ui/index.css') }}">
@endpush
@push('add-scripts')

  <script>
    new Vue({
      el: '#app',
      data: function () {
        return {
          ordersPage: {
            page: 1,
            pageSize: 20,
            q: '',
            total: 0,
            start_time: '',
            end_time: ''
          },
          ordersData: [],
          commissionUser: {},
          customer: {},
          shareUrl: '',
          cashDialog: {
            dialogFormVisible: false,
            close: false,
          },
          cashApply: {
            withdrawal_id: "",
            amount: '',
            withdrawal_group_items: []
          },
          withdrawal_btn_display:@json($withdrawal_btn_display),
          withdrawal_groups:@json($withdrawal_groups),
        }
      },
      created() {
        this.getOrders();
      },
      methods: {
        openCashDialog() {
          this.cashApply.withdrawal_id = this.withdrawal_groups[0].id;
          this.selectGroup()
          this.cashDialog.dialogFormVisible = true;
        },
        handleSaveCashApply() {

          let that = this;
          if (this.cashApply.amount <= 0) {
            that.$message.warning("{{__('Commission::orders.apply_post_param_error')}}");
            return;
          }

          let isEmpty = false;
          for (let i = 0; i < this.cashApply.withdrawal_group_items.length; i++) {
            if (this.cashApply.withdrawal_group_items[i].value.trim() == "") {
              isEmpty = true;
              that.$message.warning("{{__('Commission::orders.apply_post_param_error')}}");
              break;
            }
          }
          if (isEmpty) {
            return;
          }

          $http.post(`/commission/shop/cash_apply`, this.cashApply).then((res) => {
            if (res.code == 0) {
              this.$message.success("{{__('Commission::orders.success')}}");
              this.cashDialog.dialogFormVisible = false;
              this.getOrders();
            } else {
              this.$message.warning(res.msg)
            }
          })
        },
        getOrders() {
          let that = this;
          $http.get(`/commission/shop/orders`, this.ordersPage).then((res) => {
            //console.log(res);
            that.ordersData = res.logs.data;
            that.ordersPage.page = res.logs.current_page;
            that.ordersPage.pageSize = res.logs.per_page;
            that.ordersPage.total = res.logs.total;
            that.commissionUser = res.commission_user;
            that.customer = res.customer;
          })
        },
        handleOrdersPageChange(val) {
          this.ordersPage.page = val;
          this.ordersPage.offset = (val - 1) * this.ordersPage.pageSize;
          this.getOrders()
        },

        selectGroup() {
          //chargeCashApply.group_id
          let that = this;
          this.withdrawal_groups.forEach(function (group) {
            if (group.id == that.cashApply.withdrawal_id) {
              that.cashApply.withdrawal_group_items = group.items;
            }
          })
        }
      }

    })
  </script>
@endpush
