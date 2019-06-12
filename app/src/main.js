import Vue from 'vue';
import App from './App.vue';
import DashboardPlugin from './dashboard-plugin';
import Axios from 'axios';
import router from './router';
import store from './store/store';
import { Message } from 'element-ui';

const he = require('he');
const $ = require('jquery');
window.$ = $;
window.jQuery = $;

// Require Froala Editor js file.
require('froala-editor/js/froala_editor.pkgd.min');

// Require Froala Editor css files.
require('froala-editor/css/froala_editor.pkgd.min.css');
require('font-awesome/css/font-awesome.css');
require('froala-editor/css/froala_style.min.css');

// Import and use Vue Froala lib.
import VueFroala from 'vue-froala-wysiwyg';
Vue.use(VueFroala);

window.$.FroalaEditor = window.$.FroalaEditor || {};
window.$.FroalaEditor.DEFAULTS = window.$.FroalaEditor.DEFAULTS || {};

window.$.FroalaEditor.DEFAULTS.key = window.scVars.froalaKey;
window.$.FroalaEditor.DEFAULTS.pastedImagesUploadURL = '';
window.$.FroalaEditor.DEFAULTS.imageUploadURL = '';

window.$.FroalaEditor.DEFAULTS.imageUploadToS3 = window.scFroalaS3;
window.$.FroalaEditor.DEFAULTS.imageMaxSize = 1024 * 1024 * 1;

Vue.config.productionTip = false;
Vue.config.devtools = true;

// plugin setup
Vue.use(DashboardPlugin);

Vue.prototype.$http = Axios;
Vue.prototype.$message = Message;
Vue.prototype.$decode = he.decode;

window.refTagger = {
  settings: {
    bibleReader: "bible.faithlife",
    bibleVersion      : 'ESV',
    roundCorners      : true,
    noSearchClassNames: ['study-meta', 'btn', 'el-select-dropdown'],
    noSearchTagNames: [],
    socialSharing     : []
  }
};
(
  function (d, t) {
    var g = d.createElement(t), s = d.getElementsByTagName(t)[0];
    g.src = '//api.reftagger.com/v2/RefTagger.js';
    s.parentNode.insertBefore(g, s);
  }(document, 'script')
);

/* eslint-disable no-new */
new Vue({
  el     : '#app',
  render : h => h(App),
  router,
  store,
  created() {
    store
      .dispatch('user/fetchMe')
      .then(() => {
        store.dispatch('group/fetchOrgs')
          .then(() => {
            store.dispatch('study/fetchStudies');
          });
        store.dispatch('group/fetchGroups');
      })
  },
  methods: {
    setCurrentGroup (groupID) {
      this.currentGroup = groupID;
      localStorage.setItem('currentGroup', groupID);
    },
    getCurrentGroup () {
      if (!this.currentGroup || undefined === this.currentGroup) {
        this.currentGroup = localStorage.getItem('currentGroup');
      }

      return this.currentGroup;
    },
    getCurrentGroupData() {
      if (!this.getCurrentGroup()) {
        return false;
      }

      for (let i = 0; i < this.$store.state.user.me.groups.length; i++) {
        if (this.getCurrentGroup() === this.$store.state.user.me.groups[i].id) {
          return this.$store.state.user.me.groups[i];
        }
      }

      return false;
    },
    updateUserData (data) {
      this.userData = data;
    },
    cleanLink (link) {
      if ('string' !== typeof link) {
        return link;
      }
      return link.replace(window.location.protocol + '//' + window.location.host, '');
    },
    reftag () {
      if (undefined === window.refTagger.tag) {
        return;
      }

      this.$nextTick(() => {
        window.refTagger.tag();
      });
    }
  }

});