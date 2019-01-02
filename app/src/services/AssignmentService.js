import apiClient from './apiClient'

export default {
  getAssignments(user = 0) {
    let path = '/wp-json/studychurch/v1/assignments/';

    return apiClient.get(path)
  },
}