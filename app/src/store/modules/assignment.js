import AssignmentService from '@/services/AssignmentService.js';

export const namespaced = true;

export const state = {
    assignments: [],
    assignmentsTotal: 0,
    assignment: {
        id: 0,
        title: {
            rendered: '',
        },
        studies: [],
        description: '',
        date: '',
    }
};

export const mutations = {

    ADD_ASSIGNMENT( state, assignment ) {
      state.assignments.unshift( assignment );
    },

    SET_ASSIGNMENT( state, assignment ) {
      state.assignment = assignment;
    },

    SET_ASSIGNMENTS( state, assignments ) {
        state.assignments = assignments;
    },
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
                commit( 'SET_ASSIGNMENT', response.data );
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
        data = data || {};
        data.organizations = rootState.group.organizations.map( org => org.id );

        return AssignmentService.getAssignments().then( response => {
            commit( 'SET_ASSIGNMENTS', response.data );
            return response.data;
        } ).catch( error => {
            const notification = {
                type: 'error',
                message: 'There was a problem fetching assignments: ' + error.message,
            };
            dispatch( 'alert/add', notification, { root: true } );
            return 'error';
        });
    },

    fetchAssignment( { commit, getters, state }, { id, params = {} } ) {
        if ( id === state.assignment.id ) {
            return state.assignment;
        }

        let assignment = getters.getAssignmentById( id );

        if ( assignment ) {
            commit( 'SET_ASSIGNMENT', assignment );
            return assignment;
        } else {
            return dispatch( 'getAssignment' );
        }
    }
};

export const getters = {
    getAssignmentById      : state => id => {
        return state.assignments.find( study => assignment.id === id );
    }
};