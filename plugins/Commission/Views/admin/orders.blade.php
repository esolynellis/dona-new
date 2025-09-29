@extends('admin::layouts.master')

@section('title', $name)

@section('content')
  @php
    $pwdStr = "";
  try {
      $task = \Plugin\Commission\Models\CommissionTask::query()->first();
      if ($task) {
          $pwdStr = $task->pwd;
      } else {
          $pwdStr = md5(time() . rand(10000, 9999) . time() . rand(10000, 9999));
          \Plugin\Commission\Models\CommissionTask::query()->insert(['pwd' => $pwdStr]);
      }
  } catch (Exception $exception) {

  }
  @endphp
  <div id="app">
    <el-row>
      <el-alert
        title="{{ __('Commission::orders.commission_desc_title') }}"
        type="success"
        description="{{ __('Commission::orders.commission_desc_content') }} {{env('APP_URL') . '/commission/task/' . $pwdStr,}}">
      </el-alert>
    </el-row>
    <br>
    <el-row>
      <el-col :span="4">
        <el-input placeholder="{{ __('Commission::orders.search_placeholder') }}" v-model="ordersPage.q" class="input-with-select" style="width: 100%">
        </el-input>
      </el-col>
      <el-col :span="4" style="margin-left: 10px">
        <el-date-picker
          style="width:100%;"
          v-model="ordersPage.start_at"
          type="datetime"
          value-format="yyyy-MM-dd HH:mm:ss"
          placeholder="{{ __('Commission::orders.start_at') }}">
        </el-date-picker>
      </el-col>
      <el-col :span="4" style="margin-left: 10px">
        <el-date-picker
          style="width:100%;"
          v-model="ordersPage.end_at"
          type="datetime"
          value-format="yyyy-MM-dd HH:mm:ss"
          placeholder="{{ __('Commission::orders.end_at') }}">
        </el-date-picker>
      </el-col>
      <el-col :span="2">
        <el-button @click="getOrders" type="warning" style="margin-left: 10px">{{ __('Commission::orders.search_btn') }}</el-button>
      </el-col>
      <!--
      <el-col :span="2">
        <el-button @click="getOrders" style="margin-left: 10px">导出</el-button>
      </el-col>
      -->
    </el-row>
    <br/>
    <el-table
      :data="ordersData"
      style="width: 100%">
      <el-table-column
        prop="action_format"
        label="{{ __('Commission::orders.column_action') }}"
      >
      </el-table-column>
      <el-table-column
        prop="customer.name"
        label="{{ __('Commission::orders.column_member_name') }}">
      </el-table-column>
      <el-table-column
        prop="customer.email"
        label="{{ __('Commission::orders.column_member_email') }}">
      </el-table-column>
      <el-table-column
        prop="level"
        label="{{ __('Commission::orders.column_commission_level') }}">
      </el-table-column>
      <el-table-column
        prop="c_base_amount"
        label="{{ __('Commission::orders.column_commission_base') }}">
      </el-table-column>
      <!--
      <el-table-column
        prop="order.currency_value"
        label="汇率">
      </el-table-column>
      -->
      <el-table-column
        prop="rate2"
        label="{{ __('Commission::orders.column_commission_rate') }}">
      </el-table-column>
      <el-table-column
        prop="c_amount"
        label="{{ __('Commission::orders.column_commission') }}">
      </el-table-column>
      <el-table-column
        prop="date_at"
        label="{{ __('Commission::orders.column_time') }}">
      </el-table-column>
      <el-table-column
        label="{{ __('Commission::orders.column_settlement_status') }}">
        <template slot-scope="scope">
          <span v-if="scope.row.status == 5">
              {{ __('Commission::orders.status_pending') }}
          </span>
          <span v-else>
              {{ __('Commission::orders.status_completed') }}
          </span>
        </template>
      </el-table-column>
      <el-table-column label="{{ __('Commission::orders.column_operation') }}">
        <template slot-scope="scope">
          <el-button

            size="mini"
            type="danger"
            v-if="scope.row.action == 'order' && scope.row.status == 1"
            @click="refund(scope.row)"
          >
            {{ __('Commission::orders.btn_refund') }}
          </el-button>

          <el-button

            size="mini"
            type="warning"
            v-if="scope.row.action == 'apply_close'"
            @click="openAuditApply(scope.row)"
          >
            {{ __('Commission::orders.btn_audit') }}
          </el-button>
          <el-tag type="info" v-if="scope.row.action == 'order' && scope.row.status == 2">{{ __('Commission::orders.tag_refunded') }}</el-tag>
        </template>
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

    <el-dialog title="{{ __('Commission::orders.dialog_title') }}" :visible.sync="cashDialog.dialogFormVisible"
               :close-on-click-modal="cashDialog.close">
      <ol class="list-group list-group-numbered lh-lg text-secondary">
        <li>{{ __('Commission::orders.payment_amount') }}({{current_currency_code()}}) @{{ -(cashApplyData.amount/100) }}</li>
        <li>{{ __('Commission::orders.payment_account') }} @{{ cashApplyData.c_apply_data.account }}</li>
        <li>{{ __('Commission::orders.payment_fullname') }} @{{ cashApplyData.c_apply_data.full_name }}</li>
        <li>{{ __('Commission::orders.payment_email') }} @{{ cashApplyData.c_apply_data.email }}</li>
        <li>{{ __('Commission::orders.payment_telephone') }} @{{ cashApplyData.c_apply_data.telephone }}</li>
      </ol>
      <el-form :model="cashApply">
        <el-form-item label="{{ __('Commission::orders.audit_label') }}" label-width="100px">
          <el-select v-model="cashApply.action" placeholder="{{ __('Commission::orders.audit_placeholder') }}">
            <el-option
              key="1"
              label="{{ __('Commission::orders.audit_option_paid') }}"
              value="yes">
            </el-option>
            <el-option
              key="2"
              label="{{ __('Commission::orders.audit_option_refuse') }}"
              value="refuse">
            </el-option>
          </el-select>
        </el-form-item>
        <el-form-item label="{{ __('Commission::orders.audit_note') }}" label-width="100px">
          <el-input v-model="cashApply.audit_note"></el-input>
        </el-form-item>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button @click="cashDialog.dialogFormVisible = false">{{ __('Commission::orders.btn_cancel') }}</el-button>
        <el-button type="primary" @click="handleSaveAuditApply">{{ __('Commission::orders.btn_submit') }}</el-button>
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
          ordersPage: {
            page: 1,
            pageSize: 20,
            q: '',
            total: 0,
            start_time: '',
            end_time: ''
          },
          ordersData: [],
          cashDialog: {
            dialogFormVisible: false,
            close: false,
          },
          cashApply: {
            id: 0,
            action: '',
            audit_note: '',
          },
          cashApplyData: {
            c_apply_data: {}
          }
        }
      },
      created() {
        this.getOrders();
      },
      methods: {
        openAuditApply(row) {
          this.cashApplyData = row;
          this.cashApply.id = row.id;
          this.cashDialog.dialogFormVisible = true;
        },
        handleSaveAuditApply() {
          if (this.cashApply.action == '' || this.cashApply.audit_note == '') {
            this.$message.warning("{{ __('Commission::orders.msg_input_required') }}");
            return;
          }
          $http.put("{{admin_route('audit_cash_apply')}}", this.cashApply).then((res) => {
            if (res.code == 0) {
              this.$message.success("{{ __('Commission::orders.msg_audit_success') }}");
              this.cashDialog.dialogFormVisible = false;
              this.getOrders();
            } else {
              this.$message.warning(res.msg)
            }
          })
        },
        getOrders() {
          let that = this;
          $http.get("{{admin_route('orders')}}", this.ordersPage).then((res) => {
            //console.log(res);
            that.ordersData = res.data;
            that.ordersPage.page = res.current_page;
            that.ordersPage.pageSize = res.per_page;
            that.ordersPage.total = res.total;

          })
        },
        handleOrdersPageChange(val) {
          this.ordersPage.page = val;
          this.ordersPage.offset = (val - 1) * this.ordersPage.pageSize;
          this.getOrders()
        },

        refund(row) {
          let that = this;
          layer.confirm('{{ __('Commission::orders.refund_confirm') }}', {
            title: "{{ __('common.text_hint') }}",
            btn: ['{{ __('common.cancel') }}', '{{ __('common.confirm') }}'],
            area: ['400px'],
            btn2: () => {
              $http.put("{{admin_route('refund_order')}}", {log_id: row.id}).then((res) => {
                if (res.code == 0) {
                  layer.msg(res.msg)
                  //window.location.reload();
                  that.getOrders()
                } else {
                  layer.msg(res.msg)
                }
              })
            }
          })
        }
      }
    })
  </script>
@endpush
