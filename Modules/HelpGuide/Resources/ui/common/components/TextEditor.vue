<template>
  <div class="text-editor-container bg-red">
    <editor v-model="econtent" :init="computedEditorOptions" :placeholder="placeholder"
      :tinymce-script-src="$base_url + 'assets/libs/tinymce/tinymce.min.js'">
    </editor>

  </div>
</template>

<script>
import axios from "axios";
import Editor from '@tinymce/tinymce-vue'
plugins: [
  'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview', 'anchor',
  'searchreplace', 'visualblocks', 'code', 'fullscreen',
  'insertdatetime', 'media', 'table', 'paste', 'help', 'wordcount'
]


// var basicPlugins = 'autolink lists link image anchor searchreplace code table paste';


// var toolbar = 'undo redo | formatselect | bold italic underline strikethrough forecolor forecolor backcolor | \
//            alignleft aligncenter alignright alignjustify | \
//            bullist numlist outdent indent | removeformat | help | image media link | code preview searchreplace anchor'

var basicToolbar = 'undo redo | formatselect | bold italic forecolor backcolor | \
           bullist numlist outdent indent removeformat image | \
           alignleft aligncenter alignright alignjustify'

export default {
  data() {
    return {
      editorOptions: {
        height: 300,
        menubar: false,
        relative_urls: false,
        remove_script_host: false,
        convert_urls: false,
        automatic_uploads: true,
        images_reuse_filename: true,
        file_picker_types: 'image',
        paste_data_images: true,
        plugins: ['autolink', 'lists', 'link', 'image', 'anchor', 'searchreplace', 'code', 'table', 'paste'],
        toolbar: basicToolbar,
        images_upload_handler: this.handleImageAdded
      }

    };
  },
  components: {
    'editor': Editor
  },
  beforeMount() {
    if (this.editorHeight) this.editorOptions.height = this.editorHeight;
    if (this.uploadtype) this.editorOptions.images_upload_handler = this.handleImageAdded;
    if (this.options === 'full') {
      this.editorOptions.toolbar = toolbar;
      this.editorOptions.plugins = plugins;
    }
  },
  props: ["placeholder", "editorHeight", "value", 'options', 'uploadtype', 'scope'],
  mounted() {
    console.log("✅ TextEditor.vue recompiled and loaded");

  },
  computed: {
    computedEditorOptions() {
      return {
        ...this.editorOptions,
        plugins: this.options === 'full'
          ? [
            'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'print', 'preview', 'anchor',
            'searchreplace', 'visualblocks', 'code', 'fullscreen',
            'insertdatetime', 'media', 'table', 'paste', 'code', 'help', 'wordcount'
          ]
          : ['autolink', 'lists', 'link', 'image', 'anchor', 'searchreplace', 'code', 'table', 'paste'],
        toolbar: this.options === 'full'
          ? 'undo redo | formatselect | bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | help | image media link'
          : 'bold italic underline | bullist numlist | link image | code',
        images_upload_handler: this.uploadtype ? this.handleImageAdded : undefined
      };
    }
  },

  methods: {
    handleImageAdded(blobInfo, success, failure, progress) {
      const formData = new FormData();
      formData.append('file', blobInfo.blob(), blobInfo.filename());

      const scope = this.scope || 'global';
      formData.append('scope', scope);

      axios.post(this.$myaccount_url + "upload?type=" + this.uploadtype, formData)
        .then(response => {
          if (response.data && response.data.location) {
            success(response.data.location.trim());
          } else if (response.data && response.data.status === 'ok') {
            success(response.data.data);
          } else {
            failure('Invalid server response: ' + JSON.stringify(response.data));
          }
        })
        .catch(err => {
          failure('Image upload failed: ' + (err.response?.data?.message || err.message));
        });
    }

  }
}
</script>

<style>
.tox-statusbar__branding {
  display: none;
}

.text-editor-container textarea {
  display: none;
}
</style>
