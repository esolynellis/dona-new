@extends('admin::layouts.master')

@section('title', __('admin/common.huipi_categorys'))

@section('content')
  <div id="customer-app" class="card h-min-600" v-cloak>
    <div class="card-body">
      {{--      <div class="d-flex justify-content-between mb-4">--}}
      {{--        <button type="button" class="btn btn-primary" @click="checkedCreate('add', null)">{{ __('admin/brand.brands_create') }}</button>--}}
      {{--      </div>--}}
      <div class="table-push">
        <table class="table">
          <thead>
          <tr>
            <th>{{ __('common.id') }}</th>
            <th>{{ __('common.name') }}</th>
            <th>{{ __('common.image') }}</th>
          </tr>
          </thead>
          <tbody v-if="categorys.data.length">
          <tr v-for="category, index in categorys.data" :key="index">
            <td>@{{ category.category_id }}</td>
            <td>@{{ category.category_name }}</td>
            <td><div class="wh-50 border d-flex justify-content-center rounded-2 align-items-center"><img :src="thumbnail(category.image)" class="img-fluid rounded-2"></div></td>
          </tr>
          </tbody>
          <tbody v-else><tr><td colspan="7" class="border-0"><x-admin-no-data /></td></tr></tbody>
        </table>
      </div>

      <el-pagination v-if="categorys.data.length" layout="prev, pager, next" background :page-size="categorys.per_page" :current-page.sync="page"
                     :total="categorys.total" :current-page.sync="page"></el-pagination>
    </div>
  </div>
@endsection



@push('footer')
  @include('admin::shared.vue-image')
  <script>
    new Vue({
      el: '#customer-app',

      data: {
        categorys: @json($categorys ?? []),
        page: bk.getQueryString('page', 1) * 1,
        dialog: {
          show: false,
          index: null,
          type: 'add',
          form: {
            category_id: null,
            category_name: '',
            logo: '',
          },
        },

        rules: {
          category_name: [{required: true,message: '{{ __('common.error_required', ['name' => __('common.name')])}}',trigger: 'blur'}, ],
          {{--first: [{required: true,message: '{{ __('common.error_required', ['name' => __('brand.first_letter')])}}',trigger: 'blur'}, ],--}}
          logo: [{required: true,message: '{{ __('admin/brand.error_upload') }}',trigger: 'change'}, ],
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
        url() {
          const url = @json(admin_route('huipi_categorys.index'));

          if (this.page) {
            return url + '?page=' + this.page;
          }

          return url;
        },
      },

      methods: {
        loadData() {
          window.history.pushState('', '', this.url);
          $http.get(`huipi_categorys?page=${this.page}`).then((res) => {
            this.categorys = res.data.categorys;
          })
        },

        checkedCreate(type, index) {
          this.dialog.show = true
          this.dialog.type = type
          this.dialog.index = index

          if (type == 'edit') {
            let item = JSON.parse(JSON.stringify(this.categorys.data[index]));
            item.status = Number(item.status)
            this.dialog.form = item
          }
        },

        submit(form) {
          const self = this;
          const type = this.dialog.type == 'add' ? 'post' : 'put';
          const url = this.dialog.type == 'add' ? 'huipi_categorys' : 'huipi_categorys/' + this.dialog.form.category_id;

          this.$refs[form].validate((valid) => {
            if (!valid) {
              this.$message.error('{{ __('common.error_form') }}');
              return;
            }

            $http[type](url, this.dialog.form).then((res) => {
              this.$message.success(res.message);

              if (this.dialog.type == 'add') {
                this.categorys.data.unshift(res.data)
              } else {
                this.categorys.data[this.dialog.index] = res.data
              }
              this.dialog.show = false
              this.loadData()
            })
          });
        },

        deleteItem(id, index) {
          const self = this;
          this.$confirm('{{ __('common.confirm_delete') }}', '{{ __('common.text_hint') }}', {
            confirmButtonText: '{{ __('common.confirm') }}',
            cancelButtonText: '{{ __('common.cancel') }}',
            type: 'warning'
          }).then(() => {
            $http.delete('huipi_categorys/' + id).then((res) => {
              this.$message.success(res.message);
              self.categorys.data.splice(index, 1)
            })
          }).catch(()=>{})
        },

        closeDialog(form) {
          this.$refs[form].resetFields();
          Object.keys(this.dialog.form).forEach(key => this.dialog.form[key] = '')
          this.dialog.form.status = 1
        }
      }
    })
  </script>
@endpush
