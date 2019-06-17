import apiClient from './apiClient'

let path = '/wp-json/studychurch/v1/activity/';

export default {
  getActivity() {
    return apiClient.get(path)
  },

  addActivity(data) {
    return apiClient.post(path, data);
  },

  deleteActivity( item ) {
    return apiClient.delete( path + item.id );
  }
}