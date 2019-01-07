import apiClient from './apiClient'

let base = '/wp-json/studychurch/v1/studies/';

export default {
  getStudies(data) {
    let config = Object.assign({
      params : {
        status : 'any',
        per_page : 100,
        orderby : 'title',
        order : 'asc',
        _embed : true
      },
    }, data);

    config = Object.assign(config, data);

    return apiClient.get(base, config)
  },
  getStudy(id) {
    return apiClient.get('/events/' + id)
  },
  postStudy(study) {
    return apiClient.post('/wp-json/studychurch/v1/studies/', study)
  }
}