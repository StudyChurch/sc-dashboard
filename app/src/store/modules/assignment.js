import AssignmentService from '@/services/AssignmentService.js';

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

    ADD_ASSIGNMENT( state, assignment ) {
      // TODO
    },
    SET_ASSIGNMENT( state, assignment ) {
        state.assignment = assignment;
    }
};

export const actions = {
    createAssignment( {commit, dispatch }, data ) {
        return AssignmentService.createAssignment( data ).then( response => {
            return response.data;
        } );
    },

    getAssignment( {commit, dispatch }, assignmentId ) {
        return AssignmentService.getAssignment( assignmentId ).then( response => {
                //commit( 'SET_ASSIGNMENT', response.data );
                return response.data;
            });
    },

    updateAssignment( {commit, dispatch }, assignmentId ) {
        return AssignmentService.updateAssignment( assignmentId ).then( response => {
            //commit( 'SET_ASSIGNMENT', response.data );
            return response.data;
        } );
    }
};