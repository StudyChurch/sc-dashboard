import Vue from 'vue';
import App from './App.vue';
import DashboardPlugin from './dashboard-plugin';
import Axios from 'axios';
import router from './router';
import store from './store/store';

import { setAccessToken, setUserData, getUserData } from './auth';

Vue.config.productionTip = false;
Vue.config.devtools = true;

// plugin setup
Vue.use(DashboardPlugin);

Vue.prototype.$http = Axios;


/* eslint-disable no-new */
let vm = new Vue({
  el     : '#app',
  render : h => h(App),
  router,
  store,
  data   : {
    userData    : getUserData(),
    currentGroup: 0,
  },
  created() {
    store
      .dispatch('user/fetchMe')
      .then(() => {
        store.dispatch('group/fetchGroups');
        store.dispatch('group/fetchOrgs');
        store.dispatch('study/fetchStudies');
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
    }
  }

});

/* We import element-ui variables at the end so they can override the default element-ui colors */
import './assets/sass/element_variables.scss';
