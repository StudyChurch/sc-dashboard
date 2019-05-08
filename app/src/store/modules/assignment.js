import AssignmentService from '@/services/AssignmentService.js';

export const namespaced = true;

export const state = {
    assignments: [],
    assignmentsTotal: 0,
    assignment: {

    }
};

export const mutations = {

    ADD_ASSIGNMENT( state, assignment ) {
      state.assignments.unshift( assignment );
    }
};

export const actions = {
    createAssignment( {commit, dispatch }, data ) {
        return AssignmentService.createAssignment( data ).then( response => {
            commit( 'ADD_ASSIGNMENT', response.data );
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
    },

    deleteAssignment( { commit, dispatch }, itemId ) {
        return AssignmentService.deleteAssignment( itemId ).then( response => {
            return response.data;
        } );
    },

    fetchAssignments( {commit, dispatch, rootState }, data ) {
        // TODO
    },

    fetchAssignment( { commit, getters, state }, { id, params = {} } ) {
        if ( id === state.assignment.id ) {
            return state.assignment;
        }

        // TODO
    }
};

export const getters = {
    getAssignmentById      : state => id => {
        return state.assignments.find( study => assignment.id === id );
    }
};