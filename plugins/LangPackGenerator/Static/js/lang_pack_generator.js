
$(document, window.parent.document).on('click','.translate-btn',function () {

  translateSkus($(this, window.parent.document));
});
function translateSkus(obj){
  var from = obj.siblings('.from-locale-code').val();
  var to = obj.siblings('.to-locale-code').val();
  let $parents = obj.parents().parents().parents('.el-form-item');
  let langName = $parents.find('.el-input-group__prepend').html();
  let langValue = $parents.find('.el-input__inner').val();
  console.log(langName, langValue)
  var text = $parents.find('.input-' + from).val();
  if (!text) {
    return layer.msg(lang.translate_form, () => {});
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
function translateDefault(obj){
  var from = obj.siblings('.from-locale-code').val();
  var to = obj.siblings('.to-locale-code').val();
  let $parents = obj.parents('.input-locale-wrap').length ? obj.parents('.input-locale-wrap') : obj.parents('.col-auto');
  var text = $parents.find('.input-' + from).val();
  if (!text) {
    return layer.msg(lang.translate_form, () => {});
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
