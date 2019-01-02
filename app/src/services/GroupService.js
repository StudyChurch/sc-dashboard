import apiClient from './apiClient'

let base = '/wp-json/studychurch/v1/groups/';

export default {
  getGroups(user = 0) {
    let path =  base + '?show_hidden=true&group_type__not_in=organization';

    if (user) {
      path += '&user_id=' + user;
    }

    return apiClient.get(path)
  },
  getOrganizationGroups(org) {
    return apiClient.get(base, {
      params: {
        show_hidden: true,
        parent_id : org
      }
    });
  },
  getOrganizations(user) {
    return apiClient.get(base, {
      params: {
        show_hidden: true,
        group_type: 'organization',
        user_id: user
      }
    })
  },
  getOrganization(id) {
    return apiClient.get(base + id)
  },
  getGroup(id) {
    return apiClient.get(base + id)
  },
  postGroup(group) {
    return apiClient.post(base, group)
  },

  upgradeUser(userID, groupID) {
    return apiClient.post(base + groupID + '/promote/' + userID);
  },

  demoteUser(userID, groupID) {
    return apiClient.post(base + groupID + '/demote/' + userID);
  },

  removeUser(userID, groupID) {
    return apiClient.post(base + groupID + '/remove/' + userID);
  },

}