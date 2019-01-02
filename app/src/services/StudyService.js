import apiClient from './apiClient'

export default {
  getStudies(user = 0) {
    let path = '/wp-json/studychurch/v1/studies/?status=any&per_page=100&orderby=title&order=asc';

    if (user) {
      path += '&author=' + user;
    }

    return apiClient.get(path)
  },
  getStudy(id) {
    return apiClient.get('/events/' + id)
  },
  postStudy(study) {
    return apiClient.post('/wp-json/studychurch/v1/studies/', study)
  }
}