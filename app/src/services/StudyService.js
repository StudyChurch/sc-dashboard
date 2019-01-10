import apiClient from './apiClient'

let base = '/wp-json/studychurch/v1/studies/';

export default {
  getStudies(data) {
    let config = Object.assign({
      params : {
        per_page : 100,
        orderby : 'title',
        order : 'asc'
      },
    }, data);

    config = Object.assign(config, data);

    return apiClient.get(base, config)
  },
  getStudy(id) {
    return apiClient.get('/events/' + id)
  },
  postStudy(study) {
    return apiClient.post(base, study)
  }
}