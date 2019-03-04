import StudyService from '@/services/StudyService.js';

export const namespaced = true;

export const state = {
  studies     : [],
  studiesTotal: 0,
  study       : {
    id        : 0,
    thumbnail : '',
    title     : {
      rendered: '',
    },
    excerpt   : {
      rendered: '',
    },
    navigation: []
  },
  chapter     : {
    id      : 0,
    title   : {
      rendered: '',
      raw     : '',
    },
    excerpt : {
      rendered: '',
      raw     : '',
    },
    elements: [],
  },
  navigation  : [],
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
  },
  SET_CHAPTER(state, chapter) {
    state.chapter = chapter;
  },
  SET_NAVIGATION(state, navigation) {
    state.study.navigation = navigation;
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
        dispatch('alert/add', notification, {root: true});
        return response.data;
      })
      .catch(error => {
        const notification = {
          type   : 'error',
          message: 'There was a problem creating your study: ' + error.message
        };
        dispatch('alert/add', notification, {root: true});
        throw error;
      });
  },
  deleteStudy({commit, dispatch}, study) {
    return StudyService.deleteStudy(study)
      .then(response => {
        const notification = {
          type   : 'success',
          message: 'Your study has been deleted!'
        };
        dispatch('alert/add', notification, {root: true});
        return response.data;
      })
      .catch(error => {
        const notification = {
          type   : 'error',
          message: 'There was a problem deleting your study: ' + error.message
        };
        dispatch('alert/add', notification, {root: true});
        throw error;
      });
  },
  fetchStudies({commit, dispatch, rootState}, data) {
    data = data || {};
    data.params = data.params || {};
    data.params.organizations = rootState.group.organizations.map(org => org.id);

    return StudyService.getStudies(data)
      .then(response => {
        commit('SET_STUDIES_TOTAL', parseInt(response.headers['x-wp-total']));
        commit('SET_STUDIES', response.data);
      })
      .catch(error => {
        const notification = {
          type   : 'error',
          message: 'There was a problem fetching studies: ' + error.message
        };
        dispatch('alert/add', notification, {root: true});
      });
  },
  fetchStudy({commit, getters, state}, {id, params = {}}) {
    if (id === state.study.id) {
      return state.study;
    }

    let study = getters.getStudyById(id);

    if (study) {
      commit('SET_STUDY', study);
      return study;
    } else {
      return StudyService.getStudy(id, params).then(response => {
        commit('SET_STUDY', response.data);
        return response.data;
      });
    }
  },
  updateStudy({commit, getters, state, dispatch}, {studyID, data}) {
    return StudyService.updateStudyChapter(studyID, data)
      .then(response => {
        commit('SET_STUDY', response.data);
        const notification = {
          type   : 'success',
          message: 'Your study has been updated'
        };
        dispatch('alert/add', notification, {root: true});
        return response.data;
      });
  },
  updateStudyThumbnail({commit, getters, state, dispatch}, {studyID, data}) {
    return StudyService.updateStudyThumbnail(studyID, data)
      .then(response => {
        commit('SET_STUDY', response.data);
        const notification = {
          type   : 'success',
          message: 'Your study has been updated'
        };
        dispatch('alert/add', notification, {root: true});
        return response.data;
      });
  },
  fetchNavigation({commit, getters, state}) {
    if (state.study.id && undefined !== state.study.navigation) {
      return state.study.navigation;
    }

    return StudyService.getStudyNavigation(state.study.id).then(response => {
      commit('SET_NAVIGATION', response.data);
      return response.data;
    });
  },
  updateNavigation({commit, getters, state}, {studyID, data}) {
    return StudyService.updateStudyNavigation(studyID, data)
      .then(response => {
        commit('SET_NAVIGATION', response.data);
        return response.data;
      });
  },
  updateStudyChapter({commit, getters, state}, {chapterID, data}) {
    return StudyService.updateStudyChapter(chapterID, data)
      .then(response => {
        commit('SET_CHAPTER', response.data);
        return response.data;
      });
  },
  getStudyChapters({commit, getters, state}, id) {
    return StudyService.getStudyChapters(id).then();
  },
  getStudyChapter({commit, getters, state}, {study, chapter}) {
    return StudyService.getStudyChapter(study, chapter).then(response => {
      commit('SET_CHAPTER', response.data);
      return response.data;
    });
  }
};
export const getters = {
  getStudyById      : state => id => {
    return state.studies.find(study => study.id === id);
  },
  getStudyNavigation: state => {
    return undefined === state.study.navigation ? [] : state.study.navigation;
  }
};