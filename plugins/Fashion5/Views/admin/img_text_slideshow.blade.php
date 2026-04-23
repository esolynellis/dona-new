<template id="module-editor-img-text_slideshow-template">
  <div>
    <div class="module-edit-group">
      <div class="module-edit-title">{{ __('admin/builder.modules_select_image') }}</div>
      <draggable
        ghost-class="dragabble-ghost"
        :list="module.images"
        :options="{animation: 330, handle: '.icon-rank'}"
      >
        <div class="pb-images-selector" v-for="(item, index) in module.images" :key="index">
          <div class="selector-head" @click="itemShow(index)">
            <div class="left">
              <el-tooltip class="icon-rank" effect="dark" content="{{ __('admin/builder.text_drag_sort') }}" placement="left">
                <i class="el-icon-rank"></i>
              </el-tooltip>

              <img :src="thumbnail(item.image.src, 40, 40)" class="img-responsive">
            </div>

            <div class="right">
              <el-tooltip class="" effect="dark" content="{{ __('admin/builder.text_delete') }}" placement="left">
                <div class="remove-item" @click.stop="removeImage(index)"><i class="el-icon-delete"></i></div>
              </el-tooltip>
              <i :class="'el-icon-arrow-'+(item.show ? 'up' : 'down')"></i>
            </div>
          </div>
          <div :class="'pb-images-list ' + (item.show ? 'active' : '')">
            <div class="pb-images-top">
              <pb-image-selector :is-alt="true"  v-model="item.image" :is-language="false"></pb-image-selector>
              <div class="tag">{{ __('admin/builder.text_suggested_size') }} 2100 x 1200</div>
            </div>
            <link-selector v-model="item.link" style="margin-bottom: 10px"></link-selector>

            <div class="module-edit-title">{{ __('admin/builder.sub_title') }}</div>
            <text-i18n v-model="item.sub_title" style="margin-bottom: 10px"></text-i18n>

            <div class="module-edit-title">{{ __('admin/builder.text_title') }}</div>
            <text-i18n v-model="item.title" style="margin-bottom: 10px"></text-i18n>

            <div class="module-edit-title">{{ __('admin/builder.text_describe') }}</div>
            <text-i18n v-model="item.description" style="margin-bottom: 10px"></text-i18n>

            <div class="module-edit-title">{{ __('Fashion5::common.text_position') }}</div>
            <el-radio-group v-model="item.text_position" size="mini">
              <el-radio-button label="start">{{ __('Fashion5::common.text_start') }}</el-radio-button>
              <el-radio-button label="center">{{ __('Fashion5::common.text_center') }}</el-radio-button>
              <el-radio-button label="end">{{ __('Fashion5::common.text_end') }}</el-radio-button>
            </el-radio-group>
          </div>
        </div>
      </draggable>

      <div class="add-item">
        <el-button type="primary" size="small" @click="addImage" icon="el-icon-circle-plus-outline">{{ __('admin/builder.text_add_pictures') }}</el-button>
      </div>
    </div>
  </div>
</template>

<script type="text/javascript">

Vue.component('module-editor-img-text_slideshow', {
  template: '#module-editor-img-text_slideshow-template',

  props: ['module'],

  data: function () {
    return {
      // full: true
    }
  },

  watch: {
    module: {
      handler: function (val) {
        this.$emit('on-changed', val);
      },
      deep: true,
    }
  },

  methods: {
    removeImage(index) {
      this.module.images.splice(index, 1);
    },

    itemShow(index) {
      this.module.images.find((e, key) => {if (index != key) return e.show = false});
      this.module.images[index].show = !this.module.images[index].show;
    },

    addImage() {
      this.module.images.find(e => e.show = false);
      this.module.images.push({
        image: {
          src: 'catalog/demo/banner/text-image-banner-1.jpg',
          alt: languagesFill(''),
        },
        show: true,
        sub_title: languagesFill(''),
        title: languagesFill(''),
        description: languagesFill(''),
        text_position: 'start',
        link: {type: 'product', value:''}
      });
    }
  }
});

</script>

@push('footer-script')
  <script>
    register = @json($register);

    // 定义模块的配置项
    register.make = {
      style: {
        background_color: ''
      },
      floor: languagesFill(''),
      module_size: 'w-100',// 窄屏、宽屏、全屏
      images: [
        {
          image: 'catalog/fashion_3/banner/main-banner-1.jpg',
          sub_title: languagesFill(''),
          title: languagesFill(''),
          description: languagesFill(''),
          text_position: 'start',
          show: true,
          link: {
            type: 'product',
            value:''
          }
        },
        {
          image: 'catalog/fashion_3/banner/main-banner-3.jpg',
          show: false,
          sub_title: languagesFill(''),
          title: languagesFill(''),
          text_position: 'start',
          description: languagesFill(''),
          link: {
            type: 'product',
            value:''
          }
        }
      ]
    }

    app.source.modules.push(register)
  </script>
@endpush