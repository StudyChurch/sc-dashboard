import UserService from '@/services/UserService';

export const namespaced = true;

export const state = {
  users     : [],
  usersTotal: 0,
  me        : {
    id         : 0,
    can        : {},
    avatar_urls: {},
    studies    : [],
  },
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
  },
  UPDATE_USER(state, user) {
    if (state.user.id === user.id) {
      state.user = user;
    }

    if (state.me.id === user.id) {
      state.me = user;
    }

    for (let i = 0; i < state.users.length; i++) {
      if (state.users[i].id === user.id) {
        state.users[i] = user;
        break;
      }
    }
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
        dispatch('alert/add', notification, {root: true});
      })
      .catch(error => {
        const notification = {
          type   : 'error',
          message: 'There was a problem creating your user: ' + error.message
        };
        dispatch('alert/add', notification, {root: true});
        throw error;
      });
  },
  updateUser({commit, dispatch, state}, {userID, data}) {
    return UserService.updateUser(userID, data)
      .then(response => {
        commit('UPDATE_USER', response.data);
        return response.data;
      })
      .catch(error => {
        console.log(error);
      });
  },
  updateAvatar({commit}, {userID, data}) {
    return UserService.updateAvatar(userID, data)
      .then(response => {
        commit('UPDATE_USER', response.data);
        return response.data;
      })
      .catch(error => {
        console.log(error);
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
        dispatch('alert/add', notification, {root: true});
      });
  },
  fetchUsersByID({commit, getters, dispatch}, ids) {
    let idsToFetch = [];

    for (let id of ids) {
      if (undefined === getters.getUserById(id)) {
        idsToFetch.push(id);
      }
    }

    if (!idsToFetch.length) {
      return;
    }

    return UserService.getUsersById(idsToFetch)
      .then(response => {
        commit('ADD_USERS', response.data);
      })
      .catch(error => {
        const notification = {
          type   : 'error',
          message: 'There was a problem fetching users: ' + error.message
        };
        dispatch('alert/add', notification, {root: true});
      });
  },
  fetchUser({commit, getters, state}, id) {
    if (id === state.user.id) {
      return state.user;
    }

    let user = getters.getUserById(id);

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
  fetchMe({commit}) {
    return UserService.getMe().then(response => {
      commit('SET_ME', response.data);
      return response.data;
    });
  }
};
export const getters = {
  getMyId       : state => {
    return state.me.id;
  },
  getUserById   : (state, getters) => id => {
    if (id === getters.getMyId) {
      return state.me;
    }

    let user = state.users.find(user => user.id === id);

    if (undefined === user) {
      return { id : 0, name : '' };
    }

    return user;
  },
  getAvatar     : (state, getters) => id => {
    let user = getters.getUserById(id);
    return user.avatar_urls !== undefined ? user.avatar_urls.full : '';
  },
  getName       : (state, getters) => id => {
    let user = getters.getUserById(id);
    return user !== undefined ? user.name : '';
  },
  getUsername   : (state, getters) => id => {
    let user = getters.getUserById(id);
    return user !== undefined ? user.user_login : '';
  },
  currentUserCan: (state) => cap => {
    return state.me.can[cap];
  }
};
