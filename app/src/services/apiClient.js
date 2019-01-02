import axios from 'axios';
import Cookies from 'js-cookie';

const apiClient = axios.create({
  baseURL: window.location.protocol + '//' + window.location.host,
});

apiClient.defaults.headers.common['X-WP-NONCE'] = Cookies.get('_wpnonce');

apiClient.interceptors.response.use(function (response) {
  // Do something with response data
  apiClient.defaults.headers.common['X-WP-NONCE'] = response.headers['x-wp-nonce'];
  return response;
}, function (error) {
  console.log('error');
  console.log(error);
  // Do something with response error
  return Promise.reject(error);
});

export default apiClient;