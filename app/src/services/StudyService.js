import apiClient from './apiClient'

let base = '/wp-json/studychurch/v1/studies/';

export default {
  getStudies(params) {
    let config = {};
    config.params = Object.assign({
        per_page : 100,
        orderby : 'title',
        order : 'asc',
        status : ['publish', 'private'],
    }, params);

    return apiClient.get(base, config)
  },
  getStudy(id, data = {}) {
    return apiClient.get(base + id, data)
  },
  postStudy(study) {
    return apiClient.post(base, study)
  },
  deleteStudy(id) {
    return apiClient.delete(base + id)
  },
  getStudyChapters(id) {
    return apiClient.get(base + id + '/chapters/');
  },
  getStudyChapter(study, chapter) {
    return apiClient.get(base + study + '/chapters/' + chapter, {
      params : {
        context : 'edit'
      }
    });
  },
  getStudyNavigation(study) {
    return apiClient.get(base + study + '/navigation');
  },
  updateStudyNavigation(chapterID, data) {
    return apiClient.post(base + chapterID + '/navigation', data);
  },
  updateStudyChapter(chapterID, data) {
    return apiClient.post(base + chapterID, data);
  },
  updateStudyThumbnail(studyID, data) {
    return apiClient.post(base + studyID + '/thumbnail', data, {
      headers: {
        'Content-Type': 'multipart/form-data',
      }
    });
  }
}