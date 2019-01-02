import decode from 'jwt-decode';
import axios from 'axios';
import Router from 'vue-router';
const ACCESS_TOKEN_KEY = 'access_token';
const USER_DATA_KEY    = 'user_data';
//const PUBLIC_KEY = 'ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAACAQDrWNUnBF5yWl0uaTEEphkmxNmun0ZIG+s0ecL+cslFUrGHLp4jNK16X9uoPWbJjXZPHj3xAEZKTOJWdFsccaOgkQS1jfnlDc0tvE0r7pWex+XIX72FlUpJ72oTa3bZDVAi5GvKZMYAsANzyDpUpFgPdrw01Xd5PYuc5oNNSCcLDGVabu11t4GV1knbKuWqzv3bz8K5EtS4WF9SxCQKsgcDQ+je6KbQm6uVlIUIbCihKNT5cWU8x3gTSG97dzsPJnw9Cogrich+anbvJww1M7DMWzZ3M1Y+/PU3nvNmTvug/yVHOHUWVbv22M+3AMy+eafb7TxpMzyeWmfeNgRHkXgMqZeukgSS55gui3DC1RPfwgy7BAUfwzYA2e+XHW8whRv5x/hnNEnbiCPQiWnAuuI+rzhKVSCwyY+oU6HSaFlyd0eK7LBha3pb6KK8XWR/mK1D5+/sqNVyRx3kr/MQrc7rYH6v+NukCAgxVCZ6eUwWg1L32gG8xc7b7oAvjY+bE0jE6mdeQndwl9DkBwIcllXoN7tf1I2QTkFlTD+VbTW9ASJGXMZBYkEaCUuX9Cor+dbfTEolUEiQhPOqoSeSkEnNEjsDhC4wwbK4BKjIB9V6/kMqwWCP9ouSDh8vgJH37M0QeiUwisjGN6SW19/bnq5glj1aoVCc+BGqVjn8BEXL5Q== tannermoushey@Tanners-MBP-13';

let router = new Router({
   mode: 'history',
});

export function login(username, password, redirect = true) {

  axios
    .post('/wp-json/studychurch/v1/authenticate', {
      username,
      password
    } )
    .then(response => {
      setAccessToken(response.data.token);
      setUserData(response.data.user);

      if (redirect) {
        router.go('/');
      }
    });
}

export function logout() {
  clearAccessToken();
  router.go('/');
}

export function requireAuth(to, from, next) {
  if (!isLoggedIn()) {
    next({
      path: '/',
      query: { redirect: to.fullPath }
    });
  } else {
    next();
  }
}

export function getAccessToken() {
  return localStorage.getItem(ACCESS_TOKEN_KEY);
}

function clearAccessToken() {
  localStorage.removeItem(ACCESS_TOKEN_KEY);
}

// Get and store access_token in local storage
export function setAccessToken(accessToken) {
  localStorage.setItem(ACCESS_TOKEN_KEY, accessToken);
}

export function getUserData() {
  let data = {};

  try {
    data = JSON.parse(localStorage.getItem(USER_DATA_KEY));
  } catch (e) {
    return data;
  }

  return data;
}

export function getUserMeta(key) {
  let data = getUserData();

  return data[key];
}

export function getUserID() {
  return getUserData().id;
}

export function setUserData(userData) {
  localStorage.setItem(USER_DATA_KEY, JSON.stringify(userData));
}

function clearUserData() {
  localStorage.removeItem(USER_DATA_KEY);
}

export function isLoggedIn() {
  const token = getAccessToken();
  return !!token && !isTokenExpired(token) && getUserID();
}

function getTokenExpirationDate(encodedToken) {
  const token = decode(encodedToken);
  if (!token.exp) { return null; }

  const date = new Date(0);
  date.setUTCSeconds(token.exp);

  return date;
}

function isTokenExpired(token) {
  const expirationDate = getTokenExpirationDate(token);
  return expirationDate < new Date();
}