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
  getStudy(id, data = {}) {
    return apiClient.get(base + id, data)
  },
  postStudy(study) {
    return apiClient.post(base, study)
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