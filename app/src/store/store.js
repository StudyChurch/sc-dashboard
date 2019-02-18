import Vue from 'vue';
import Vuex from 'vuex';
import * as user from 'src/store/modules/user';
import * as group from 'src/store/modules/group';
import * as study from 'src/store/modules/study';
import * as alert from 'src/store/modules/alert';

Vue.use(Vuex);

export default new Vuex.Store({
  modules: {
    user,
    group,
    study,
    alert
  }
});