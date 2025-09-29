@extends('layout.master')

@section('body-class', 'page-account-order-list')

@section('content')
  <div class="container">
    <div class="row">
      <x-shop-sidebar/>

      <div class="col-12 col-md-9"  id="app">
        <div class="card mb-4 account-card order-wrap">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="card-title">{{ __('shop/common2.menu.bank') }} [@{{balance}}]</h5>
          </div>
          <div class="card-body">

            <link rel="stylesheet" href="https://unpkg.com/element-ui/lib/theme-chalk/index.css">
            <div>
              <el-button type="success" size="small" v-if="showGetCash == 1"
                         @click="openCashDialog()">{{__('shop/common2.balance.apply')}}</el-button>

              <br/>
              <el-table
                v-if="ordersData != null && ordersData.length > 0"
                :data="ordersData"
                style="width: 100%">
                <el-table-column
                  label="{{__('common.created_at')}}"
                  prop="update_at"
                >
                </el-table-column>
                <el-table-column
                  :formatter="typeFormat"
                  label="{{__('shop/common2.balance.type')}}">
                </el-table-column>
                <el-table-column
                  prop="amount"
                  label="{{__('shop/common2.balance.amount')}}">
                </el-table-column>
                <el-table-column
                  :formatter="statusFormat"
                  label="{{__('common.status')}}">
                </el-table-column>
              </el-table>
              <div class="Pagination">
                <el-pagination
                  v-if="ordersData != null && ordersData.length > 0"
                  @current-change="handleOrdersPageChange"
                  :current-page="ordersPage.page"
                  :page-size="ordersPage.pageSize"
                  layout="total, prev, pager, next"
                  :total="ordersPage.total">
                </el-pagination>
              </div>
              <div v-if="ordersData == null || ordersData.length == 0">
                <x-shop-no-data/>
              </div>
              <el-dialog title="{{__('shop/common2.balance.apply')}}" :visible.sync="cashDialog.dialogFormVisible" width="90%"
                         :close-on-click-modal="cashDialog.close">
                <el-form :model="cashApply">
                  <el-form-item label="{{__('shop/common2.balance.amount')}}" label-width="70px">
                    <el-input-number :precision="2" v-model="cashApply.amount" :min="1"
                                     :max="balance"></el-input-number>
                  </el-form-item>

                </el-form>
                <div slot="footer" class="dialog-footer">
                  <el-button
                    @click="cashDialog.dialogFormVisible = false">{{ __('common.cancel') }}</el-button>
                  <el-button type="primary"
                             @click="handleSaveCashApply">{{ __('common.confirm') }}</el-button>
                </div>
              </el-dialog>

            </div>


          </div>
        </div>
      </div>
    </div>
  </div>
            <!-- import Vue before Element -->
            <script src="https://unpkg.com/vue@2/dist/vue.js"></script>
            <!-- import JavaScript -->
            <script src="https://unpkg.com/element-ui/lib/index.js"></script>

            <script>
              new Vue({
                el: '#app',
                data: function () {
                  return {
                    balance:0,
                    showGetCash:0,
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
                      amount: '',
                    }
                  }
                },
                created() {
                  this.getOrders();
                },
                methods: {
                  openCashDialog() {
                    this.cashApply.amount = this.balance;
                    this.cashDialog.dialogFormVisible = true;
                  },
                  handleSaveCashApply() {
                    if (this.cashApply.amount <= 0 || this.cashApply.account == '') {
                      this.$message.warning("{{__('shop/common2.apply_post_param_error')}}");
                      return;
                    }
                    $http.post(`/commission/shop/cash_apply`, this.cashApply).then((res) => {
                      if (res.code == 0) {
                        this.$message.success("{{__('common.success')}}");
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
                      that.balance = res.balance;
                      that.showGetCash = res.showGetCash;
                      console.log(res);
                      that.ordersPage.page = res.logs.current_page;
                      that.ordersPage.pageSize = res.logs.per_page;
                      that.ordersPage.total = res.logs.total;
                    })
                  },
                  handleOrdersPageChange(val) {
                    this.ordersPage.page = val;
                    this.ordersPage.offset = (val - 1) * this.ordersPage.pageSize;
                    this.getOrders()
                  },

                  typeFormat(row){
                    return "{{__('shop/common2.cash')}}"
                  },
                  statusFormat(row){
                    if(row.status == 'apply_unpaid'){
                      return "{{__('shop/common2.balance.wait')}}";
                    }else if(row.status == 'apply_paid'){
                      return "{{__('shop/common2.balance.paid')}}";
                    } else if(row.status == 'apply_refund'){
                      return "{{__('shop/common2.balance.refund')}}";
                    }
                  }

                }
              })
            </script>
            <style>
              .Pagination {
                display: flex;
                justify-content: flex-start;
                margin-top: 8px;
              }
            </style>
@endsection
