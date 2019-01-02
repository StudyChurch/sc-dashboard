import GroupService from '@/services/GroupService.js';

export const namespaced = true;

export const state = {
  organizations: [],
  organization: {
    id : 0
  },
  groups     : [],
  groupsTotal: 0,
  group      : {
    id : 0
  },
};

export const mutations = {
  ADD_GROUP(state, group) {
    state.groups.unshift(group);
  },
  SET_GROUPS(state, groups) {
    state.groups = groups;
  },
  SET_GROUPS_TOTAL(state, groupsTotal) {
    state.groupsTotal = groupsTotal;
  },
  SET_GROUP(state, group) {
    state.group = group;
  },
  UPDATE_GROUP(state, group) {
    if (state.group.id === group.id) {
      state.group = group;
    }

    for (let i = 0; i < state.groups.length; i++) {
      if (state.groups[i].id === group.id) {
        state.groups[i] = group;
        break;
      }
    }
  },
  SET_ORGANIZATIONS(state, groups) {
    state.organizations = groups;
  },
  SET_ORGANIZATION(state, group) {
    state.organization = group;
  },
  UPDATE_ORGANIZATION(state, group) {
    if (state.organization.id === group.id) {
      state.organization = group;
    }

    for (let i = 0; i < state.organizations.length; i++) {
      if (state.organizations[i].id === group.id) {
        state.organizations[i] = group;
        break;
      }
    }
  }
};

export const actions = {

  /**
   * Handle Group Creation
   * @param commit
   * @param dispatch
   * @param group
   */
  createGroup({commit, dispatch}, group) {
    return GroupService.postGroup(group)
      .then(response => {
        commit('ADD_GROUP', response.data[0]);
        commit('SET_GROUP', response.data[0]);
        const notification = {
          type   : 'success',
          message: 'Your group has been created!'
        };
        return response.data[0];
//        dispatch('notification/add', notification, {root: true});
      })
      .catch(error => {
        const notification = {
          type   : 'error',
          message: 'There was a problem creating your group: ' + error.message
        };
//        dispatch('notification/add', notification, {root: true});
        throw error;
      });
  },

  upgradeUser({commit, dispatch, state}, {userID, groupID}) {
    return GroupService.upgradeUser(userID, groupID)
      .then(response => {
        let action = ('organization' === response.data[0].group_type) ? 'UPDATE_ORGANIZATION' : 'UPDATE_GROUP';
        commit(action, response.data[0]);
        return response.data[0];
      })
      .catch(error => {
        console.log(error);
      })
  },

  demoteUser({commit, dispatch, state}, {userID, groupID}) {
    return GroupService.demoteUser(userID, groupID)
      .then(response => {
        let action = ('organization' === response.data[0].group_type) ? 'UPDATE_ORGANIZATION' : 'UPDATE_GROUP';
        commit(action, response.data[0]);
        return response.data[0];
      })
      .catch(error => {
        console.log(error);
      })
  },

  removeUser({commit, dispatch, state}, {userID, groupID}) {
    return GroupService.removeUser(userID, groupID)
      .then(response => {
        let action = ('organization' === response.data[0].group_type) ? 'UPDATE_ORGANIZATION' : 'UPDATE_GROUP';
        commit(action, response.data[0]);
        return response.data[0];
      })
      .catch(error => {
        console.log(error);
      })
  },

  /**
   * Fetch groups
   *
   * @param commit
   * @param dispatch
   * @param state
   * @param rootState
   */
  fetchGroups({commit, dispatch, state, rootState}) {
    return GroupService.getGroups(rootState.user.me.id)
      .then(response => {
//        commit('SET_GROUPS_TOTAL', parseInt(response.headers['x-total-count']));
        commit('SET_GROUPS', response.data);
      })
      .catch(error => {
        const notification = {
          type   : 'error',
          message: 'There was a problem fetching groups: ' + error.message
        };
//        dispatch('notification/add', notification, {root: true});
      });
  },

  /**
   * Fetch single group
   *
   * @param commit
   * @param getters
   * @param state
   * @param id
   * @param key
   * @returns {*}
   */
  fetchGroup({commit, getters, state}, {id, key = 'id'}) {
    if (id === state.group[key]) {
      return state.group;
    }

    let group = ('id' === key) ? getters.getGroupById(id) : getters.getGroupBySlug(id);

    if (group) {
      commit('SET_GROUP', group);
      return group;
    } else {
      return GroupService.getGroup(id).then(response => {
        commit('SET_GROUP', response.data[0]);
        return response.data;
      });
    }
  },


  /**
   * Fetch organization groups
   *
   * @param commit
   * @param dispatch
   * @param state
   * @param rootState
   */
  fetchOrgs({commit, dispatch, state, rootState}) {
    return GroupService.getOrganizations(rootState.user.me.id)
      .then(response => {
        commit('SET_ORGANIZATIONS', response.data);
      })
      .catch(error => {
        const notification = {
          type   : 'error',
          message: 'There was a problem fetching groups: ' + error.message
        };
//        dispatch('notification/add', notification, {root: true});
      });
  },

  /**
   * Fetch single organization group
   *
   * @param commit
   * @param getters
   * @param state
   * @param id
   * @param key
   * @returns {*}
   */
  fetchOrg({commit, getters, state}, {id, key = 'id'}) {
    if (id === state.organization[key]) {
      return state.organization;
    }

    let group = ('id' === key) ? getters.getOrgById(id) : getters.getOrgBySlug(id);

    if (group) {
      commit('SET_ORGANIZATION', group);
      return group;
    } else {
      return GroupService.getOrganization(id).then(response => {
        commit('SET_ORGANIZATION', response.data[0]);
        return response.data;
      });
    }
  },

  fetchOrgGroups({commit, getters, state}, orgID) {
    return GroupService.getOrganizationGroups(orgID)
      .then(response => {
        return response.data;
      });
  }
};

export const getters = {

  /**
   * Get group by provided ID
   */
  getGroupById: state => id => {
    return state.groups.find(group => group.id === id);
  },

  /**
   * Get group by slug
   */
  getGroupBySlug: state => id => {
    return state.groups.find(group => group.slug === id);
  },

  /**
   * Get organization by id
   */
  getOrgById: state => id => {
    return state.organizations.find(group => group.id === id);
  },

  /**
   * Get organization by slug
   */
  getOrgBySlug: state => slug => {
    return state.organizations.find(group => group.slug === slug);
  },

  /**
   * Get group members
   *
   * @param state
   * @returns {*}
   */
  getGroupMembers: state => {
    if (undefined === state.group.members) {
      return [];
    }

    return state.group.members.members;
  },

  /**
   * Get organization admins
   *
   * @param state
   * @returns {*}
   */
  getGroupAdmins: state => {
    if (undefined === state.group.members) {
      return [];
    }

    return state.group.members.admins;
  },

  /**
   * Get organization members
   *
   * @param state
   * @returns {*}
   */
  getOrgMembers: state => {
    if (undefined === state.organization.members) {
      return [];
    }

    return state.organization.members.members;
  },

  /**
   * Get organization admins
   *
   * @param state
   * @returns {*}
   */
  getOrgAdmins: state => {
    if (undefined === state.organization.members) {
      return [];
    }

    return state.organization.members.admins;
  },

  /**
   * Return whether or not the current user is an admin of the current Org
   *
   * @param state
   * @param getters
   * @param rootState
   * @returns {boolean}
   */
  isOrgAdmin: (state, getters, rootState) => id => {
    let organization = (undefined !== id) ? getters.getOrgById(id) : state.organization;
    return organization.members.admins.includes(rootState.user.me.id);
  },

  /**
   * Return whether or not the current user is an admin of the current Org
   *
   * @param state
   * @param getters
   * @param rootState
   * @returns {boolean}
   */
  isGroupAdmin: (state, getters, rootState) => id => {
    let group = (undefined !== id) ? getters.getGroupById(id) : state.group;

    if (group.parent_id && getters.isOrgAdmin(group.parent_id)){
      return true;
    }

    return group.members.admins.includes(rootState.user.me.id);
  }

};