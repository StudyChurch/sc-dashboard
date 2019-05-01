export const namespaced = true;

export const mutations = {
    /*ADD_STUDY(state, study) {
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
    }*/


};

export const actions = {
    createAssignment( {commit, dispatch }, assignment ) {
        alert( assignment.message );
    }
};