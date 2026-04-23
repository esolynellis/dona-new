@extends('admin::layouts.master')

@section('title', __('ProductImport::common.title'))

@section('body-class', 'page-pages-form')

@push('header')
  <script src="{{ asset('vendor/vue/Sortable.min.js') }}"></script>
  <script src="{{ asset('vendor/vue/vuedraggable.js') }}"></script>
  <script src="{{ asset('vendor/tinymce/5.9.1/tinymce.min.js') }}"></script>
@endpush

@section('content')
@if ($errors->has('error'))
  <x-admin-alert type="danger" msg="{{ $errors->first('error') }}" class="mt-4" />
@endif

<div class="card mb-3">
  <div class="card-header">{{ trans('ProductImport::common.text_export') }}</div>
  <div class="card-body">
    <form id="form-export" class="form-horizontal no-load" method="post" action="{{ admin_route("import.export") }}">
      @csrf
      <div id="product-export" style="">
        <div class="form-group">
          <label class="col-sm-2 control-label mb-2" for="input-export_way">{{ trans('ProductImport::common.entry_export_way') }}</label>
          <div class="col-sm-6 col-md-4">
            <label class="radio-inline"><input type="radio" name="export_way" value="pid" checked="checked">
              {{ trans('ProductImport::common.text_export_pid') }}
            </label>
            <label class="radio-inline ms-2"><input type="radio" name="export_way" value="page">
              {{ trans('ProductImport::common.text_export_page') }}
            </label>
          </div>
        </div>

        <div class="input-group mb-3 wp-400">
          <span class="input-group-text pid">{{ trans('ProductImport::common.entry_start_id') }}</span>
          <span class="input-group-text page">{{ trans('ProductImport::common.entry_number') }}</span>
          <input type="number" name="min" value="" placeholder="" id="input-min" class="form-control">
        </div>

        <div class="input-group mb-3 wp-400">
          <span class="input-group-text pid">{{ trans('ProductImport::common.entry_end_id') }}</span>
          <span class="input-group-text page">{{ trans('ProductImport::common.entry_index') }}</span>
          <input type="number" name="max" value="" placeholder="" id="input-max" class="form-control">
        </div>

        <button class="btn btn-primary">{{ trans('ProductImport::common.button_export') }}</button>
      </div>
    </form>
  </div>
</div>

<div class="card">
  <div class="card-header">{{ trans('ProductImport::common.text_import') }}</div>
  <div class="card-body">
    <form id="form-import" class="form-horizontal">
      <div class="input-group mb-2">
        <label for="input-file"> <input type="file" accept=".xlsx" class="form-control" name="file" id="input-file"></label>
      </div>

      <div class="text-secondary mb-3">{!! trans('ProductImport::common.help_file') !!}</div>
      <div class="d-flex align-items-center mb-3 progress-wrap">
        <div class="progress d-none wp-400" id="progress" role="progressbar" aria-label="Animated striped example" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100">
          <div id="progress-bar" class="progress-bar progress-bar-striped progress-bar-animated"><span></span></div>
        </div>
        <span class="ms-2 success-text d-none text-primary"><i class="bi bi-check-circle"></i> {{ __('common.success') }}</span>
      </div>
      <div class="form-group">
        <div class="col-md-10 col-md-offset-2">
          <button type="button" id="button-import" class="btn btn-primary">{{ trans('ProductImport::common.button_import') }}</button>
        </div>
      </div>
    </form>
  </div>
</div>

@endsection

@push('footer')
  <script>
    let index = 0;
    let rows = 0;

    $('#button-import').on('click', function() {
      let file = $('#form-import #input-file')[0].files[0];
      if (!file) {
        layer.msg('{{ trans('ProductImport::common.error_file_required') }}');
        return;
      }

      layer.confirm('{{ trans('ProductImport::common.text_upload_confirm') }}', {
        btn: ['{{ trans('ProductImport::common.button_cancel') }}', '{{ trans('ProductImport::common.button_import') }}']
      }, function() {
        layer.closeAll();
      }, function() {
        layer.closeAll();
        let formData = new FormData();
        formData.append('import_file', file);
        formData.append('type', $('#input-import-type').val());

        $.ajax({
          url: '{{ admin_route('import.upload') }}',
          type: 'post',
          data: formData,
          processData: false,
          contentType: false,
          dataType: 'json',
          beforeSend: function() {
            $('#progress').addClass('d-none');
            $('.progress-wrap .success-text').addClass('d-none');
          },
          success: function(json) {
            if (json.data.error) {
              layer.msg(json.data.error);
              return;
            }

            rows = json.data.count;
            index = 0;

            updateProgress(0);
            $('#progress').removeClass('d-none');
            job();
          }
        });
      });
    });

    function job() {
      $type = $('#input-import-type').val();
      $.ajax({
        url: '{{ admin_route('import.import') }}?index=' + index,
        type: 'post',
        contentType: false,
        dataType: 'json',
        success: function(json) {
          if (json.data.error) {
            layer.msg(json.data.error);
            return;
          }

          index++;

          let percent = index / rows * 100;
          updateProgress(percent.toFixed(2) + '%');
          setTimeout(() => {
            $('.progress-wrap .success-text').removeClass('d-none');
          }, 500);

          if (index < rows) {
            job();
          }
        }
      });
    }

    function updateProgress(percent) {
      $('#progress-bar').css('width', percent);
      $('#progress-bar span').html(percent);
    }

    $("input[value=page]").click(function() {
      $(".pid").hide();
      $(".page").show();
    });
    $("input[value=pid]").click(function() {
      $(".page").hide();
      $(".pid").show();
    });

    $("input[value=page]").trigger('click');
  </script>
@endpush



