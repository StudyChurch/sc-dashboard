import apiClient from './apiClient'


let base = '/wp-json/studychurch/v1/assignments/';

export default {
  getAssignments(user = 0) {
    let path = '/wp-json/studychurch/v1/assignments/';

    return apiClient.get(path)
  },

    getAssignment( id ) {
      return apiClient.get( base + id );
    },

    updateAssignment( id ) {
      return apiClient.post( base + id );
    },

    createAssignment( data ) {
      return apiClient.post( base, data );
    },

    deleteAssignment( itemId ) {
      return apiClient.delete( base + itemId, { assignment_id: itemId } );
    }
}