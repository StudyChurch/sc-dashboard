export const namespaced = true;

export const state = {
  alerts : [],
  alert : {},
};

export const mutations = {
  ADD_ALERT(state, alert) {
    state.alert = alert;
    state.alerts.unshift(alert);
  },
};

export const actions = {

  /**
   * Add Alert
   * @param commit
   * @param dispatch
   * @param alert
   */
  add({commit, dispatch}, alert) {
    commit('ADD_ALERT', alert);
  },
};

export const getters = {};