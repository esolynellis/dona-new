<iframe class="langIframe" frameborder="no" border="0" marginwidth="0" marginheight="0"   allowtransparency="yes" width="100%" height="60px" src="{{ admin_route('LangPackGenerator.component', ['component'  => $component??'common']) }}"></iframe>

@if(in_array($component,['product', 'article']))
<div style="color: #aaa;font-size: 10px">
  <div ><i class="bi bi-info-circle"></i> 说明: </div>
  <div  >第1栏：翻译原文本内容， 第2栏：需要翻译的语言    </div>
  <div  > 内容翻译:不一定稳定，翻译完成后需自行检查翻译样式是否混乱， </div>
  <div  > 翻译内容源于翻译平台，不确保 100% 翻译结果正确 </div>
</div>
@endif
