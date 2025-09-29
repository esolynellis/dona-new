@extends('admin::layouts.master')

@section('title', $name)

@section('content')
  <div id="app">
    <el-row>
      <el-col :span="6">
        <el-button @click="groupAddRow(null)">{{ __('Commission::withdrawal_group.btn_add_platform') }}</el-button>
      </el-col>
    </el-row>
    <br/>
    <br>
    <el-table
      :data="groups"
      default-expand-all
      style="width: 100%">
      <el-table-column type="expand">
        <template slot-scope="props">
          <el-table
            border
            :data="props.row.items"
            style="width: 80%;margin-left: 80px;">
            <el-table-column
              label="{{ __('Commission::withdrawal_group.column_name') }}"
              prop="description.content">
            </el-table-column>
            <el-table-column
              label="{{ __('Commission::withdrawal_group.column_sort') }}"
              prop="show_sort">
            </el-table-column>
            <el-table-column label="{{ __('Commission::withdrawal_group.column_operation') }}">
              <template slot-scope="scope">
                <el-button
                  size="mini"
                  type="warning"
                  @click="openAddPop(scope.row.group_id,scope.row)"
                >
                  {{ __('Commission::withdrawal_group.btn_edit') }}
                </el-button>
                <el-button
                  size="mini"
                  type="danger"
                  @click="rowDelete(scope.row)">
                  {{ __('Commission::withdrawal_group.btn_delete') }}
                </el-button>
              </template>
            </el-table-column>
          </el-table>
        </template>
      </el-table-column>
      <el-table-column
        label="{{ __('Commission::withdrawal_group.column_bank') }}"
        prop="name">
      </el-table-column>
      <el-table-column label="{{ __('Commission::withdrawal_group.column_operation') }}">
        <template slot-scope="scope">
          <el-button @click="openAddPop(scope.row.id,null)" size="mini" type="success">{{ __('Commission::withdrawal_group.btn_add_config') }}</el-button>
          <el-button
            size="mini"
            type="warning"
            @click="groupAddRow(scope.row)"
          >
            {{ __('Commission::withdrawal_group.btn_edit') }}
          </el-button>
          <el-button @click="groupDelete(scope.row)"
                     size="mini"
                     type="danger">{{ __('Commission::withdrawal_group.btn_delete') }}
          </el-button>
        </template>
      </el-table-column>
    </el-table>

    <el-dialog title="{{ __('Commission::withdrawal_group.dialog_bank_title') }}" :visible.sync="groupDialog.dialogFormVisible">
      <el-form :model="group">
        <el-form-item label="{{ __('Commission::withdrawal_group.dialog_bank_name') }}">
          <el-input v-model="group.name" autocomplete="off"></el-input>
        </el-form-item>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button @click="groupDialog.dialogFormVisible = false">{{ __('Commission::withdrawal_group.btn_cancel') }}</el-button>
        <el-button type="primary" @click="saveGroup">{{ __('Commission::withdrawal_group.btn_confirm') }}</el-button>
      </div>
    </el-dialog>

    <el-dialog title="{{ __('Commission::withdrawal_group.dialog_item_title') }}" :visible.sync="dialog1.dialogFormVisible">
      <el-form :model="groupItem" label-position="top">
        <el-form-item label="{{ __('Commission::withdrawal_group.dialog_item_name') }}" label-width="120"
                      required
        >
          <ul class="nav nav-tabs mb-3" role="tablist" style="z-index: 1000000">
            @foreach ($languages2 as $language)
              <li class="nav-item" role="presentation">
                <button class="nav-link {{ $loop->first ? 'active' : '' }}" data-bs-toggle="tab"
                        data-bs-target="#tab-descriptions-{{ $language->code }}"
                        type="button">{{ $language->name }}</button>
              </li>
            @endforeach
          </ul>

          <div class="tab-content" style="z-index: 1000000">
            @foreach ($languages2 as $language)
              <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}"
                   id="tab-descriptions-{{ $language->code }}">
                <input name="descriptions" autocomplete="off" id="description-locale-{{$language->code}}"
                       data-locale="{{$language->code}}" class="form-control"></input>
              </div>
            @endforeach
          </div>
        </el-form-item>
        <el-form-item label="{{ __('Commission::withdrawal_group.dialog_item_sort') }}" label-width="120">
          <el-input-number v-model="groupItem.show_sort" autocomplete="off"></el-input-number>
        </el-form-item>
      </el-form>
      <div slot="footer" class="dialog-footer">
        <el-button @click="dialog1.dialogFormVisible = false">{{ __('Commission::withdrawal_group.btn_cancel') }}</el-button>
        <el-button type="primary" @click="saveItem">{{ __('Commission::withdrawal_group.btn_confirm') }}</el-button>
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
          groups:@json($groups?$groups:[]),
          group: {name: ""},
          groupItem: {
            id: 0,
            name: "",
            group_id: 0,
            show_sort: 10,
            descriptions: []
          },
          dialog1: {
            dialogFormVisible: false,
          },
          groupDialog: {
            dialogFormVisible: false,
            close: false,
          },
        }
      },
      created() {
      },
      methods: {

        groupAddRow(row) {
          if (row == null) {//新增
            this.group.id = 0;
            this.group.name = '';
          } else {
            this.group.id = row.id;
            this.group.name = row.name;
          }
          this.groupDialog.dialogFormVisible = true;
        },
        saveGroup() {
          $http.post("{{admin_route("withdrawal_group.store")}}", this.group).then((res) => {
            this.groupDialog.dialogFormVisible = false;
            window.location.reload();
          })

        },
        groupDelete(row) {
          let that = this;
          let tip = '{{ __('Commission::withdrawal_group.confirm_delete') }}'
          layer.confirm(tip, {
            title: "{{ __('common.text_hint') }}",
            btn: ['{{ __('common.cancel') }}', '{{ __('common.confirm') }}'],
            area: ['400px'],
            btn2: () => {
              $http.delete("{{admin_route("withdrawal_group.destory")}}", {id: row.id}).then((res) => {
                if (res.code == 0) {
                  window.location.reload();
                } else {
                  layer.msg(res.msg)
                }
              })
            }
          })
        },


        openAddPop(group_id, row) {
          if (row == null) {//新增
            this.groupItem.id = 0;
            this.groupItem.show_sort = 10;
            this.groupItem.name = "";
            this.groupItem.group_id = group_id;
            this.groupItem.descriptions = []
          } else {
            this.groupItem.id = row.id;
            this.groupItem.show_sort = row.show_sort;
            this.groupItem.name = row.name;
            this.groupItem.group_id = group_id;
            setTimeout(function () {
              let descriptions = row.descriptions;
              let len1 = descriptions.length;
              for (var i = 0; i < len1; i++) {
                var entry = descriptions[i];
                console.log("#description-locale-"+entry.locale)
                console.log(entry.content)
                $("#description-locale-"+entry.locale).val(entry.content)
              }
            },500)

          }
          this.dialog1.dialogFormVisible = true;
        },
        saveItem() {
          let that = this;
          var descriptions = $('input[name="descriptions"]');

          let len1 = descriptions.length;
          for (var i = 0; i < len1; i++) {
            let val = $(descriptions[i]).val();
            if (val.trim() == '') {
              alert("{{ __('Commission::withdrawal_group.msg_content_required') }}");
              return;
            }
            that.groupItem.descriptions[i] = {locale:$(descriptions[i]).attr("data-locale"),content:val};
          }
          $http.post('{{admin_route('withdrawal_group.item.store')}}', this.groupItem).then((res) => {
            layer.msg(res.msg);
            if (res.code == 0) {
              this.dialog1.dialogFormVisible = false;
              setTimeout(function () {
                window.location.reload();
              }, 1000)
            }
          })
        },
        groupDelete(row) {
          let that = this;
          let tip = '{{ __('Commission::withdrawal_group.confirm_delete') }}'
          layer.confirm(tip, {
            title: "{{ __('common.text_hint') }}",
            btn: ['{{ __('common.cancel') }}', '{{ __('common.confirm') }}'],
            area: ['400px'],
            btn2: () => {
              $http.delete("{{admin_route("withdrawal_group.destory")}}", {id: row.id}).then((res) => {
                if (res.code == 0) {
                  window.location.reload();
                } else {
                  layer.msg(res.msg)
                }
              })
            }
          })
        },
        rowDelete(row) {
          let that = this;
          let tip = '{{ __('Commission::withdrawal_group.confirm_delete_item', ['name' => '']) }}' + row.name;
          layer.confirm(tip, {
            title: "{{ __('common.text_hint') }}",
            btn: ['{{ __('common.cancel') }}', '{{ __('common.confirm') }}'],
            area: ['400px'],
            btn2: () => {
              $http.delete("{{admin_route('withdrawal_group.item.destory')}}", {'id': row.id}).then((res) => {
                if (res.code == '0') {
                  layer.msg("{{ __('Commission::withdrawal_group.msg_delete_success') }}")
                  setTimeout(function () {
                    window.location.reload();
                  }, 1000)
                } else {
                  layer.msg(res.msg)
                }
              })
            }
          })
        },

      }
    })
  </script>
@endpush
