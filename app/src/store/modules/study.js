import StudyService from '@/services/StudyService.js';

export const namespaced = true;

export const state = {
  studies     : [],
  studiesTotal: 0,
  study      : {},
};

export const mutations = {
  ADD_STUDY(state, study) {
    state.studies.unshift(study);
  },
  SET_STUDIES(state, studies) {
    state.studies = studies;
  },
  SET_STUDIES_TOTAL(state, studiesTotal) {
    state.studiesTotal = studiesTotal;
  },
  SET_STUDY(state, study) {
    state.study = study;
  }
};

export const actions = {
  createStudy({commit, dispatch}, study) {
    return StudyService.postStudy(study)
      .then(response => {
        commit('ADD_STUDY', response.data);
        commit('SET_STUDY', response.data);
        const notification = {
          type   : 'success',
          message: 'Your study has been created!'
        };
        return response.data;
      })
      .catch(error => {
        const notification = {
          type   : 'error',
          message: 'There was a problem creating your study: ' + error.message
        };
//        dispatch('notification/add', notification, {root: true});
        throw error;
      });
  },
  fetchStudies({commit, dispatch, rootState}, data) {
    return StudyService.getStudies(rootState.user.me.id, data)
      .then(response => {
        commit('SET_STUDIES_TOTAL', parseInt(response.headers['x-wp-total']));
        commit('SET_STUDIES', response.data);
      })
      .catch(error => {
        const notification = {
          type   : 'error',
          message: 'There was a problem fetching studies: ' + error.message
        };
        dispatch('notification/add', notification, {root: true});
      });
  },
  fetchStudy({commit, getters, state}, id) {
    if (id === state.study.id) {
      return state.study;
    }

    let study = getters.getStudyById(id);

    if (study) {
      commit('SET_STUDY', study);
      return study;
    } else {
      return StudyService.getStudy(id).then(response => {
        commit('SET_STUDY', response.data);
        return response.data;
      });
    }
  }
};
export const getters = {
  getStudyById: state => id => {
    return state.studies.find(study => study.id === id);
  }
};