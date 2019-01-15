import apiClient from './apiClient';

let base = '/wp-json/studychurch/v1/users/';

export default {
  getUsers(perPage, page) {
    return apiClient.get(base + perPage + '&_page=' + page);
  },
  getUsersById(ids) {
    ids = ids.join();
    return apiClient.get(base, {
      params : {
        per_page: 100,
        include: ids
      }
    });
  },
  getUser(id) {
    return apiClient.get(base + id);
  },
  updateUser(userID, data) {
    return apiClient.post(base + userID, data);
  },
  updateAvatar(userID, data) {
    return apiClient.post(base + userID + '/avatar', data, {
      headers: {
        'Content-Type' : 'multipart/form-data',
      }
    });
  },
  getMe() {
    return apiClient.get(base + 'me/');
  },
};