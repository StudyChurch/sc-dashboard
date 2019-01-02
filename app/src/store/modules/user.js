import UserService from '@/services/UserService';

export const namespaced = true;

export const state = {
  users     : [],
  usersTotal: 0,
  me        : {},
  user      : {},
  perPage   : 3
};

export const mutations = {
  ADD_USER(state, user) {
    state.users.push(user);
  },
  ADD_USERS(state, users) {
    state.users = state.users.concat(users);
  },
  SET_USERS(state, users) {
    state.users = users;
  },
  SET_USERS_TOTAL(state, usersTotal) {
    state.usersTotal = usersTotal;
  },
  SET_USER(state, user) {
    state.user = user;
  },
  SET_ME(state, user) {
    state.me = user;
  }
};

export const actions = {
  createUser({commit, dispatch}, user) {
    return UserService.postUser(user)
      .then(() => {
        commit('ADD_USER', user);
        commit('SET_USER', user);
        const notification = {
          type   : 'success',
          message: 'Your user has been created!'
        };
        dispatch('notification/add', notification, {root: true});
      })
      .catch(error => {
        const notification = {
          type   : 'error',
          message: 'There was a problem creating your user: ' + error.message
        };
        dispatch('notification/add', notification, {root: true});
        throw error;
      });
  },
  fetchUsers({commit, dispatch, state}, {page}) {
    return UserService.getUsers(state.perPage, page)
      .then(response => {
        commit('SET_USERS_TOTAL', parseInt(response.headers['x-total-count']));
        commit('SET_USERS', response.data);
      })
      .catch(error => {
        const notification = {
          type   : 'error',
          message: 'There was a problem fetching users: ' + error.message
        };
        dispatch('notification/add', notification, {root: true});
      });
  },
  fetchUsersByID({commit, getters, state}, ids) {
    let idsToFetch = [];

    for (let id of ids) {
      if (undefined === getters.getUserById(id)) {
        idsToFetch.push(id);
      }
    }

    if (! idsToFetch.length) {
      return;
    }

    return UserService.getUsersById(idsToFetch)
      .then(response => {
        commit('ADD_USERS', response.data);
      })
      .catch(error => {
      });
  },
  fetchUser({commit, getters, state}, id) {
    if (id === state.user.id) {
      return state.user;
    }

    var user = getters.getUserById(id);

    if (user) {
      commit('SET_USER', user);
      return user;
    } else {
      return UserService.getUser(id).then(response => {
        commit('SET_USER', response.data);
        return response.data;
      });
    }
  },
  fetchMe({commit, getters, state}) {
    return UserService.getMe().then(response => {
      commit('SET_ME', response.data);
      return response.data;
    });
  }
};
export const getters = {
  getMyId: state => {
    return state.me.id;
  },
  getUserById: (state, getters) => id => {
    if (id === getters.getMyId) {
      return state.me;
    }

    return state.users.find(user => user.id === id);
  },
  getAvatar: (state, getters) => id => {
    let user = getters.getUserById(id);
    return typeof user.avatar_urls.full !== "undefined" ? user.avatar_urls.full : '';
  },
  getName: (state, getters) => id => {
    let user = getters.getUserById(id);
    return user.name;
  },
};