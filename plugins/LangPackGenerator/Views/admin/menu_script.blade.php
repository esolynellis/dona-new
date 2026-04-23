<!DOCTYPE html>
<html lang="{{ admin_locale() }}">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1">
  <base href="{{ $admin_base_url }}">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="asset" content="{{ asset('/') }}">
  <meta name="editor_language" content="{{ locale() }}">
  <script src="{{ asset('vendor/vue/2.7/vue' . (!config('app.debug') ? '.min' : '') . '.js') }}"></script>
  <script src="{{ asset('vendor/element-ui/index.js') }}"></script>
  <script src="{{ asset('vendor/jquery/jquery-3.6.0.min.js') }}"></script>
  <script src="{{ asset('vendor/layer/3.5.1/layer.js') }}"></script>
  <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
  <script src="{{ asset('vendor/cookie/js.cookie.min.js') }}"></script>
  <link href="{{ mix('/build/beike/admin/css/bootstrap.css') }}" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('vendor/element-ui/index.css') }}">

  <link href="{{ mix('build/beike/admin/css/app.css') }}" rel="stylesheet">

  @stack('header')
  {{-- <x-analytics /> --}}
</head>

@include('LangPackGenerator::components.auto-translation', ['type' => $component])

<script>
  let locales = @json(locales());
  $(document).on('click', '.translate-btn', function () {

    $(this).attr('disabled', true);
    $(this).html('<i class="el-icon-loading"></i>')
    translateMenu($(this));
  });
  function sleep(millisecond) {
    return new Promise(resolve => {
      setTimeout(() => {
        resolve()
      }, millisecond)
    })
  }
  async function translateMenu(obj) {
    let _obj = obj;
    obj.siblings('.from-locale-code').attr('disabled', true);
    obj.siblings('.to-locale-code').attr('disabled', true);

    var from = obj.siblings('.from-locale-code').val();
    var to = obj.siblings('.to-locale-code').val();
    var _field = obj.siblings('.to-article-field').val();
    let fields = [];
    if (_field === 'all') {
      let options = obj.siblings('.to-article-field').find('option');
      for (let i = 0; i < options.length; i++) {
        let optionValue = options.eq(i).attr('value');
        if (optionValue !== 'all') {
          fields.push({'name': options.eq(i).text(), 'value': options.eq(i).attr('value')})
        }
      }
    } else {
      fields.push({'name': obj.siblings('.to-article-field').find('option:selected').text(), 'value': _field})
    }
    for (let f = 0; f < fields.length; f++) {

      let $parents = $(window.frameElement).parent().parent().parent();
      let text = $parents.find('#pane-language-'+from+' input').val();

      let fromText = obj.siblings('.from-locale-code').find("option:selected").text();
      if (!text) {

        text = $parents.find('#pane-language-'+from+' textarea').val();
        if (!text) {
          parent.layer.msg('警告:翻译'+fromText+'【'+fields[f].name+'】'+(f+1)+'/'+fields.length+', 该文本为空, 自动过滤' , {
            shade: 0.01
          });
        }
        await sleep(1000);
      }


      // console.log(text, "获取文本")
      if (text) {
        parent.layer.msg('正在翻译:['+text+']中...', {
          shade: 0.01,
          time: 60000
        }, function(){
        });
        const token = document.querySelector('meta[name="csrf-token"]').content;
        $.ajax({
          url: '{{ admin_route('translation.translate') }}',
          type: 'POST',
          timeout:600000, //设置超时的时间10s
          headers: {'X-CSRF-TOKEN': token},
          data: {from, to, text},
          success: function (res) {
            // console.log("translation result=", res)
            parent.layer.closeAll();
            if (res.status === "fail"){
              parent.layer.msg(res.message );
            }else{
              let isError = res.data[0]?.error || '';
              if (isError) {
                parent.layer.alert(isError, {title:'语言翻译助手', icon:2});
              }else{
                res.data.forEach((e) => {
                  let obj =  $parents.find('#pane-language-'+e.locale+' input');
                  obj.val(e.result)
                  obj[0].dispatchEvent(new Event('input'));
                  obj.focus()
                  obj.blur();

                });
                parent.layer.msg('翻译完成' );
              }

            }

            _obj.attr('disabled', false)
            _obj.html('翻译')
            _obj.attr('disabled', false)
            _obj.siblings('.from-locale-code').attr('disabled', false);
            _obj.siblings('.to-locale-code').attr('disabled', false);
          },error: function (e) {
            parent.layer.closeAll();
            let res = e.responseJSON;
            // console.log(e)
            _obj.attr('disabled', false)
            _obj.html('翻译')
            _obj.attr('disabled', false)
            _obj.siblings('.from-locale-code').attr('disabled', false);
            _obj.siblings('.to-locale-code').attr('disabled', false);
            parent.layer.alert('翻译请求错误::'+res.message, {title:'语言翻译助手', icon:2});
          }
        })
        await sleep(2000);

      }else{
        parent.layer.msg('没有内容', {
          shade: 0.01,
        }, function(){
          _obj.attr('disabled', false)
          _obj.html('翻译')
          _obj.attr('disabled', false)
          _obj.siblings('.from-locale-code').attr('disabled', false);
          _obj.siblings('.to-locale-code').attr('disabled', false);
        });
      }

    }

  }

  function translateDefault(obj) {
    var from = obj.siblings('.from-locale-code').val();
    var to = obj.siblings('.to-locale-code').val();
    let $parents = obj.parents('.input-locale-wrap').length ? obj.parents('.input-locale-wrap') : obj.parents('.col-auto');
    var text = $parents.find('.input-' + from).val();
    if (!text) {
      return layer.msg(lang.translate_form, () => {
      });
    }

    // 发请求之前删除所有错样式
    $http.post('translation', {from, to, text}).then((res) => {
      $('.translation-error-text').remove()

      res.data.forEach((e) => {
        $parents.find('.input-' + e.locale).removeClass('translation-error');

        if (e.error) {
          $parents.find('.input-' + e.locale).addClass('translation-error');
          $parents.find('.input-' + e.locale).parents('.input-for-group').after('<div class="invalid-feedback translation-error-text mb-1 d-block" style="margin-left: 86px">' + e.error + '</div>');
        } else {
          $parents.find('.input-' + e.locale).val(e.result);
        }
      });
    })
  }

</script>

<style>
  html, body {
    background: none;
    height: 60px;
  }
</style>
