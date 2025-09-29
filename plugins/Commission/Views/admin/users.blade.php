@extends('admin::layouts.master')

@section('title', $name)

@section('content')
  <div id="app">
    <el-row>
      <el-alert
        title="{{ __('Commission::users.recommend_title') }}"
        type="success"
        description="{{ __('Commission::users.recommend_desc', ['url' => env('APP_URL')]) }}">
      </el-alert>
      <ul>
        <li>{{ __('Commission::users.recommend_rule_1') }}</li>
        <li>{{ __('Commission::users.recommend_rule_2') }}</li>
        <li>{{ __('Commission::users.recommend_rule_3') }}</li>
      </ul>

    </el-row>
    <br/>
    <el-row :gutter="10">
      <el-col :span="4">
        <el-input placeholder="{{ __('Commission::users.search_placeholder') }}" v-model="usersPage.q" class="input-with-select" style="width: 100%">
        </el-input>
      </el-col>
      <el-col :span="4">
        <el-date-picker
          style="width:100%;"
          v-model="usersPage.start_at"
          type="datetime"
          value-format="yyyy-MM-dd HH:mm:ss"
          placeholder="{{ __('Commission::users.start_time') }}">
        </el-date-picker>
      </el-col>
      <el-col :span="4">
        <el-date-picker
          style="width:100%;"
          v-model="usersPage.end_at"
          type="datetime"
          value-format="yyyy-MM-dd HH:mm:ss"
          placeholder="{{ __('Commission::users.end_time') }}">
        </el-date-picker>
      </el-col>
      <el-col :span="2">
        <el-button @click="search" style="margin-left: 10px">{{ __('Commission::users.search') }}</el-button>
      </el-col>
    </el-row>
    <br/>
    <el-row v-if="parent != null">
      <el-col :span="24">
        <el-alert
          :title="parentTips"
          type="success"
          effect="dark"
          @close="clearParentCustomerID"
        >
        </el-alert>
      </el-col>
    </el-row>
    <br/>
    <el-table
      @cell-click="searchChild"
      :data="usersData"
      style="width: 100%">
      <el-table-column
        prop="date_at"
        label="{{ __('Commission::users.join_time') }}">
      </el-table-column>
      <el-table-column
        prop="customer.name"
        label="{{ __('Commission::users.name') }}">
      </el-table-column>
      <el-table-column
        width="180"
        prop="account"
        label="{{ __('Commission::users.account') }}">
        <template slot-scope="scope">
          <el-tooltip class="item" effect="dark" content="{{ __('Commission::users.tooltip_view_subordinate') }}" placement="top-start">
            <el-alert
              :title="scope.row.account"
              type="success"
              :closable="false">
            </el-alert>
          </el-tooltip>
        </template>
      </el-table-column>
      <el-table-column
        prop="rate1"
        width="100"
        label="{{ __('Commission::users.commission_rate') }}">
        <template slot-scope="scope">
          <span>{{ __('Commission::users.commission_level_1') }}：</span><span v-text="scope.row.rate1"></span>%
          <br/>
          <span>{{ __('Commission::users.commission_level_2') }}：</span><span v-text="scope.row.rate2"></span>%
          <br/>
          <span>{{ __('Commission::users.commission_level_3') }}：</span><span v-text="scope.row.rate3"></span>%
          <br/>

        </template>
      </el-table-column>
      <el-table-column
        prop="rate1"
        width="150"
        label="{{ __('Commission::users.commission') }}">
        <template slot-scope="scope">
          <span>{{ __('Commission::users.balance_available') }}：</span><span v-text="scope.row.balance_format"></span>
          <br/>
          <span>{{ __('Commission::users.balance_pending') }}：</span><span v-text="scope.row.balance_progress_format"></span>
          <br/>
          <span>{{ __('Commission::users.balance_total') }}：</span><span v-text="scope.row.total_amount_format"></span>
          <br/>

        </template>
      </el-table-column>

      <el-table-column
        prop="code"
        label="{{ __('Commission::users.referral_code') }}">
      </el-table-column>
      <el-table-column
        width="180"
        label="{{ __('Commission::users.direct_superior') }}">
        <template slot-scope="scope">
          <el-tooltip class="item" effect="dark" content="{{ __('Commission::users.tooltip_view_subordinate') }}" placement="top-start">
            <el-alert
              :title="scope.row.parent_user_name"
              type="success" :closable="false">
            </el-alert>
          </el-tooltip>
        </template>
      </el-table-column>
      <el-table-column
        label="{{ __('Commission::users.status') }}">
        <template slot-scope="scope">
          <p>
            <el-tag v-if="scope.row.status == 2" type="success">{{ __('Commission::users.status_normal') }}</el-tag>
            <el-tag v-if="scope.row.status == 3" type="danger">{{ __('Commission::users.status_frozen') }}</el-tag>
            <el-tag v-if="scope.row.status == 1" type="danger">{{ __('Commission::users.status_pending') }}</el-tag>
          </p>
          <p>
            <el-button type="success" icon="el-icon-check" circle size="mini" v-if="scope.row.status == 1"
                       @click="rowStatusChange( scope.row,2)"></el-button>
            <el-button type="danger" icon="el-icon-delete" circle size="mini" v-if="scope.row.status == 1"
                       @click="rowDelete(scope.row)"></el-button>

          </p>
        </template>
      </el-table-column>

      <el-table-column label="{{ __('Commission::users.operation') }}" width="350">
        <template slot-scope="scope">
          <!--<el-button
            size="mini"
            @click="handleEdit(scope.$index, scope.row,'edit')">
            编辑
          </el-button>-->
          <el-button
            size="mini"
            type="danger"
            v-if="scope.row.status == 2"
            @click="rowStatusChange( scope.row,3)">
            {{ __('Commission::users.btn_freeze') }}
          </el-button>
          <el-button
            v-if="scope.row.status == 3"
            size="mini"
            type="warning"
            @click="rowStatusChange( scope.row,2)">
            {{ __('Commission::users.btn_unfreeze') }}
          </el-button>

          <el-button
            size="mini"
            type="info"
            @click="closeAmount(scope.row)">
            {{ __('Commission::users.btn_settle') }}
          </el-button>
          <el-button
            size="mini"
            type="info"
            @click="openRatePop(scope.row)">
            {{ __('Commission::users.btn_set_rate') }}
          </el-button>
          <el-button size="mini" type="info"
                     @click="openBalancePop(scope.row)">{{ __('Commission::users.btn_adjust_balance') }}
          </el-button>
        </template>
      </el-table-column>
    </el-table>
    <div class="Pagination">
      <el-pagination
        @current-change="handleUsersPageChange"
        :current-page="usersPage.page"
        :page-size="usersPage.pageSize"
        layout="total, prev, pager, next"
        :total="usersPage.total">
      </el-pagination>
    </div>

    <el-dialog title="{{ __('Commission::users.dialog_rate_title') }}" :visible.sync="dialogFormVisible">
      <el-form :model="user">
        <el-form-item label="{{ __('Commission::users.rate_level_1') }}" label-width="120">
          <el-input-number v-model="user.rate1" autocomplete="off"></el-input-number>
        </el-form-item>
        <el-form-item label="{{ __('Commission::users.rate_level_2') }}" label-width="120">
          <el-input-number v-model="user.rate2" autocomplete="off"></el-input-number>
        </el-form-item>
        <el-form-item label="{{ __('Commission::users.rate_level_3') }}" label-width="120">
          <el-input-number v-model="user.rate3" autocomplete="off"></el-input-number>
        </el-form-item>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button @click="dialogFormVisible = false">{{ __('Commission::users.btn_cancel') }}</el-button>
        <el-button type="primary" @click="saveUserRate">{{ __('Commission::users.btn_confirm') }}</el-button>
      </div>
    </el-dialog>

    <el-dialog title="{{ __('Commission::users.dialog_balance_title') }}" :visible.sync="dialog1.dialogFormVisible">
      <el-form :model="user">
        <el-form-item label="{{ __('Commission::users.balance_amount') }}" label-width="120">
          <el-input-number v-model="user.balance" autocomplete="off" :min="user.min_balance"
                           :precision="2"></el-input-number>

          <el-alert title="{{ __('Commission::users.balance_tip') }}" style="margin-top: 10px"></el-alert>
        </el-form-item>
        <el-form-item label="{{ __('Commission::users.balance_note') }}" label-width="120">
          <el-input v-model="user.note" autocomplete="off" type="textarea"></el-input>
        </el-form-item>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button @click="dialog1.dialogFormVisible = false">{{ __('Commission::users.btn_cancel') }}</el-button>
        <el-button type="primary" @click="saveBalance">{{ __('Commission::users.btn_confirm') }}</el-button>
      </div>
    </el-dialog>

  </div>

@endsection

@push('footer')
  <script>
    new Vue({
      el: '#app',
      data: function () {
        return {
          usersPage: {
            page: 1,
            pageSize: 20,
            q: '',
            total: 0,
            parent_customer_id: 0,
            start_time: '',
            end_time: '',
            parentTips:''
          },
          usersData: [],
          systemRate: {rate1: 1, rate2: 2, rate3: 3},
          user: {customer_id: 0, rate1: -1, rate2: -1, rate3: -1, balance: 0, min_balance: 0, note: ""},
          dialogFormVisible: false,
          dialog1: {
            dialogFormVisible: false,
          },
          parent: null,
        }
      },
      created() {
        this.getUsers();
      },
      methods: {
        handleClick(tab, event) {
          console.log(tab, event);
        },
        openBalancePop(row) {
          this.user.customer_id = row.customer_id;
          this.user.balance = 0;
          this.user.note = "";
          this.user.min_balance = -row.balance;
          this.dialog1.dialogFormVisible = true;
        },
        saveBalance() {
          let that = this;
          $http.put('{{admin_route('admin_commission_user_balance_update')}}', {
            'customer_id': this.user.customer_id,
            'amount': this.user.balance,
            'note': this.user.note,
          }).then((res) => {
            layer.msg(res.msg);
            that.getUsers();
            this.dialog1.dialogFormVisible = false;
          })
        },

        search() {
          //this.usersPage.parent_customer_id = "";
          this.getUsers();
        },
        clearParentCustomerID() {
          this.usersPage.parent_customer_id = "";
          this.getUsers();
        },
        searchChild(row, column, cell, event) {
          if (column && column.label == "{{ __('Commission::users.direct_superior') }}") {
            this.usersPage.page = 1
            this.usersPage.p = ""
            this.usersPage.parent_customer_id = row.parent_user_id;
            this.getUsers();
          } else if (column && column.label == "{{ __('Commission::users.account') }}") {
            this.usersPage.page = 1
            this.usersPage.p = ""
            this.usersPage.parent_customer_id = row.customer_id;
            this.getUsers();
          }
          console.log(column.label);
        },

        getUsers() {
          let that = this;
          $http.get("{{admin_route('users')}}", this.usersPage).then((res) => {
            console.log(res);
            that.usersData = res.users.data;
            that.parent = res.parent;
            if(that.parent){
              that.parentTips = "{{ __('Commission::users.subordinate_alert') }}".replace('{name}', that.parent.name)
            }else{
              that.parentTips = "";
            }
            that.systemRate = res.systemRate;
            that.usersPage.page = res.users.current_page;
            that.usersPage.pageSize = res.users.per_page;
            that.usersPage.total = res.users.total;

          })
        },

        handleUsersPageChange(val) {
          this.usersPage.page = val;
          this.usersPage.offset = (val - 1) * this.usersPage.pageSize;
          this.getUsers()
        },

        openRatePop(row) {
          this.user.customer_id = row.customer_id;
          this.user.rate1 = row.rate1;
          this.user.rate2 = row.rate2;
          this.user.rate3 = row.rate3;
          this.dialogFormVisible = true;
        },
        saveUserRate() {
          let that = this;
          $http.post("{{admin_route('customer_user_rate')}}", this.user).then((res) => {
            if (res.code == 0) {
              layer.msg("{{ __('Commission::users.operation_success') }}")
              //window.location.reload();
              this.dialogFormVisible = false;
              that.getUsers()
            } else {
              layer.msg(res.msg)
            }
          })
        },
        rowStatusChange(row, status) {
          let that = this;
          let tip = "";
          if (row.status == 2 && status == 3) {
            tip = '{{ __('Commission::users.confirm_freeze', ['name' => '']) }}' + row.customer.name;
          } else if (row.status == 3 && status == 2) {
            tip = '{{ __('Commission::users.confirm_unfreeze', ['name' => '']) }}' + row.customer.name;
          } else if (row.status == 1 && status == 2) {
            tip = '{{ __('Commission::users.confirm_approve', ['name' => '']) }}' + row.customer.name;
          }
          layer.confirm(tip, {
            title: "{{ __('common.text_hint') }}",
            btn: ['{{ __('common.cancel') }}', '{{ __('common.confirm') }}'],
            area: ['400px'],
            btn2: () => {
              $http.post("{{admin_route('customer_user')}}", {
                'customer_id': row.customer.id,
                status: status
              }).then((res) => {
                if (res.code == 0) {
                  layer.msg("{{ __('Commission::users.operation_success') }}")
                  //window.location.reload();
                  that.getUsers()
                } else {
                  layer.msg(res.msg)
                }
              })
            }
          })
        },

        rowDelete(row) {
          let that = this;
          let tip = '{{ __('Commission::users.confirm_delete', ['name' => '']) }}' + row.customer.name;
          layer.confirm(tip, {
            title: "{{ __('common.text_hint') }}",
            btn: ['{{ __('common.cancel') }}', '{{ __('common.confirm') }}'],
            area: ['400px'],
            btn2: () => {
              $http.delete("{{admin_route('users_delete')}}", {'customer_id': row.customer.id}).then((res) => {
                if (res.code == 0) {
                  layer.msg(res.msg)
                  that.getUsers()
                } else {
                  layer.msg(res.msg)
                }
              })
            }
          })
        },
        closeAmount(row) {
          let that = this;
          let tip = '{{ __('Commission::users.confirm_settle') }}';
          layer.confirm(tip, {
            title: "{{ __('common.text_hint') }}",
            btn: ['{{ __('common.cancel') }}', '{{ __('common.confirm') }}'],
            area: ['400px'],
            btn2: () => {
              $http.put("{{admin_route('customer_user_balance_close')}}", {'customer_id': row.customer.id}).then((res) => {
                if (res.code == 0) {
                  layer.msg(res.msg)
                  that.getUsers()
                } else {
                  layer.msg(res.msg)
                }
              })
            }
          })
        },

        rateFormat(row) {
          let rate1 = row.rate_1;
          if (rate1 == -1) {
            rate1 = this.systemRate.rate1;
          }
          let rate2 = row.rate_2;
          if (rate2 == -1) {
            rate2 = this.systemRate.rate2;
          }
          let rate3 = row.rate_3;
          if (rate3 == -1) {
            rate3 = this.systemRate.rate3;
          }
          return rate1 + "%/" + rate2 + "%/" + rate3 + "%";
        }
      }
    })
  </script>
@endpush
