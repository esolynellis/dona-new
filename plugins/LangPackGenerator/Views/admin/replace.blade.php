@extends('admin::layouts.master')

@section('title', '语言替换')

@section('content')
  <div id="tax-classes-app" class="card" v-cloak>

    <div class="card-body h-min-600">

      <el-tabs v-model="configTab">
        <el-tab-pane label="查找语言" name="query">
          <x-admin::form.row title="语言包" class="mb-3">
            <el-select v-model="queryParams.lang" filterable
                       placeholder="{{ __('LangPackGenerator::common.from_code') }}" class="w-100">
              <el-option
                v-for="language, index in languages" :key="index"
                :key="language.code"
                :label="`${language.name}(${language.code})`"
                :value="language.code">
              </el-option>
            </el-select>
          </x-admin::form.row>
          <x-admin::form.row title="搜索关键字" class="mb-3">
            <el-input name="keywords" v-model="queryParams.keywords" placeholder="{{ __('搜索关键字') }}"></el-input>
          </x-admin::form.row>
          <x-admin::form.row title="替换成" class="mb-3">
            <el-input name="replace" v-model="queryParams.replace" placeholder="{{ __('替换文本') }}"></el-input>
          </x-admin::form.row>
          <div class="d-flex justify-content-between mb-4">
            <div></div>
            <button type="button" class="btn btn-primary" @click="search">{{ __('立即搜索') }}</button>
          </div>
        </el-tab-pane>
      </el-tabs>

      <table class="table">
        <thead>
        <tr>
          <th>{{ __('所属文件') }}  </th>
          <th>{{ __('搜索结果') }}  </th>
          <th>{{ __('替换文本') }}  </th>
          <th class="text-end">{{ __('common.action') }}</th>
        </tr>
        </thead>
        <tbody>

        <tr v-for="item, index in lists" :key="index">
          <td>

            <el-tag>@{{ item.name }}</el-tag>
          </td>

          <td>
            <div v-html="item.text"></div>
          </td>

          <td>
            <el-input name="replace" size="mini" v-model="item.replace" placeholder="{{ __('替换文本') }}"></el-input>

          </td>
          <td class="text-end">
            <el-button size="mini" @click="replace_handle(index)">{{ __('替换') }}</el-button>
          </td>
        </tr>
        </tbody>
      </table>
      <div class="d-flex justify-content-between mb-4">
        <div></div>
        <button type="button" class="btn btn-primary" @click="batch_replace_handle">{{ __('批量替换') }}</button>
      </div>
    </div>
  </div>
@endsection

@push('footer')
  <script>
    new Vue({
      el: '#tax-classes-app',
      data: {
        languages: @json($languages ?? []),
        configTab: 'query',
        lists: [],
        queryParams: {
          keywords: '',
          lang: '{{locale()}}'
        }
      },
      methods: {
        batch_replace_handle: function (){
          let data = this.lists;

          this.$alert('是否确认批量替换？批量替换请谨慎操作，否则可能导致语言包异常', '替换文本', {
            confirmButtonText: '确定',
            callback: action => {
              $http.post('lang_pack_generator_batch_replace', data, {
                'hload': false,
                'hmsg': false
              }).then((res) => {
                if (res.code === 200) {
                  this.$message({
                    type: 'success',
                    message: '替换成功，刷新后自动生效（如未生效请确认是否替换正确或存在缓存）'
                  });
                }else{
                  this.$message.error(res.msg)
                }
              })
            }
          });
        },
        replace_handle: function (index){
          let data = this.lists[index];
          this.$alert('是否确认替换成【'+data.replace+'】？', '替换文本', {
            confirmButtonText: '确定',
            callback: action => {
              $http.post('lang_pack_generator_replace', data, {
                'hload': false,
                'hmsg': false
              }).then((res) => {
                if (res.code === 200) {
                  this.$message({
                    type: 'success',
                    message: '替换成功，刷新后自动生效（如未生效请确认是否替换正确或存在缓存）'
                  });
                }else{
                  this.$message.error(res.msg)
                }
              })
            }
          });
        },
        search: function () {
          let that = this;
          $http.get('lang_pack_generator_replace', this.queryParams, {
            'hload': false,
            'hmsg': false
          }).then((res) => {
            that.lists = res.data;
            console.log(res)
          })
        }
      }
    })
  </script>
@endpush
