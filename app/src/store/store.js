import Vue from 'vue';
import Vuex from 'vuex';
import * as user from 'src/store/modules/user';
import * as group from 'src/store/modules/group';
import * as study from 'src/store/modules/study';
import * as notification from 'src/store/modules/notification';

Vue.use(Vuex);

export default new Vuex.Store({
  modules: {
    user,
    group,
    study,
    notification
  }
});